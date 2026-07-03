/**
 * R2 Uploader — Upload Form Logic
 */
(function() {
    'use strict';

    window.switchTab = function(mode) {
        const modeInput = document.getElementById('upload-mode');
        if (modeInput) modeInput.value = mode;

        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        const activeTab = document.getElementById('tab-' + mode);
        if (activeTab) activeTab.classList.add('active');

        const remoteContent = document.getElementById('content-remote');
        const manualContent = document.getElementById('content-manual');
        const fileUrl = document.getElementById('fileUrl');
        const manualFile = document.getElementById('manualFile');
        const btnText = document.getElementById('submit-btn-text');

        if (mode === 'remote') {
            if (remoteContent) remoteContent.style.display = 'block';
            if (manualContent) manualContent.style.display = 'none';
            if (fileUrl) fileUrl.setAttribute('required', '');
            if (manualFile) manualFile.removeAttribute('required');
            if (btnText) btnText.innerText = 'Upload dari URL';
        } else {
            if (remoteContent) remoteContent.style.display = 'none';
            if (manualContent) manualContent.style.display = 'block';
            if (fileUrl) fileUrl.removeAttribute('required');
            if (manualFile) manualFile.setAttribute('required', '');
            if (btnText) btnText.innerText = 'Upload dari Perangkat';
        }
    };

    window.setUploadingState = function(isUploading, mode) {
        const btn = document.getElementById('submit-btn');
        const btnText = document.getElementById('submit-btn-text');
        const spinner = document.getElementById('submit-spinner');
        const helper = document.getElementById('submit-helper');

        if (!btn || !btnText || !spinner) return;

        if (!isUploading) {
            btn.disabled = false;
            spinner.style.display = 'none';
            if (helper) helper.style.display = 'none';
            return;
        }

        btn.disabled = true;
        spinner.style.display = 'inline-block';
        btnText.innerText = mode === 'manual' ? 'Sedang mengunggah…' : 'Sedang mengunduh…';

        if (helper) {
            helper.style.display = 'block';
            helper.innerText = 'Jangan tutup halaman ini sampai proses selesai.';
        }
    };

    // Update file list visually
    window.updateFileInputText = function(input) {
        const listContainer = document.getElementById('selected-files-list');
        if (!listContainer) return;
        
        listContainer.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach(file => {
                const item = document.createElement('div');
                item.className = 'selected-file-item';
                
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                item.innerHTML = `
                    <div class="selected-file-name" title="${file.name}">
                        <svg style="width:1rem;height:1rem;display:inline-block;vertical-align:text-bottom;margin-right:0.25rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        ${file.name}
                    </div>
                    <div style="color:var(--text-muted);font-size:0.75rem;">${sizeMB} MB</div>
                `;
                listContainer.appendChild(item);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('upload-form');
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('manualFile');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');

        if (dropZone && fileInput) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
            });

            dropZone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    updateFileInputText(fileInput);
                }
            }, false);
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const modeInput = document.getElementById('upload-mode');
                const mode = modeInput ? modeInput.value : 'remote';
                setUploadingState(true, mode);
                
                const formData = new FormData(form);
                
                if (mode === 'manual' && progressContainer) {
                    progressContainer.style.display = 'block';
                    progressText.style.display = 'block';
                    progressBar.style.width = '0%';
                    progressText.innerText = '0% (Memulai...)';
                }

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action || window.location.href, true);
                xhr.setRequestHeader('Accept', 'application/json');
                
                if (mode === 'manual' && xhr.upload) {
                    xhr.upload.onprogress = function(event) {
                        if (event.lengthComputable && progressContainer) {
                            const percentComplete = (event.loaded / event.total) * 100;
                            progressBar.style.width = percentComplete + '%';
                            
                            const loadedMB = (event.loaded / (1024 * 1024)).toFixed(2);
                            const totalMB = (event.total / (1024 * 1024)).toFixed(2);
                            progressText.innerText = `${Math.round(percentComplete)}% (${loadedMB} / ${totalMB} MB)`;
                        }
                    };
                }

                xhr.onload = function() {
                    setUploadingState(false, mode);
                    if (mode === 'manual' && progressContainer) {
                        progressContainer.style.display = 'none';
                        progressText.style.display = 'none';
                    }
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                if (window.showToast) window.showToast('Upload berhasil!', 'success');
                                // Fallback to reload if JSON success
                                window.location.reload();
                            } else {
                                if (window.showToast) window.showToast(res.error || 'Terjadi kesalahan.', 'error');
                            }
                        } catch (e) {
                            // If response is not JSON, it might be the HTML success page.
                            // We replace document with it.
                            document.open();
                            document.write(xhr.responseText);
                            document.close();
                        }
                    } else {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.error) {
                                if (window.showToast) window.showToast(res.error, 'error');
                                return;
                            }
                        } catch(e) {}
                        if (window.showToast) window.showToast(`Error: ${xhr.statusText || 'Gagal mengupload.'}`, 'error');
                    }
                };

                xhr.onerror = function() {
                    setUploadingState(false, mode);
                    if (mode === 'manual' && progressContainer) {
                        progressContainer.style.display = 'none';
                        progressText.style.display = 'none';
                    }
                    if (window.showToast) window.showToast('Gagal terhubung ke server.', 'error');
                };

                xhr.send(formData);
            });
        }
    });
})();

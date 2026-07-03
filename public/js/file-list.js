/**
 * R2 Uploader — File List Logic
 */
(function() {
    'use strict';

    // Rename file via native dialog
    window.renameFile = function(oldKey, type) {
        const modal = document.getElementById('rename-modal');
        const form = document.getElementById('rename-form');
        const input = document.getElementById('rename-input');
        
        if (!modal || !form || !input) return;
        
        form.action = '/?action=rename&type=' + encodeURIComponent(type) + '&key=' + encodeURIComponent(oldKey);
        input.value = oldKey;
        modal.showModal();
    };

    // Search/Filter
    window.filterFiles = function(query) {
        const rows = document.querySelectorAll('.file-row');
        const q = query.toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(row => {
            const fileName = row.querySelector('.file-link')?.textContent?.toLowerCase() || '';
            const match = !q || fileName.includes(q);
            row.dataset.filtered = match ? 'false' : 'true';
            if (match) visibleCount++;
        });

        if (window._r2Pagination) {
            window._r2Pagination.reset();
        }

        const emptyState = document.getElementById('search-empty-state');
        if (emptyState) {
            emptyState.style.display = (visibleCount === 0 && q) ? 'block' : 'none';
        }
    };

    // Client-side Pagination
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('pagination-controls');
        const info = document.getElementById('pagination-info');
        const selectEl = document.getElementById('per-page-select');
        const tableContainer = document.getElementById('file-table-container');

        if (!container || !tableContainer) return;

        const STORAGE_KEY = 'r2mgr_per_page';
        let perPage = parseInt(localStorage.getItem(STORAGE_KEY) || '25', 10);
        let currentPage = 1;
        const validValues = [25, 50, 100];
        if (!validValues.includes(perPage)) perPage = 25;

        function getVisibleRows() {
            return Array.from(document.querySelectorAll('.file-row')).filter(r => r.dataset.filtered !== 'true');
        }

        function render() {
            const rows = getVisibleRows();
            const totalFiles = rows.length;
            const totalPages = Math.max(1, Math.ceil(totalFiles / perPage));
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * perPage;
            const end = Math.min(start + perPage, totalFiles);

            rows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            document.querySelectorAll('.file-row[data-filtered="true"]').forEach(row => {
                row.style.display = 'none';
            });

            if (info) {
                info.textContent = totalFiles === 0
                    ? 'Tidak ada file'
                    : 'Menampilkan ' + (start + 1) + '-' + end + ' dari ' + totalFiles + ' file';
            }

            container.innerHTML = '';
            if (totalPages <= 1) return;

            container.appendChild(createBtn('\u00ab', currentPage > 1, () => goTo(currentPage - 1)));

            getPages(currentPage, totalPages).forEach(p => {
                if (p === '...') {
                    const el = document.createElement('span');
                    el.className = 'page-ellipsis';
                    el.textContent = '\u2026';
                    container.appendChild(el);
                } else {
                    container.appendChild(createBtn(String(p), true, () => goTo(p), p === currentPage));
                }
            });

            container.appendChild(createBtn('\u00bb', currentPage < totalPages, () => goTo(currentPage + 1)));
        }

        function goTo(page) {
            currentPage = page;
            render();
            const table = document.getElementById('file-table');
            if (table) table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function createBtn(label, enabled, onClick, isActive) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (isActive ? ' active' : '');
            btn.textContent = label;
            if (!enabled) btn.disabled = true;
            btn.addEventListener('click', onClick);
            return btn;
        }

        function getPages(current, total) {
            if (total <= 7) {
                const arr = [];
                for (let i = 1; i <= total; i++) arr.push(i);
                return arr;
            }
            const pages = [1];
            if (current > 3) pages.push('...');
            for (let s = Math.max(2, current - 1); s <= Math.min(total - 1, current + 1); s++) {
                pages.push(s);
            }
            if (current < total - 2) pages.push('...');
            pages.push(total);
            return pages;
        }

        window._r2Pagination = {
            reset: function() { currentPage = 1; render(); },
            changePerPage: function(val) {
                perPage = parseInt(val, 10);
                localStorage.setItem(STORAGE_KEY, String(perPage));
                currentPage = 1;
                render();
            }
        };
        window.changePerPage = window._r2Pagination.changePerPage;

        if (selectEl) selectEl.value = String(perPage);
        render();

    });

    // --- Preview Modal ---
    window.previewFile = function(url, filename) {
        const modal = document.getElementById('preview-modal');
        const title = document.getElementById('preview-title');
        const container = document.getElementById('preview-container');
        const dlBtn = document.getElementById('preview-download-btn');
        
        if (!modal || !container) return;
        
        title.textContent = filename;
        dlBtn.href = url;
        container.innerHTML = '<div class="spinner" style="border-color:var(--accent); border-right-color:transparent;"></div>';
        modal.showModal();
        
        const ext = filename.split('.').pop().toLowerCase();
        const images = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        const videos = ['mp4', 'webm', 'ogg'];
        const audios = ['mp3', 'wav', 'ogg', 'aac'];
        
        const sanitizeHtml = (str) => {
            const temp = document.createElement('div');
            temp.textContent = str;
            return temp.innerHTML;
        };
        const safeUrl = sanitizeHtml(url);
        const safeExt = sanitizeHtml(ext);

        if (images.includes(ext)) {
            const img = new Image();
            img.onload = () => {
                img.style.maxWidth = '100%';
                img.style.maxHeight = '60vh';
                img.style.objectFit = 'contain';
                container.innerHTML = '';
                container.appendChild(img);
            };
            img.onerror = () => {
                container.innerHTML = '<p style="color:var(--danger);">Gagal memuat gambar.</p>';
            };
            img.src = url;
        } else if (videos.includes(ext)) {
            container.innerHTML = `
                <video controls style="max-width:100%; max-height:60vh;">
                    <source src="${safeUrl}" type="video/${safeExt === 'mp4' ? 'mp4' : (safeExt === 'webm' ? 'webm' : 'ogg')}">
                    Browser Anda tidak mendukung tag video.
                </video>
            `;
        } else if (audios.includes(ext)) {
            container.innerHTML = `
                <audio controls style="width:100%;">
                    <source src="${safeUrl}" type="audio/${safeExt === 'mp3' ? 'mpeg' : (safeExt === 'wav' ? 'wav' : 'ogg')}">
                    Browser Anda tidak mendukung tag audio.
                </audio>
            `;
        } else {
            container.innerHTML = `
                <div style="text-align:center; color:var(--text-muted);">
                    <svg style="width:4rem;height:4rem; margin-bottom:1rem; opacity:0.5;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p>Preview tidak tersedia untuk format file ini.</p>
                </div>
            `;
        }
    };
    
    window.closePreview = function() {
        const modal = document.getElementById('preview-modal');
        const container = document.getElementById('preview-container');
        if (modal) {
            modal.close();
            if (container) container.innerHTML = ''; // Stop media playing
        }
    };

    // Stop media playing when dialog is closed via Esc key or form button
    const previewModal = document.getElementById('preview-modal');
    if (previewModal) {
        previewModal.addEventListener('close', function() {
            const container = document.getElementById('preview-container');
            if (container) container.innerHTML = '';
        });
        
        // Light-dismiss by clicking on the backdrop
        previewModal.addEventListener('click', function(event) {
            if (event.target === previewModal) {
                previewModal.close();
            }
        });
    }

    const renameModal = document.getElementById('rename-modal');
    if (renameModal) {
        // Light-dismiss by clicking on the backdrop
        renameModal.addEventListener('click', function(event) {
            if (event.target === renameModal) {
                renameModal.close();
            }
        });
    }

})();

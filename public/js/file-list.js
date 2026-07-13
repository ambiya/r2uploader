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

    // Client-side Pagination replaced with AJAX
    document.addEventListener('DOMContentLoaded', function() {
        const fileTableBody = document.getElementById('file-table-body');
        if (!fileTableBody) return; // Not on list page

        const folderGrid = document.getElementById('folder-grid');
        const folderTitle = document.getElementById('folder-title');
        const fileTitle = document.getElementById('file-title');
        const fileTableContainer = document.getElementById('file-table-container');
        const searchEmptyState = document.getElementById('search-empty-state');
        const folderEmptyState = document.getElementById('folder-empty-state');
        const spinner = document.getElementById('loading-spinner');
        const paginationBar = document.getElementById('pagination-bar');
        
        const btnPrev = document.getElementById('btn-prev-page');
        const btnNext = document.getElementById('btn-next-page');
        const pageIndicator = document.getElementById('page-indicator');
        const perPageSelect = document.getElementById('per-page-select');
        const searchInput = document.getElementById('file-search-input');
        const flatViewCheckbox = document.getElementById('flat-view-checkbox');
        const sortableHeaders = document.querySelectorAll('.sortable-header');

        const STORAGE_KEY = 'r2mgr_per_page';
        let limit = parseInt(localStorage.getItem(STORAGE_KEY) || '25', 10);
        const validValues = [25, 50, 100];
        if (!validValues.includes(limit)) limit = 25;
        if (perPageSelect) perPageSelect.value = String(limit);

        // State
        let currentPageIndex = 0; // 0-based index
        let historyTokens = [null]; // Maps page index to continuation token
        let currentSearch = searchInput ? searchInput.value.trim() : '';
        const selectedFiles = new Set();

        // Extract current URL params
        const urlParams = new URLSearchParams(window.location.search);
        const typeParam = urlParams.get('type') || '';
        const prefixParam = urlParams.get('prefix') || '';
        
        let currentSort = urlParams.get('sort') || localStorage.getItem('r2mgr_sort') || 'name';
        let currentOrder = urlParams.get('order') || localStorage.getItem('r2mgr_order') || 'asc';
        let currentFlat = urlParams.get('flat') === '1' || localStorage.getItem('r2mgr_flat') === '1';

        if (flatViewCheckbox) flatViewCheckbox.checked = currentFlat;

        const bulkBar = document.getElementById('bulk-actions-bar');
        const selectedCountSpan = document.getElementById('bulk-selected-count');
        const selectAllCheckbox = document.getElementById('select-all-checkbox');

        function updateBulkActionsBar() {
            if (!bulkBar || !selectedCountSpan) return;
            if (selectedFiles.size > 0) {
                bulkBar.style.display = 'flex';
                selectedCountSpan.textContent = selectedFiles.size;
            } else {
                bulkBar.style.display = 'none';
            }

            const renderedCheckboxes = document.querySelectorAll('.file-select-checkbox');
            if (renderedCheckboxes.length > 0) {
                let allChecked = true;
                renderedCheckboxes.forEach(cb => {
                    if (!cb.checked) allChecked = false;
                });
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.disabled = false;
                }
            } else {
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.disabled = true;
                }
            }
        }

        function updateSortIndicators() {
            sortableHeaders.forEach(th => {
                const indicator = th.querySelector('.sort-indicator');
                if (!indicator) return;
                
                if (th.dataset.sort === currentSort) {
                    indicator.textContent = currentOrder === 'asc' ? '▲' : '▼';
                    indicator.style.color = 'var(--accent)';
                } else {
                    indicator.textContent = '';
                }
            });
        }
        
        // Initial setup for indicators
        updateSortIndicators();

        function renderFileRow(obj, publicUrl, type) {
            const fileUrl = publicUrl.replace(/\/$/, '') + '/' + obj.Key.replace(/^\//, '');
            const displayName = obj.Key.replace(prefixParam, '');
            const isChecked = selectedFiles.has(obj.Key) ? 'checked' : '';
            
            const tr = document.createElement('tr');
            tr.className = 'file-row';
            
            const dateStr = obj.LastModified ? new Date(obj.LastModified).toLocaleString() : '-';
            
            tr.innerHTML = `
              <td style="text-align: center; vertical-align: middle; width: 40px;">
                <input type="checkbox" class="file-select-checkbox" data-key="${escapeHtml(obj.Key)}" ${isChecked} style="width: auto; cursor: pointer; transform: scale(1.1);">
              </td>
              <td>
                <div class="file-name-cell">
                  <span style="color:var(--accent);">
                    <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                  </span>
                  <a href="${escapeHtml(fileUrl)}" class="file-link" target="_blank" style="word-break:break-all;">${escapeHtml(displayName)}</a>
                </div>
              </td>
              <td><span style="color:var(--text-muted); font-size:0.85rem;">${escapeHtml(dateStr)}</span></td>
              <td><span class="badge" style="background-color:var(--bg-app); border:1px solid var(--border); color:var(--text-muted);">${escapeHtml(obj.SizeMB)} MB</span></td>
              <td>
                <div class="actions-cell" style="justify-content:flex-end;">
                  <button class="btn btn-secondary" onclick="copyToClipboard('${escapeJs(fileUrl)}')" style="padding:0.4rem 0.65rem;" title="Copy URL">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                  </button>
                  <a href="${escapeHtml(fileUrl)}" target="_blank" class="btn btn-secondary" style="padding:0.4rem 0.65rem;" title="Download">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                  </a>
                  <button class="btn btn-warning" onclick="renameFile('${escapeJs(obj.Key)}','${escapeJs(type)}')" style="padding:0.4rem 0.65rem;" title="Rename">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                  </button>
                  <form method="POST" action="/?action=delete&type=${encodeURIComponent(type)}&key=${encodeURIComponent(obj.Key)}" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                    <input type="hidden" name="csrf_token" value="${escapeHtml(window._csrfToken || '')}">
                    <button class="btn btn-danger" style="padding:0.4rem 0.65rem;" title="Delete">
                      <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </form>
                </div>
              </td>
            `;
            return tr;
        }

        function renderFolderItem(folder, type) {
            const folderDisplay = folder.replace(prefixParam, '');
            const a = document.createElement('a');
            a.className = 'folder-item';
            a.href = `/?action=list&type=${encodeURIComponent(type)}&prefix=${encodeURIComponent(folder)}`;
            a.innerHTML = `
              <span class="folder-icon">
                <svg style="width:1.25rem;height:1.25rem;" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
              </span>
              <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(folderDisplay)}</span>
            `;
            return a;
        }

        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }
        
        function escapeJs(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString().replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        function fetchPage() {
            // UI State loading
            spinner.style.display = 'block';
            fileTableContainer.style.display = 'none';
            folderGrid.style.display = 'none';
            folderTitle.style.display = 'none';
            fileTitle.style.display = 'none';
            paginationBar.style.display = 'none';
            searchEmptyState.style.display = 'none';
            folderEmptyState.style.display = 'none';
            fileTableBody.innerHTML = '';
            folderGrid.innerHTML = '';

            const token = historyTokens[currentPageIndex];
            
            // Build API URL
            const apiUrl = new URL(window.location.origin + '/');
            apiUrl.searchParams.set('action', 'api_list');
            apiUrl.searchParams.set('type', typeParam);
            apiUrl.searchParams.set('prefix', prefixParam);
            apiUrl.searchParams.set('limit', limit);
            apiUrl.searchParams.set('page', currentPageIndex + 1);
            
            if (currentSort !== 'name') apiUrl.searchParams.set('sort', currentSort);
            if (currentOrder !== 'asc') apiUrl.searchParams.set('order', currentOrder);
            if (currentFlat) apiUrl.searchParams.set('flat', '1');
            
            if (currentSearch) {
                apiUrl.searchParams.set('q', currentSearch);
            }

            fetch(apiUrl)
                .then(res => res.json())
                .then(data => {
                    spinner.style.display = 'none';
                    if (data.error) {
                        searchEmptyState.textContent = data.error;
                        searchEmptyState.style.display = 'block';
                        return;
                    }

                    window._csrfToken = data.csrfToken; // Store for delete forms

                    // Render folders
                    if (data.prefixes && data.prefixes.length > 0) {
                        folderTitle.style.display = 'block';
                        folderGrid.style.display = 'grid';
                        data.prefixes.forEach(p => {
                            folderGrid.appendChild(renderFolderItem(p, data.type));
                        });
                    }

                    // Render files
                    if (data.objects && data.objects.length > 0) {
                        fileTitle.style.display = 'block';
                        fileTableContainer.style.display = 'block';
                        data.objects.forEach(obj => {
                            fileTableBody.appendChild(renderFileRow(obj, data.publicUrl, data.type));
                        });
                    }

                    // Empty states
                    if (data.objects.length === 0 && data.prefixes.length === 0) {
                        if (currentSearch) {
                            searchEmptyState.style.display = 'block';
                        } else {
                            folderEmptyState.style.display = 'block';
                        }
                    }

                    // Pagination state
                    if (data.objects.length > 0 || data.prefixes.length > 0 || currentPageIndex > 0) {
                        paginationBar.style.display = 'flex';
                        pageIndicator.textContent = `Page ${currentPageIndex + 1}`;
                        
                        btnPrev.disabled = currentPageIndex === 0;
                        btnNext.disabled = !data.isTruncated;
                    }

                    // If there's a next page, ensure we store its token
                    if (data.isTruncated) {
                        // For search, we just increment page. For normal, we need the token.
                        historyTokens[currentPageIndex + 1] = currentSearch ? null : data.nextToken;
                    }

                    updateBulkActionsBar();
                })
                .catch(err => {
                    spinner.style.display = 'none';
                    searchEmptyState.textContent = 'Gagal memuat data.';
                    searchEmptyState.style.display = 'block';
                    console.error(err);
                });
        }

        // Event Listeners
        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (currentPageIndex > 0) {
                    currentPageIndex--;
                    fetchPage();
                }
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                currentPageIndex++;
                fetchPage();
            });
        }

        if (perPageSelect) {
            perPageSelect.addEventListener('change', (e) => {
                limit = parseInt(e.target.value, 10);
                localStorage.setItem(STORAGE_KEY, String(limit));
                // Reset to page 1
                currentPageIndex = 0;
                historyTokens = [null];
                fetchPage();
            });
        }
        
        function updateUrlAndFetch() {
            const url = new URL(window.location.href);
            if (currentSearch) url.searchParams.set('q', currentSearch); else url.searchParams.delete('q');
            if (currentSort !== 'name') url.searchParams.set('sort', currentSort); else url.searchParams.delete('sort');
            if (currentOrder !== 'asc') url.searchParams.set('order', currentOrder); else url.searchParams.delete('order');
            if (currentFlat) url.searchParams.set('flat', '1'); else url.searchParams.delete('flat');
            window.history.replaceState({}, '', url);
            updateSortIndicators();
            fetchPage();
        }

        sortableHeaders.forEach(th => {
            th.addEventListener('click', () => {
                const sortKey = th.dataset.sort;
                if (currentSort === sortKey) {
                    // Toggle order
                    currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    // Change sort column, default to asc
                    currentSort = sortKey;
                    currentOrder = 'asc';
                }
                
                localStorage.setItem('r2mgr_sort', currentSort);
                localStorage.setItem('r2mgr_order', currentOrder);
                currentPageIndex = 0;
                historyTokens = [null];
                updateUrlAndFetch();
            });
        });

        if (flatViewCheckbox) {
            flatViewCheckbox.addEventListener('change', (e) => {
                currentFlat = e.target.checked;
                localStorage.setItem('r2mgr_flat', currentFlat ? '1' : '0');
                currentPageIndex = 0;
                historyTokens = [null];
                updateUrlAndFetch();
            });
        }

        // Search (Debounce)
        if (searchInput) {
            let timeout = null;
            // Also prevent form submission since we do AJAX
            const form = searchInput.closest('form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    triggerSearch();
                });
            }

            searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(triggerSearch, 500);
            });

            function triggerSearch() {
                const val = searchInput.value.trim();
                if (val !== currentSearch) {
                    currentSearch = val;
                    currentPageIndex = 0;
                    historyTokens = [null];
                    updateUrlAndFetch();
                }
            }
        }

        // Checkbox change via event delegation
        fileTableBody.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('file-select-checkbox')) {
                const key = e.target.dataset.key;
                if (e.target.checked) {
                    selectedFiles.add(key);
                } else {
                    selectedFiles.delete(key);
                }
                updateBulkActionsBar();
            }
        });

        // Select All change
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checked = selectAllCheckbox.checked;
                const checkboxes = document.querySelectorAll('.file-select-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = checked;
                    const key = cb.dataset.key;
                    if (checked) {
                        selectedFiles.add(key);
                    } else {
                        selectedFiles.delete(key);
                    }
                });
                updateBulkActionsBar();
            });
        }

        // Bulk Delete Action
        const btnBulkDelete = document.getElementById('btn-bulk-delete');
        if (btnBulkDelete) {
            btnBulkDelete.addEventListener('click', function() {
                if (selectedFiles.size === 0) return;
                
                const confirmMsg = document.documentElement.lang === 'id' 
                    ? `Apakah Anda yakin ingin menghapus ${selectedFiles.size} file terpilih?` 
                    : `Are you sure you want to delete ${selectedFiles.size} selected file(s)?`;
                
                if (!confirm(confirmMsg)) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/?action=bulk_delete&type=' + encodeURIComponent(typeParam);

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = window._csrfToken || '';
                form.appendChild(csrfInput);

                selectedFiles.forEach(key => {
                    const keyInput = document.createElement('input');
                    keyInput.type = 'hidden';
                    keyInput.name = 'keys[]';
                    keyInput.value = key;
                    form.appendChild(keyInput);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Bulk Download Action
        const btnBulkDownload = document.getElementById('btn-bulk-download');
        if (btnBulkDownload) {
            btnBulkDownload.addEventListener('click', function() {
                if (selectedFiles.size === 0) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/?action=bulk_download&type=' + encodeURIComponent(typeParam);

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = window._csrfToken || '';
                form.appendChild(csrfInput);

                selectedFiles.forEach(key => {
                    const keyInput = document.createElement('input');
                    keyInput.type = 'hidden';
                    keyInput.name = 'keys[]';
                    keyInput.value = key;
                    form.appendChild(keyInput);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                // Reset state
                selectedFiles.clear();
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                document.querySelectorAll('.file-select-checkbox').forEach(cb => cb.checked = false);
                updateBulkActionsBar();
            });
        }

        // Initial fetch
        fetchPage();
    });

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

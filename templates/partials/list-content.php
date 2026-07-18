<!-- Breadcrumbs -->
<div class="breadcrumbs" style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.75rem;">
  <a href="/?action=upload">🏠 <?= __('nav_upload') ?></a>
  <span>/</span> 
  <a href="/?action=list" style="text-decoration:none; color:inherit;"><span><?= __('nav_file_manager') ?></span></a>
  <span>/</span>
  <a href="?action=list&type=<?= urlencode((string)$type) ?>" style="text-decoration:none; color:inherit; font-weight:normal;"><?= htmlspecialchars(ucfirst((string)$type)) ?></a>
  <?php if (!empty($prefix)):
    $parts = explode('/', rtrim($prefix, '/'));
    $currentPath = '';
    foreach ($parts as $part):
      $currentPath .= $part . '/';
  ?>
    <span>/</span>
    <a href="?action=list&type=<?= urlencode((string)$type) ?>&prefix=<?= urlencode($currentPath) ?>"><?= htmlspecialchars(ucfirst((string)$part)) ?></a>
  <?php endforeach; endif; ?>
</div>

<!-- Bucket Selector -->
<div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:1.5rem; font-size:0.9rem; color:var(--text-muted);">
  <span style="font-weight: 500; display: flex; align-items: center; gap: 0.25rem;">
    📦 <?= __('choose_bucket') ?>
  </span>
  <select onchange="window.location.href='?action=list&type='+encodeURIComponent(this.value)" style="width:auto; padding:0.25rem 0.65rem; border-radius:6px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-main); font-size:0.85rem; font-weight:normal; cursor:pointer; outline:none; transition:var(--transition);">
    <?php if (isset($buckets) && is_array($buckets)): ?>
      <?php foreach ($buckets as $key => $b): ?>
        <option value="<?= htmlspecialchars((string)$key) ?>" <?= $key === $type ? 'selected' : '' ?>>
          <?= htmlspecialchars(ucfirst((string)$key)) ?>
        </option>
      <?php endforeach; ?>
    <?php else: ?>
      <option value="<?= htmlspecialchars((string)$type) ?>"><?= htmlspecialchars(ucfirst((string)$type)) ?></option>
    <?php endif; ?>
  </select>
</div>


<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
    <h2 style="font-size:1.35rem; display:flex; align-items:center; gap:0.5rem;">
      <svg style="width:1.5rem;height:1.5rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
      <?= __('file_folder_list') ?>
    </h2>
    <div style="display:flex; gap:0.5rem; align-items:center;">
      <button id="btn-sync-bucket" class="btn btn-secondary" onclick="syncBucketIndex()" style="display:flex; align-items:center; gap:0.35rem;" title="Sync database with R2">
        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        Sync
      </button>
      <button class="btn btn-secondary" onclick="openCreateFolderModal()" style="display:flex; align-items:center; gap:0.35rem;">
        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v8m0 0v8m0-8h8m-8 0H4"></path></svg>
        <?= __('create_folder') ?>
      </button>
      <?php if (!empty($prefix)):
        $backParts = explode('/', rtrim($prefix, '/'));
        array_pop($backParts);
        $parent = count($backParts) > 0 ? implode('/', $backParts) . '/' : '';
      ?>
        <a href="?action=list&type=<?= urlencode((string)$type) ?>&prefix=<?= urlencode($parent) ?>" class="btn btn-secondary" style="display:flex; align-items:center; gap:0.35rem;">
          <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
          <?= __('back') ?>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Search & Filter bar -->
  <div class="form-group" style="margin-bottom:1.5rem; display:flex; gap:0.5rem; max-width:100%; flex-wrap:wrap; align-items:center;">
    <input type="text" id="file-search-input" placeholder="<?= __('search_placeholder') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="flex:1; min-width: 200px; max-width: 400px;">
    
    <label style="display:flex; align-items:center; gap:0.35rem; font-size:0.85rem; cursor:pointer;">
      <input type="checkbox" id="flat-view-checkbox" style="width:auto;">
      Flat View (No Folders)
    </label>
  </div>

  <!-- Bulk Actions Bar -->
  <div id="bulk-actions-bar" class="card" style="display: none; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; border-color: var(--accent); background: var(--accent-soft); flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; font-size: 0.9rem;">
      🗂️ <span id="bulk-selected-count">0</span> file terpilih
    </div>
    <div style="display: flex; gap: 0.5rem;">
      <button id="btn-bulk-download" class="btn btn-secondary" style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; padding: 0.4rem 0.75rem;">
        <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
        Unduh ZIP
      </button>
      <button id="btn-bulk-delete" class="btn btn-danger" style="display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; padding: 0.4rem 0.75rem;">
        <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
        Hapus Terpilih
      </button>
    </div>
  </div>

  <!-- Folders Container -->
  <h3 id="folder-title" style="display:none; font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem; font-weight:600; letter-spacing:0.05em;">📁 <?= __('folder') ?></h3>
  <div class="folder-grid" id="folder-grid">
      <!-- Injected via JS -->
  </div>

  <!-- Files Container -->
  <h3 id="file-title" style="display:none; font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.5rem; font-weight:600; letter-spacing:0.05em; margin-top:1.5rem;">📄 <?= __('files') ?></h3>

  <div id="file-table-container" style="overflow-x:auto; display:none;">
    <table id="file-table">
      <thead>
        <tr>
          <th style="width: 40px; text-align: center; vertical-align: middle;">
            <input type="checkbox" id="select-all-checkbox" style="width: auto; cursor: pointer; transform: scale(1.15);">
          </th>
          <th class="sortable-header" data-sort="name" style="cursor:pointer; user-select:none; min-width:200px;">
            <?= __('file_name') ?> <span class="sort-indicator" style="font-size:0.8em; margin-left:2px; display:inline-block; width:10px;"></span>
          </th>
          <th class="sortable-header" data-sort="date" style="cursor:pointer; user-select:none; white-space:nowrap;">
            <?= __('upload_date') ?> <span class="sort-indicator" style="font-size:0.8em; margin-left:2px; display:inline-block; width:10px;"></span>
          </th>
          <th class="sortable-header" data-sort="size" style="cursor:pointer; user-select:none; white-space:nowrap;">
            <?= __('size') ?> <span class="sort-indicator" style="font-size:0.8em; margin-left:2px; display:inline-block; width:10px;"></span>
          </th>
          <th style="text-align:right; white-space:nowrap;"><?= __('action') ?></th>
        </tr>
      </thead>
      <tbody id="file-table-body">
        <!-- Injected via JS -->
      </tbody>
    </table>
  </div>

  <div class="pagination-bar" id="pagination-bar" style="display:none;">
    <div style="display: flex; gap: 0.5rem; align-items: center;" id="pagination-buttons">
      <button id="btn-prev-page" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;" disabled>&laquo; Prev</button>
      <span id="page-indicator" style="font-size: 0.85rem; font-weight: 500;">Page 1</span>
      <button id="btn-next-page" class="btn btn-secondary" style="padding: 0.25rem 0.5rem;" disabled>Next &raquo;</button>
    </div>
    <label class="per-page-select">
      <?= __('per_page') ?>
      <select id="per-page-select">
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </label>
  </div>

  <div id="loading-spinner" style="text-align:center; padding:3rem; color:var(--text-muted);">
      <svg style="width:2rem;height:2rem; animation: spin 1s linear infinite;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
      <div style="margin-top: 1rem; font-size: 0.9rem;">Loading...</div>
      <style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
  </div>

  <p id="search-empty-state" style="display:none; text-align:center; color:var(--text-muted); padding:2rem 0; font-style:italic;"><?= __('no_files_found') ?></p>
  <p id="folder-empty-state" style="display:none; text-align:center; color:var(--text-muted); padding:2rem 0; font-style:italic;"><?= __('no_files_in_folder') ?></p>

</div>

<!-- Rename Modal -->
<dialog id="rename-modal" class="card" style="width:90%; max-width:500px; padding:20px; border:1px solid var(--border); box-shadow:var(--shadow-lg); background-color:var(--bg-app); color:var(--text-main);">
  <form id="rename-form" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <h3 style="margin-top:0; margin-bottom:15px;"><?= __('rename_file') ?></h3>
    <div class="form-group">
      <label for="rename-input"><?= __('new_name') ?></label>
      <input type="text" id="rename-input" name="newKey" required placeholder="folder/baru.apk">
    </div>
    <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('rename-modal').close()"><?= __('cancel') ?></button>
      <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
    </div>
  </form>
</dialog>

<!-- Create Folder Modal -->
<dialog id="create-folder-modal" class="card" style="width:90%; max-width:500px; padding:20px; border:1px solid var(--border); box-shadow:var(--shadow-lg); background-color:var(--bg-app); color:var(--text-main);">
  <form id="create-folder-form" method="POST" action="/?action=create_folder&type=<?= urlencode((string)$type) ?>&prefix=<?= urlencode((string)$prefix) ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <h3 style="margin-top:0; margin-bottom:15px;"><?= __('create_folder') ?></h3>
    <div class="form-group">
      <label for="folder-name-input"><?= __('folder_name') ?></label>
      <input type="text" id="folder-name-input" name="folderName" required placeholder="Contoh: dokumen, images/2026">
    </div>
    <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-folder-modal').close()"><?= __('cancel') ?></button>
      <button type="submit" class="btn btn-primary"><?= __('btn_create') ?></button>
    </div>
  </form>
</dialog>

<!-- Delete Modal -->
<dialog id="delete-modal" class="card" style="width:90%; max-width:400px; padding:20px; border:1px solid var(--border); box-shadow:var(--shadow-lg); background-color:var(--bg-app); color:var(--text-main);">
  <h3 style="margin-top:0; margin-bottom:15px;"><?= __('title_delete') ?></h3>
  <p id="delete-modal-msg" style="margin-bottom: 20px;"></p>
  <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('delete-modal').close()"><?= __('cancel') ?></button>
    <button type="button" id="confirm-delete-btn" class="btn btn-danger"><?= __('delete') ?></button>
  </div>
</dialog>

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
  <select onchange="window.location.href='?action=list&type='+this.value" style="width:auto; padding:0.25rem 0.65rem; border-radius:6px; border:1px solid var(--border); background:var(--bg-card); color:var(--text-main); font-size:0.85rem; font-weight:normal; cursor:pointer; outline:none; transition:var(--transition);">
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
    <?php if (!empty($prefix)):
      $backParts = explode('/', rtrim($prefix, '/'));
      array_pop($backParts);
      $parent = count($backParts) > 0 ? implode('/', $backParts) . '/' : '';
    ?>
      <a href="?action=list&type=<?= urlencode((string)$type) ?>&prefix=<?= urlencode($parent) ?>" class="btn btn-secondary">
        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        <?= __('back') ?>
      </a>
    <?php else: ?>
      <a href="/?action=upload" class="btn btn-secondary">
        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        <?= __('btn_back_to_upload') ?>
      </a>
    <?php endif; ?>
  </div>

  <!-- Search bar -->
  <form method="GET" action="/" class="form-group" style="margin-bottom:1.5rem; display:flex; gap:0.5rem; max-width:100%;">
    <input type="hidden" name="action" value="list">
    <input type="hidden" name="type" value="<?= htmlspecialchars((string)$type) ?>">
    <?php if (!empty($prefix)): ?>
      <input type="hidden" name="prefix" value="<?= htmlspecialchars($prefix) ?>">
    <?php endif; ?>
    <input type="text" name="q" id="file-search-input" placeholder="<?= __('search_placeholder') ?>" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" style="flex:1; max-width: 400px;">
    <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem;"><?= __('btn_search') ?></button>
  </form>

  <!-- Folders -->
  <?php if (count($prefixes) > 0): ?>
    <h3 style="font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem; font-weight:600; letter-spacing:0.05em;">📁 <?= __('folder') ?></h3>
    <div class="folder-grid">
      <?php foreach ($prefixes as $folder):
        $folderDisplay = str_replace($prefix, '', $folder);
      ?>
        <a class="folder-item" href="?action=list&type=<?= urlencode((string)$type) ?>&prefix=<?= urlencode($folder) ?>">
          <span class="folder-icon">
            <svg style="width:1.25rem;height:1.25rem;" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"></path></svg>
          </span>
          <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($folderDisplay) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Files -->
  <?php if (count($objects) > 0):
    $totalFiles = count($objects);
  ?>
    <h3 style="font-size:0.85rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.5rem; font-weight:600; letter-spacing:0.05em; margin-top:1.5rem;">📄 <?= __('files') ?></h3>



    <div class="pagination-bar" id="pagination-bar">
      <span class="pagination-info" id="pagination-info"></span>
      <label class="per-page-select">
        <?= __('per_page') ?>
        <select id="per-page-select" onchange="changePerPage(this.value)">
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </label>
    </div>

    <div id="file-table-container" style="overflow-x:auto;">
      <table id="file-table">
        <thead>
          <tr>
            <th><?= __('file_name') ?></th>
            <th><?= __('size') ?></th>
            <th style="text-align:right;"><?= __('action') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($objects as $idx => $obj):
            $fileUrl = rtrim($publicUrl, '/') . '/' . ltrim($obj['Key'], '/');
            $displayName = str_replace($prefix, '', $obj['Key']);
            $sizeMB = number_format(($obj['Size'] ?? 0) / 1024 / 1024, 2);
          ?>
            <tr class="file-row" data-index="<?= $idx ?>">
              <td>
                <div class="file-name-cell">
                  <span style="color:var(--accent);">
                    <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                  </span>
                  <a href="<?= htmlspecialchars($fileUrl) ?>" class="file-link" target="_blank" style="word-break:break-all;"><?= htmlspecialchars($displayName) ?></a>
                </div>
              </td>
              <td><span class="badge" style="background-color:var(--bg-app); border:1px solid var(--border); color:var(--text-muted);"><?= $sizeMB ?> MB</span></td>
              <td>
                <div class="actions-cell" style="justify-content:flex-end;">
                  <button class="btn btn-secondary" onclick="previewFile('<?= htmlspecialchars(addslashes($fileUrl), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($displayName) ?>')" style="padding:0.4rem 0.65rem;" title="<?= __('btn_preview') ?>">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                  </button>
                  <button class="btn btn-secondary" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($fileUrl), ENT_QUOTES, 'UTF-8') ?>')" style="padding:0.4rem 0.65rem;" title="<?= __('btn_copy_url') ?>">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                  </button>
                  <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank" class="btn btn-secondary" style="padding:0.4rem 0.65rem;" title="<?= __('btn_download') ?>">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                  </a>
                  <button class="btn btn-warning" onclick="renameFile('<?= htmlspecialchars(addslashes($obj['Key']), ENT_QUOTES, 'UTF-8') ?>','<?= htmlspecialchars((string)$type, ENT_QUOTES, 'UTF-8') ?>')" style="padding:0.4rem 0.65rem;" title="<?= __('btn_rename') ?>">
                    <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                  </button>
                  <form method="POST" action="/?action=delete&type=<?= urlencode((string)$type) ?>&key=<?= urlencode($obj['Key']) ?>" style="display:inline;" onsubmit="return confirm('<?= __('confirm_delete_file', ['file' => htmlspecialchars(addslashes($obj['Key']), ENT_QUOTES, 'UTF-8')]) ?>');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button class="btn btn-danger" style="padding:0.4rem 0.65rem;" title="<?= __('delete') ?>">
                      <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p id="search-empty-state" style="display:none; text-align:center; color:var(--text-muted); padding:2rem 0; font-style:italic;"><?= __('no_files_found') ?></p>

    <div class="pagination-controls" id="pagination-controls"></div>

  <?php else: ?>
    <?php if (!empty($_GET['q'])): ?>
      <p style="text-align:center; color:var(--text-muted); padding:2rem 0; font-style:italic;"><?= __('no_files_found') ?></p>
    <?php else: ?>
      <p style="text-align:center; color:var(--text-muted); padding:2rem 0; font-style:italic;"><?= __('no_files_in_folder') ?></p>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php if ($isTruncated && !empty($nextToken)):
  $nextHref = '?action=list&type=' . urlencode((string)$type)
    . '&prefix=' . urlencode($prefix)
    . '&ct=' . urlencode($nextToken);
?>
<div style="display:flex; justify-content:center; align-items:center; gap:0.75rem; margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border);">
  <svg style="width:1.15rem;height:1.15rem;color:var(--text-muted);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
  <span style="font-size:0.85rem; color:var(--text-muted);"><?= __('more_than_1000_files') ?></span>
  <a class="btn btn-secondary" href="<?= htmlspecialchars($nextHref) ?>"><?= __('btn_load_more') ?></a>
</div>
<?php endif; ?>

<!-- Preview Modal -->
<dialog id="preview-modal" class="card" style="width:90%; max-width:800px; padding:20px; border:1px solid var(--border); box-shadow:var(--shadow-lg); background-color:var(--bg-app); color:var(--text-main);">
  <form method="dialog">
    <button type="submit" class="close-modal" style="background:none; border:none; color:var(--text-muted); float:right; font-size:28px; font-weight:bold; cursor:pointer; align-self:flex-end;">&times;</button>
  </form>
  <h3 id="preview-title" style="margin-top:0; margin-bottom:15px; word-break:break-all;"></h3>
  <div id="preview-container" style="display:flex; justify-content:center; align-items:center; min-height:200px; max-height:60vh; overflow:hidden;">
    <!-- Content injected by JS -->
  </div>
  <div style="margin-top:15px; text-align:center;">
    <a id="preview-download-btn" href="#" target="_blank" class="btn btn-primary"><?= __('btn_download_file') ?></a>
  </div>
</dialog>

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

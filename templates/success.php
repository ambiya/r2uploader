<div class="card success-card">
  <div class="success-icon">
    <svg style="width:2rem;height:2rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
  </div>
  <h2 style="font-size:1.5rem; margin-bottom:0.5rem;"><?= __('upload_success_title') ?></h2>
  <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;"><?= __('upload_success_desc', ['count' => count($successFiles)]) ?></p>

  <div style="text-align: left; margin-bottom: 1.5rem;">
    <?php foreach ($successFiles as $file): ?>
    <div style="background: var(--bg-body); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border); margin-bottom: 0.75rem;">
      <div class="success-link-box" style="margin-bottom: 0.5rem; padding: 0.75rem;">
        <span class="success-link-text" style="font-size:0.9rem;"><?= htmlspecialchars($file['publicUrl']) ?></span>
        <button class="btn btn-secondary" onclick="copyToClipboard('<?= htmlspecialchars(addslashes($file['publicUrl']), ENT_QUOTES, 'UTF-8') ?>')" style="padding:0.3rem 0.6rem; font-size:0.75rem;">
          <?= __('btn_copy_url') ?>
        </button>
      </div>
      <p style="font-size:0.85rem; color:var(--text-muted); margin:0;"><?= __('file_size') ?> <b><?= htmlspecialchars($file['fileSizeMB']) ?></b></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:flex; justify-content:center; gap:1rem;">
    <a href="/" class="btn btn-primary"><?= __('btn_back_home') ?></a>
    <a href="/?action=list&type=<?= htmlspecialchars((string)$type) ?>" class="btn btn-secondary"><?= __('btn_view_files') ?></a>
  </div>
</div>

<?php if (!empty($pruneDeletedKeys)): ?>
<div class="card" style="margin-top:1rem; border-color:var(--warning); background-color:var(--warning-soft);">
  <h3 style="margin-bottom:0.5rem; font-size:1rem;"><?= __('auto_retention_title') ?></h3>
  <p style="margin-bottom:0.75rem; color:var(--text-muted); font-size:0.9rem;">
    <?= __('auto_retention_desc', ['deletedCount' => count($pruneDeletedKeys), 'maxFiles' => (int)$folderMaxFiles]) ?>
  </p>
  <details>
    <summary style="cursor:pointer; font-weight:600; font-size:0.9rem;"><?= __('view_deleted_files') ?></summary>
    <ul style="margin-top:0.75rem; padding-left:1.25rem; color:var(--text-muted); font-size:0.9rem;">
      <?php foreach ($pruneDeletedKeys as $deletedKey): ?>
        <li><?= htmlspecialchars($deletedKey) ?></li>
      <?php endforeach; ?>
    </ul>
  </details>
</div>
<?php endif; ?>

<div class="breadcrumbs">
  <a href="/">🏠 <?= __('home_title') ?></a>
  <span>/</span> <span><?= __('nav_settings') ?></span>
</div>

<div style="max-width: 800px; margin: 0 auto;">
  <h2 style="font-size:1.5rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
    <svg style="width:1.75rem;height:1.75rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
    <?= __('system_settings') ?>
  </h2>

  <?php if (!empty($error)): ?>
  <div class="card error-card error-card--config" style="padding: 1rem; margin-bottom: 1.5rem;">
    <p style="margin:0; font-size: 0.9rem;"><?= htmlspecialchars($error) ?></p>
  </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
  <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; background-color: var(--success-soft); border-color: var(--success); color: var(--success);">
    <p style="margin:0; font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($success) ?></p>
  </div>
  <?php endif; ?>

  <form method="POST" action="/?action=settings_update">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Section: General -->
    <div class="card">
      <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <?= __('general_settings') ?>
      </h3>
      
      <div class="form-group" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
        <input type="checkbox" name="app_debug" id="app_debug" value="1" <?= ($config['debug'] ?? false) ? 'checked' : '' ?> style="width: auto; cursor: pointer;">
        <label for="app_debug" style="margin-bottom: 0; cursor: pointer; font-weight: 500;"><?= __('enable_debug') ?></label>
      </div>

      <div class="form-group">
        <label for="folder_max_files"><?= __('folder_max_files') ?></label>
        <input type="number" name="folder_max_files" id="folder_max_files" min="0" max="1000" value="<?= htmlspecialchars((string)($config['folderMaxFiles'] ?? 0)) ?>" required>
        <p style="margin-top:0.4rem; font-size:0.8rem; color:var(--text-muted);">
          <?= __('folder_max_files_desc') ?>
        </p>
      </div>

      <div class="form-group">
        <label for="allowed_extensions"><?= __('allowed_extensions') ?></label>
        <input type="text" name="allowed_extensions" id="allowed_extensions" placeholder="Contoh: jpg, png, zip, apk" value="<?= htmlspecialchars(implode(', ', $config['allowedExtensions'] ?? [])) ?>">
        <p style="margin-top:0.4rem; font-size:0.8rem; color:var(--text-muted);">
          <?= __('allowed_extensions_desc') ?>
        </p>
      </div>
    </div>

    <!-- Section: Cloudflare R2 Credentials -->
    <div class="card">
      <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <?= __('r2_credentials') ?>
      </h3>

      <div class="form-group">
        <label for="r2_account_id"><?= __('r2_account_id') ?></label>
        <input type="text" name="r2_account_id" id="r2_account_id" placeholder="Account ID" value="<?= htmlspecialchars($config['accountId'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="r2_access_key_id"><?= __('r2_access_key_id') ?></label>
        <input type="text" name="r2_access_key_id" id="r2_access_key_id" placeholder="Access Key ID" value="<?= htmlspecialchars($config['accessKeyId'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="r2_secret_access_key"><?= __('r2_secret_key') ?></label>
        <input type="password" name="r2_secret_access_key" id="r2_secret_access_key" placeholder="••••••••••••••••••••••••" value="">
        <p style="margin-top:0.4rem; font-size:0.8rem; color:var(--text-muted);">
          <?= __('r2_secret_key_desc') ?>
        </p>
      </div>
    </div>

    <!-- Section: Buckets -->
    <div class="card">
      <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
        <?= __('bucket_settings') ?>
      </h3>

      <div id="buckets-container" style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
        <!-- Header (Hidden on small screens) -->
        <div class="bucket-header" style="display: grid; grid-template-columns: 1.2fr 1.8fr 2.5fr auto; gap: 1rem; font-weight: 600; font-size: 0.85rem; color: var(--text-muted); border-bottom: 1px solid var(--border-light); padding-bottom: 0.5rem;">
          <div><?= __('target_slug') ?></div>
          <div><?= __('bucket_name') ?></div>
          <div><?= __('public_domain') ?></div>
          <div style="width: 50px; text-align: center;"><?= __('action') ?></div>
        </div>

        <!-- Rows -->
        <?php
        $buckets = $config['buckets'] ?? [];
        $idx = 0;
        foreach ($buckets as $key => $bucket):
        ?>
          <div class="bucket-row" data-idx="<?= $idx ?>" style="display: grid; grid-template-columns: 1.2fr 1.8fr 2.5fr auto; gap: 1rem; align-items: center; padding-bottom: 0.75rem; border-bottom: 1px dotted var(--border-light);">
            <div>
              <input type="text" name="buckets[<?= $idx ?>][key]" placeholder="Contoh: apps" value="<?= htmlspecialchars((string)$key) ?>" required style="margin-bottom:0;" pattern="^[a-zA-Z0-9_\-]+$">
            </div>
            <div>
              <input type="text" name="buckets[<?= $idx ?>][name]" placeholder="Contoh: apps-bucket" value="<?= htmlspecialchars($bucket['name'] ?? '') ?>" required style="margin-bottom:0;">
            </div>
            <div>
              <input type="url" name="buckets[<?= $idx ?>][publicUrl]" placeholder="Contoh: https://apps.domain.com" value="<?= htmlspecialchars($bucket['publicUrl'] ?? '') ?>" required style="margin-bottom:0;">
            </div>
            <div style="text-align: center;">
              <button type="button" class="btn btn-danger" onclick="removeBucketRow(this)" style="padding: 0.4rem 0.65rem;" title="Hapus Bucket">
                <svg style="width:1.15rem;height:1.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </div>
          </div>
        <?php
          $idx++;
        endforeach;
        ?>
      </div>

      <button type="button" class="btn btn-secondary" onclick="addBucketRow()" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.9rem;">
        <svg style="width:1.15rem;height:1.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
        <?= __('btn_add_bucket') ?>
      </button>
    </div>

    <div style="margin-top: 1.5rem; margin-bottom: 3rem;">
      <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem;">
        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
        <?= __('btn_save_settings') ?>
      </button>
    </div>
  </form>
</div>

<script>
let bucketIdx = <?= $idx ?>;

function addBucketRow() {
    const container = document.getElementById('buckets-container');
    const row = document.createElement('div');
    row.className = 'bucket-row';
    row.style.display = 'grid';
    row.style.gridTemplateColumns = '1.2fr 1.8fr 2.5fr auto';
    row.style.gap = '1rem';
    row.style.alignItems = 'center';
    row.style.paddingBottom = '0.75rem';
    row.style.borderBottom = '1px dotted var(--border-light)';
    row.dataset.idx = bucketIdx;

    row.innerHTML = `
        <div>
          <input type="text" name="buckets[${bucketIdx}][key]" placeholder="Contoh: new-bucket" required style="margin-bottom:0;" pattern="^[a-zA-Z0-9_\\-]+$">
        </div>
        <div>
          <input type="text" name="buckets[${bucketIdx}][name]" placeholder="Contoh: r2-bucket-name" required style="margin-bottom:0;">
        </div>
        <div>
          <input type="url" name="buckets[${bucketIdx}][publicUrl]" placeholder="Contoh: https://domain.com" required style="margin-bottom:0;">
        </div>
        <div style="text-align: center;">
          <button type="button" class="btn btn-danger" onclick="removeBucketRow(this)" style="padding: 0.4rem 0.65rem;" title="Hapus Bucket">
            <svg style="width:1.15rem;height:1.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
          </button>
        </div>
    `;
    container.appendChild(row);
    bucketIdx++;
}

function removeBucketRow(button) {
    const row = button.closest('.bucket-row');
    if (row) {
        row.remove();
    }
}
</script>

<style>
  @media (max-width: 600px) {
    .bucket-header {
      display: none !important;
    }
    .bucket-row {
      grid-template-columns: 1fr !important;
      gap: 0.5rem !important;
      padding-bottom: 1rem !important;
    }
    .bucket-row div:last-child {
      text-align: right !important;
      margin-top: 0.25rem;
    }
  }
</style>

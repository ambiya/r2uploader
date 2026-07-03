<?php if (!empty($configError)): ?>
<div class="card error-card error-card--config">
  <h3 class="error-card-title">
    <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    <?= __('config_incomplete') ?>
  </h3>
  <p class="error-card-message"><?= htmlspecialchars($configError) ?></p>
</div>
<?php endif; ?>

<div class="card" style="max-width: 600px; margin: 0 auto 1.5rem;">
  <div class="tabs">
    <button type="button" class="tab-btn active" id="tab-remote" onclick="switchTab('remote')">
      <svg style="width:1.15rem;height:1.15rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
      <?= __('remote_upload') ?>
    </button>
    <button type="button" class="tab-btn" id="tab-manual" onclick="switchTab('manual')">
      <svg style="width:1.15rem;height:1.15rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
      <?= __('manual_upload') ?>
    </button>
  </div>

  <form method="POST" enctype="multipart/form-data" id="upload-form">
    <input type="hidden" name="mode" id="upload-mode" value="remote">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Tab: Remote -->
    <div id="content-remote" class="tab-content">
      <div class="form-group">
        <label><?= __('file_url') ?></label>
        <input type="url" name="fileUrl" id="fileUrl" placeholder="https://example.com/file.zip" required>
      </div>
    </div>

    <!-- Tab: Manual -->
    <div id="content-manual" class="tab-content" style="display: none;">
      <div class="form-group">
        <label><?= __('choose_file') ?></label>
        <div class="file-input-wrapper" id="drop-zone">
          <svg class="file-input-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
          <span class="file-input-text"><?= __('drag_drop') ?></span>
          <span class="file-input-subtext"><?= __('multi_file_support') ?></span>
          <input type="file" name="manualFile[]" id="manualFile" multiple onchange="updateFileInputText(this)">
        </div>
        <div id="selected-files-list" class="selected-files-list"></div>
      </div>
    </div>

    <div class="form-group">
      <label for="target-bucket"><?= __('target_bucket') ?></label>
      <select name="type" id="target-bucket" required>
        <?php foreach ($buckets as $key => $bucket): ?>
          <option value="<?= htmlspecialchars((string)$key) ?>">
            <?= htmlspecialchars(ucfirst((string)$key)) ?> (<?= htmlspecialchars($bucket['publicUrl'] ?: $bucket['name']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="rename-file"><?= __('rename_optional') ?></label>
      <input type="text" id="rename-file" name="filename" placeholder="Contoh: Spotify Premium">
    </div>

    <div class="form-group">
      <label for="folder-input"><?= __('folder_optional') ?></label>
      <input type="text" id="folder-input" name="folder" placeholder="Contoh: Spotify" aria-describedby="folder-note">
      <?php if (!empty($folderRetentionNote)): ?>
      <p id="folder-note" style="margin-top:0.5rem; font-size:0.85rem; color:var(--text-muted);">
        <?= htmlspecialchars($folderRetentionNote) ?>
      </p>
      <?php endif; ?>
    </div>

    <button id="submit-btn" type="submit" class="btn btn-primary btn-submit"<?= !$isConfigured ? ' disabled title="Harap lengkapi konfigurasi .env terlebih dahulu"' : '' ?>>
      <svg style="width:1.15rem;height:1.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
      <span id="submit-spinner" class="spinner" style="display:none;"></span>
      <span id="submit-btn-text"><?= __('btn_upload_file') ?></span>
    </button>
    <p id="submit-helper" style="display:none; margin-top:0.75rem; font-size:0.85rem; color:var(--text-muted); text-align:center;"></p>
    
    <div id="progress-container" class="progress-container">
      <div id="progress-bar" class="progress-bar"></div>
    </div>
    <div id="progress-text" class="progress-text">0% (0 / 0 MB)</div>
  </form>
</div>


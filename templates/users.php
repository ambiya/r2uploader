<div class="breadcrumbs">
  <a href="/">🏠 <?= __('home_title') ?></a>
  <span>/</span> <span><?= __('nav_users') ?></span>
</div>

<div class="card">
  <h2 style="font-size:1.35rem; display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
    <svg style="width:1.5rem;height:1.5rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
    <?= __('user_list') ?>
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

  <div style="overflow-x:auto;">
    <table id="file-table">
      <thead>
        <tr>
          <th>ID</th>
          <th><?= __('username') ?></th>
          <th>Role</th>
          <th><?= __('created') ?></th>
          <th><?= __('last_login') ?></th>
          <th style="text-align:right;"><?= __('action') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
          <td>
            <span class="badge" style="background-color: <?= $user['role'] === 'admin' ? 'var(--warning-soft)' : 'var(--bg-app)' ?>;">
              <?= strtoupper(htmlspecialchars($user['role'])) ?>
            </span>
          </td>
          <td style="color:var(--text-muted); font-size:0.9rem;"><?= htmlspecialchars($user['created_at']) ?></td>
          <td style="color:var(--text-muted); font-size:0.9rem;"><?= htmlspecialchars($user['last_login'] ?: '-') ?></td>
          <td style="text-align:right;">
            <button class="btn btn-secondary" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['username']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($user['role']) ?>')" style="padding:0.4rem 0.65rem;" title="Edit">
              <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
            </button>
            <form method="POST" action="/?action=user_delete" style="display:inline;" onsubmit="return confirm('Hapus user <?= htmlspecialchars(addslashes($user['username']), ENT_QUOTES, 'UTF-8') ?>?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
              <button type="submit" class="btn btn-danger" style="padding:0.4rem 0.65rem;" title="Delete">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-top: 2rem;">
  <h3 id="form-title" style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 1.1rem;">
    <svg style="width:1.25rem;height:1.25rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
    <?= __('add_new_user') ?>
  </h3>

  <form id="user-form" method="POST" action="/?action=user_create">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="user_id" id="user_id" value="">
    
    <div class="form-group">
      <label><?= __('username') ?></label>
      <input type="text" name="username" id="form-username" required>
    </div>
    
    <div class="form-group">
      <label><?= __('password') ?> <span id="pwd-help" style="display:none; font-weight:normal; font-size:0.85rem; color:var(--text-muted);"><?= __('pwd_help') ?></span></label>
      <input type="password" name="password" id="form-password" required>
    </div>
    
    <div class="form-group">
      <label>Role</label>
      <select name="role" id="form-role" required>
        <option value="editor"><?= __('role_editor') ?></option>
        <option value="admin"><?= __('role_admin') ?></option>
      </select>
    </div>
    
    <div style="display:flex; gap: 1rem; margin-top: 1rem;">
      <button type="submit" id="btn-submit" class="btn btn-primary"><?= __('btn_save_user') ?></button>
      <button type="button" id="btn-cancel" class="btn btn-secondary" style="display:none;" onclick="resetForm()"><?= __('cancel') ?></button>
    </div>
  </form>
</div>

<script>
function editUser(id, username, role) {
    document.getElementById('form-title').innerHTML = '<svg style="width:1.25rem;height:1.25rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg> <?= __('edit_user') ?>';
    document.getElementById('user-form').action = '/?action=user_update';
    document.getElementById('user_id').value = id;
    
    const unameInput = document.getElementById('form-username');
    unameInput.value = username;
    unameInput.disabled = true;
    
    document.getElementById('form-role').value = role;
    
    const pwdInput = document.getElementById('form-password');
    pwdInput.required = false;
    pwdInput.value = '';
    document.getElementById('pwd-help').style.display = 'inline';
    
    document.getElementById('btn-submit').textContent = '<?= __('btn_update_user') ?>';
    document.getElementById('btn-cancel').style.display = 'block';
    
    document.getElementById('user-form').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('form-title').innerHTML = '<svg style="width:1.25rem;height:1.25rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg> <?= __('add_new_user') ?>';
    document.getElementById('user-form').action = '/?action=user_create';
    document.getElementById('user_id').value = '';
    
    const unameInput = document.getElementById('form-username');
    unameInput.value = '';
    unameInput.disabled = false;
    
    document.getElementById('form-role').value = 'editor';
    
    const pwdInput = document.getElementById('form-password');
    pwdInput.required = true;
    pwdInput.value = '';
    document.getElementById('pwd-help').style.display = 'none';
    
    document.getElementById('btn-submit').textContent = '<?= __('btn_save_user') ?>';
    document.getElementById('btn-cancel').style.display = 'none';
}
</script>

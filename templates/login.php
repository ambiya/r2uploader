<div class="card" style="max-width: 400px; margin: 4rem auto;">
  <h2 style="text-align: center; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
    <svg style="width:1.5rem;height:1.5rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
    <?= __('login_title') ?>
  </h2>

  <?php if (!empty($error)): ?>
  <div class="card error-card error-card--config" style="padding: 1rem; margin-bottom: 1.5rem;">
    <p style="margin:0; font-size: 0.9rem;"><?= htmlspecialchars($error) ?></p>
  </div>
  <?php endif; ?>

  <form method="POST" action="/?action=login">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    
    <div class="form-group">
      <label for="username"><?= __('username') ?></label>
      <input type="text" id="username" name="username" required autofocus>
    </div>
    
    <div class="form-group" style="margin-bottom: 2rem;">
      <label for="password"><?= __('password') ?></label>
      <input type="password" id="password" name="password" required>
    </div>
    
    <button type="submit" class="btn btn-primary" style="width: 100%;">
      <?= __('btn_login') ?>
      <svg style="width:1.15rem;height:1.15rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
    </button>
  </form>
</div>

<div class="card" style="text-align: center; max-width: 600px; margin: 2rem auto; padding: 3rem 2rem;">
  <h2><?= __('home_welcome') ?></h2>
  <p style="color: var(--text-muted); margin-top: 1rem; margin-bottom: 2rem;">
    <?= __('home_subtitle') ?>
  </p>
  
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="/?action=upload" class="btn btn-primary" style="text-decoration: none;"><?= __('btn_go_to_upload') ?></a>
  <?php else: ?>
    <a href="/?action=login" class="btn btn-primary" style="text-decoration: none;"><?= __('btn_login_now') ?></a>
  <?php endif; ?>
</div>

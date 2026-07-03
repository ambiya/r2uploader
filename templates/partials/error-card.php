<div class="card error-card">
  <h3><?= htmlspecialchars($title) ?></h3>
  <p><?= !empty($allowHtml) ? $message : htmlspecialchars($message) ?></p>
  <p style="margin-top:1rem;"><a href="/" class="btn btn-secondary"><?= __('btn_back_home') ?></a></p>
</div>

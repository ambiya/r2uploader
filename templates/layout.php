<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title><?= htmlspecialchars($title) ?> - R2Uploader</title>
  <!-- Favicons -->
  <link rel="icon" type="image/svg+xml" href="/img/logo.svg">
  <link rel="apple-touch-icon" href="/img/logo.svg">

  <!-- Open Graph / Facebook -->
  <meta property="og:image" content="/img/logo.svg">
  <meta property="og:image:width" content="1024">
  <meta property="og:image:height" content="1024">
  <meta property="og:image:alt" content="Cloudflare R2 Uploader">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:image" content="/img/logo.svg">
  <meta name="twitter:image:alt" content="Cloudflare R2 Uploader">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
  <script>
    const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);
  </script>
</head>
<body>
  <header>
    <a href="/" class="logo-container" style="text-decoration: none; color: inherit;">
      <div class="logo-icon" style="display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 6px; width: 1.75rem; height: 1.75rem; background: #fff;">
        <img src="/img/logo.svg" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
      </div>
      <div>
        <h1 class="title-text"><?= __('app_title') ?></h1>
      </div>
    </a>
    
    <div style="display:flex; align-items:center; gap:1rem;">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/?action=upload" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_upload') ?>">
          <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
        </a>
        <a href="/?action=list" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_file_manager') ?>">
          <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="/?action=dashboard" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_dashboard') ?>">
            <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
          </a>
          <a href="/?action=users" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_users') ?>">
            <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </a>
          <a href="/?action=settings" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_settings') ?>">
            <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
          </a>
        <?php endif; ?>
        <a href="/?action=logout" class="btn btn-secondary" style="padding:0.4rem 0.65rem; border:none; background:transparent;" title="<?= __('nav_logout') ?>">
          <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </a>
      <?php endif; ?>
      
      <?php $currentLang = \R2Uploader\Service\Translator::getInstance()?->getLocale() ?? 'id'; ?>
      <div style="display:flex; align-items:center; gap:0.5rem; margin-right:0.5rem;">
        <a href="/?action=lang&l=id" style="text-decoration:none; color:inherit; opacity: <?= $currentLang === 'id' ? '1' : '0.5' ?>; font-weight: <?= $currentLang === 'id' ? '600' : '400' ?>;">ID</a>
        <span style="opacity:0.3">|</span>
        <a href="/?action=lang&l=en" style="text-decoration:none; color:inherit; opacity: <?= $currentLang === 'en' ? '1' : '0.5' ?>; font-weight: <?= $currentLang === 'en' ? '600' : '400' ?>;">EN</a>
      </div>

      <button class="theme-toggle-btn" onclick="toggleTheme()" aria-label="Toggle Theme">
        <svg class="dark-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
        <svg class="light-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14.828 14.828a4 4 0 11-5.656-5.656 4 4 0 015.656 5.656z"></path></svg>
      </button>
    </div>
  </header>

  <main>
    <?= $contentHtml ?>
  </main>

  <div id="toast-container" popover="manual"></div>

  <script src="/js/app.js"></script>
  <?php if (!empty($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
      <script src="/js/<?= htmlspecialchars($js) ?>.js"></script>
    <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>

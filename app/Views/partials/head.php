<?php /** @var string|null $title */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($title ?? '') !== '' ? $title . ' · ' : '') ?><?= e(config('app.name', 'AIPRO Contracts')) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
<script>
  // Apply saved theme before paint to avoid a flash.
  (function(){var t=localStorage.getItem('aifin-theme');if(t&&t!=='system')document.documentElement.setAttribute('data-theme',t);})();
</script>

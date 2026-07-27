<?php $user = App\Core\Auth::user(); ?><!doctype html>
<html lang="th">
<head><?= (new App\Core\View())->partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<div style="min-height:100vh;background:var(--bg)">
  <div style="position:sticky;top:0;z-index:20;backdrop-filter:blur(14px);background:color-mix(in srgb,var(--bg) 82%,transparent);border-bottom:1px solid var(--border)">
    <div style="max-width:1080px;margin:0 auto;padding:14px 24px;display:flex;align-items:center;gap:18px">
      <a href="<?= e(url('account')) ?>" style="display:flex;align-items:center;gap:10px;color:var(--text)">
        <span class="logo"></span><span style="font-weight:700">AIPRO&thinsp;Contracts</span>
      </a>
      <div style="display:flex;gap:16px;margin-left:8px;font-size:14px">
        <a href="<?= e(url('account')) ?>" style="color:var(--muted)">สัญญาของฉัน</a>
        <a href="<?= e(url('account/ai')) ?>" style="color:var(--muted)">AI ของฉัน</a>
      </div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
        <button type="button" data-theme-toggle class="btn btn-ghost btn-sm" title="สลับธีม"><span data-theme-label></span></button>
        <span class="faint" style="font-size:13px"><?= e($user['name'] ?? '') ?></span>
        <form method="post" action="<?= e(url('logout')) ?>" style="margin:0"><?= csrf_field() ?>
          <button class="btn btn-light btn-sm" type="submit"><?= icon('logout', 15) ?>ออกจากระบบ</button>
        </form>
      </div>
    </div>
  </div>
  <div style="max-width:1080px;margin:0 auto;padding:28px 24px 80px">
    <?= (new App\Core\View())->partial('partials/flash') ?>
    <?= $content ?>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

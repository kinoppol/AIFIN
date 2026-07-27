<!doctype html>
<html lang="th">
<head><?= (new App\Core\View())->partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<div style="min-height:100vh;display:grid;place-items:center;padding:24px;background:
     radial-gradient(120% 90% at 50% -10%, color-mix(in srgb,var(--accent) 14%,transparent), transparent 60%), var(--bg)">
  <div style="width:100%;max-width:400px">
    <a href="<?= e(url('')) ?>" style="display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:22px;color:var(--text)">
      <span class="logo"></span>
      <span style="font-weight:700;font-size:17px">AIPRO&thinsp;Contracts</span>
    </a>
    <div class="card card-pad">
      <?= (new App\Core\View())->partial('partials/flash') ?>
      <?= $content ?>
    </div>
    <div style="text-align:center;margin-top:18px">
      <button type="button" data-theme-toggle class="btn btn-ghost btn-sm"><span data-theme-label>ตามระบบ</span></button>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

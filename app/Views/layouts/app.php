<?php
/**
 * Admin shell layout (sidebar + topbar). Used by every Admin controller (the
 * default layout). Expects: $title, $active (nav key), $badges, $content.
 */
$active = $active ?? '';
$badges = $badges ?? ['ext' => 0, 'redeem' => 0];
$user = App\Core\Auth::user();
$nav = function (string $key) use ($active) {
    return 'nav-item' . ($active === $key ? ' on' : '');
};
?><!doctype html>
<html lang="th">
<head><?= (new App\Core\View())->partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<div class="admin">
  <aside class="side">
    <div class="brand">
      <span class="logo" style="width:24px;height:24px"></span>
      <div><div style="color:#fff;font-weight:600;font-size:14px;line-height:1.1">AIPRO Contracts</div>
        <div style="font-size:11px;color:#7b90b3">แผงบริหารจัดการ</div></div>
    </div>
    <nav>
      <div class="group">ภาพรวม</div>
      <a class="<?= $nav('dash') ?>" href="<?= e(url('admin')) ?>">แดชบอร์ด</a>
      <div class="group">สัญญา</div>
      <a class="<?= $nav('contracts') ?>" href="<?= e(url('admin/contracts')) ?>">รายการสัญญา</a>
      <a class="<?= $nav('ext') ?>" href="<?= e(url('admin/extensions')) ?>">คำขอขยายอายุ<?php if ($badges['ext']): ?><span class="count-badge"><?= (int)$badges['ext'] ?></span><?php endif; ?></a>
      <div class="group">หน่วย &amp; สิทธิ์</div>
      <a class="<?= $nav('wallet') ?>" href="<?= e(url('admin/wallets')) ?>">คลังหน่วยลูกค้า</a>
      <a class="<?= $nav('redeem') ?>" href="<?= e(url('admin/redeem')) ?>">คำขอแลกสิทธิ์<?php if ($badges['redeem']): ?><span class="count-badge"><?= (int)$badges['redeem'] ?></span><?php endif; ?></a>
      <div class="group">การขาย</div>
      <a class="<?= $nav('pack') ?>" href="<?= e(url('admin/packages')) ?>">แพ็กเกจ &amp; โปรโมชั่น</a>
      <div class="group">ระบบ</div>
      <a class="<?= $nav('migrations') ?>" href="<?= e(url('admin/migrations')) ?>">Migrations ฐานข้อมูล</a>
    </nav>
    <div class="foot">
      <a class="btn btn-ghost btn-block" style="color:#c6d5ec;border-color:rgba(255,255,255,.16)" href="<?= e(url('')) ?>">← กลับหน้า Landing</a>
    </div>
  </aside>

  <div style="min-width:0">
    <div class="topbar">
      <div style="font-weight:600;font-size:15px"><?= e($title ?? '') ?></div>
      <div style="margin-left:auto;display:flex;align-items:center;gap:10px">
        <button type="button" data-theme-toggle class="btn btn-ghost btn-sm"><span data-theme-label>ตามระบบ</span></button>
        <div style="display:flex;align-items:center;gap:9px;padding-left:12px;border-left:1px solid var(--border)">
          <div class="avatar"><?= e(mb_substr($user['name'] ?? 'A', 0, 1)) ?></div>
          <div style="font-size:12.5px;line-height:1.25">
            <div style="font-weight:600"><?= e($user['name'] ?? '') ?></div>
            <div class="faint">ผู้ดูแลระบบ</div>
          </div>
          <form method="post" action="<?= e(url('logout')) ?>" style="margin:0"><?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm" type="submit">ออก</button>
          </form>
        </div>
      </div>
    </div>
    <div class="content">
      <?= (new App\Core\View())->partial('partials/flash') ?>
      <?= $content ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

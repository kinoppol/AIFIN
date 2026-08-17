<?php
/**
 * Sub-navigation for the customer "การตั้งค่าการใช้งาน" section.
 * @var string $tab one of: domains | emails | team
 */
$items = [['emails', 'อีเมลที่ลงทะเบียน', 'account/emails']];
if (!App\Core\Auth::isAssistant()) {
    $items[] = ['team', 'ผู้ช่วยของฉัน', 'account/team'];
}
$items[] = ['domains', 'โดเมนที่อนุญาต', 'account/domains'];
?>
<div style="margin-bottom:18px">
  <h1 style="margin:0;font-size:26px">การตั้งค่าการใช้งาน</h1>
  <div style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
    <?php foreach ($items as [$key, $label, $href]): $on = ($tab === $key); ?>
      <a href="<?= e(url($href)) ?>" class="btn btn-sm <?= $on ? 'btn-primary' : 'btn-light' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php
/** @var array $packages @var array $contracts */
$unitDays = (int) config('app.unit_days', 30);
?>
<a class="muted" style="font-size:13px" href="<?= e(url('account')) ?>">← กลับบัญชีของฉัน</a>
<h1 style="margin:10px 0 4px;font-size:26px">ซื้อหน่วย AI Pro</h1>
<p class="muted" style="margin:0 0 24px">เลือกแพ็กเกจ ระบบจะออกสัญญาอายุ 1 ปีและเติมหน่วยเข้าคลังทันที (1 M = <?= $unitDays ?> วัน)</p>

<div style="display:grid;grid-template-columns:repeat(<?= max(1, min(4, count($packages))) ?>,1fr);gap:18px" class="buy-grid">
  <?php foreach ($packages as $i => $p):
    $featured = ($i === 1);
    $total = (int)$p['units'] * (int)$p['sale_price']; ?>
    <div style="position:relative;border-radius:16px;padding:24px;box-shadow:var(--shadow);
         <?= $featured ? 'border:1px solid transparent;background:linear-gradient(160deg,var(--navy),var(--navy1));color:#eaf1ff'
                       : 'border:1px solid var(--border);background:var(--surface);color:var(--text)' ?>">
      <?php if (!empty($p['promo_label'])): ?>
        <div style="display:inline-block;font:600 11px var(--sans);background:var(--accent2);color:#03251f;border-radius:6px;padding:4px 9px;margin-bottom:12px;max-width:100%;line-height:1.45"><?= e($p['promo_label']) ?></div>
      <?php endif; ?>
      <div style="font-weight:600;font-size:16px"><?= e($p['name']) ?></div>
      <div class="mono" style="font-size:40px;font-weight:600;margin-top:14px"><?= (int)$p['units'] ?></div>
      <div style="font-size:13px;opacity:.7;margin-top:2px">หน่วย (M)<?= $p['note'] ? ' · ' . e($p['note']) : '' ?></div>
      <div style="height:1px;background:currentColor;opacity:.12;margin:18px 0"></div>
      <div style="font-size:16px;font-weight:600"><?= baht($total) ?></div>
      <div style="font-size:13px;opacity:.7;margin-top:4px"><?= baht($p['sale_price']) ?> ต่อหน่วย</div>
      <form method="post" action="<?= e(url('account/buy')) ?>" style="margin-top:20px"
            data-confirm="ทำสัญญาแพ็กเกจ <?= e($p['name']) ?> (<?= (int)$p['units'] ?> M) มูลค่า <?= e(baht($total)) ?>&#10;ระบบจะออกสัญญาอายุ 1 ปีและเติมหน่วยเข้าคลังทันที"
            data-confirm-title="ยืนยันการทำสัญญา" data-confirm-ok="ทำสัญญา">
        <?= csrf_field() ?>
        <input type="hidden" name="package_id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn-block" type="submit" style="<?= $featured ? 'background:#fff;color:#0d1c34' : 'background:var(--navy);color:#fff' ?>">ทำสัญญาแพ็กเกจนี้</button>
      </form>
    </div>
  <?php endforeach; ?>
  <?php if (!$packages): ?><div class="card card-pad muted">ยังไม่มีแพ็กเกจเปิดขาย</div><?php endif; ?>
</div>
<p class="faint" style="margin-top:18px;font-size:13px">* ตัวอย่างระบบ — การทำสัญญาจะบันทึกลงฐานข้อมูลจริง แต่ไม่มีการตัดชำระเงินจริง</p>
<style>@media(max-width:800px){.buy-grid{grid-template-columns:1fr!important}}</style>

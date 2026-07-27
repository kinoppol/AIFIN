<?php /** @var array $reqs @var int $maxExt */ ?>
<div class="page-cols">
  <div class="card card-pad muted" style="font-size:13.5px;line-height:1.7">
    นโยบาย: ขยายอายุสัญญาได้รวมไม่เกิน <b style="color:var(--text)"><?= $maxExt ?> เดือน</b> ต่อสัญญา ระบบจะบล็อกคำขอที่ทำให้เกินโควตาโดยอัตโนมัติ
  </div>
  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px" class="ext-grid">
    <?php foreach ($reqs as $x):
      $blocked = $x['status'] === 'over_quota';
      $decided = in_array($x['status'], ['approved','rejected'], true); ?>
      <div class="card card-pad">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
          <div>
            <div class="mono" style="font-size:12px;color:var(--accent)"><?= e($x['ext_no']) ?></div>
            <div style="font-weight:600;font-size:15.5px;margin-top:5px"><?= e($x['customer_name']) ?></div>
            <div class="muted" style="font-size:12.5px;margin-top:3px"><?= e($x['contract_no']) ?> · คงเหลือ <?= (int)$x['units_total'] > 0 ? units($x['units_remaining']) : (int)$x['gpu_remaining'] . ' G' ?></div>
          </div>
          <?= pill('ext', $x['status']) ?>
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:16px;font-size:12.5px">
          <div style="background:var(--sunk);border-radius:10px;padding:11px"><div class="faint" style="font-size:11.5px">ขอขยาย</div><div class="mono" style="font-weight:600;font-size:15px;margin-top:3px"><?= (int)$x['months_requested'] ?> ด.</div></div>
          <div style="background:var(--sunk);border-radius:10px;padding:11px"><div class="faint" style="font-size:11.5px">ใช้โควตาแล้ว</div><div class="mono" style="font-weight:600;font-size:15px;margin-top:3px"><?= (int)$x['months_used_before'] ?> ด.</div></div>
          <div style="background:var(--sunk);border-radius:10px;padding:11px"><div class="faint" style="font-size:11.5px">สิ้นสุดใหม่</div><div class="mono" style="font-weight:600;font-size:15px;margin-top:3px"><?= $x['new_end_date'] ? thai_date($x['new_end_date']) : '—' ?></div></div>
        </div>
        <div class="muted" style="font-size:12.5px;margin-top:14px;line-height:1.6">เหตุผล: <?= e($x['reason']) ?></div>
        <?php if (!$decided): ?>
          <div style="display:flex;gap:9px;margin-top:16px">
            <form method="post" action="<?= e(url('admin/extensions/approve')) ?>" style="flex:1" data-confirm="อนุมัติคำขอขยายอายุ <?= e($x['ext_no']) ?> (+<?= (int)$x['months_requested'] ?> เดือน) ของ <?= e($x['customer_name']) ?>?" data-confirm-title="ยืนยันการอนุมัติ" data-confirm-ok="อนุมัติ"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-primary btn-block" type="submit" <?= $blocked ? 'disabled' : '' ?>><?= icon('check', 15) ?>อนุมัติ</button></form>
            <form method="post" action="<?= e(url('admin/extensions/reject')) ?>" data-confirm="ปฏิเสธคำขอขยายอายุ <?= e($x['ext_no']) ?> ของ <?= e($x['customer_name']) ?>?" data-confirm-title="ยืนยันการปฏิเสธ" data-confirm-ok="ปฏิเสธ" data-confirm-danger><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-danger" type="submit"><?= icon('x', 15) ?>ปฏิเสธ</button></form>
          </div>
          <?php if ($blocked): ?><div class="faint" style="font-size:12px;margin-top:8px">คำขอนี้เกินโควตา <?= $maxExt ?> เดือน — อนุมัติไม่ได้</div><?php endif; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$reqs): ?><div class="card card-pad muted" style="grid-column:1/-1;text-align:center">ยังไม่มีคำขอขยายอายุสัญญา</div><?php endif; ?>
  </div>
</div>
<style>@media(max-width:900px){.ext-grid{grid-template-columns:1fr!important}}</style>

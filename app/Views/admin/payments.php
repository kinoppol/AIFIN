<?php /** @var array $queue rows of contracts awaiting verification (with payment fields) */ ?>
<div class="page-cols">
  <div class="card card-pad muted" style="font-size:13.5px;line-height:1.7">
    ตรวจสอบหลักฐานการชำระเงินจากลูกค้า — <b style="color:var(--text)">อนุมัติ</b> เพื่อเปิดใช้งานสัญญา หรือ <b style="color:var(--text)">ปฏิเสธ</b> ให้ลูกค้าแจ้งชำระใหม่
    <?php if ($queue): ?><br><b style="color:var(--warn)">รอตรวจสอบ <?= count($queue) ?> รายการ</b><?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px" class="pay-grid">
    <?php foreach ($queue as $q):
      $isPdf = $q['proof_path'] && str_ends_with(strtolower($q['proof_path']), '.pdf'); ?>
      <div class="card card-pad">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
          <div>
            <div class="mono" style="font-size:12px;color:var(--accent)"><?= e($q['contract_no']) ?></div>
            <div style="font-weight:600;font-size:15.5px;margin-top:4px"><?= e($q['customer_name']) ?></div>
            <div class="muted" style="font-size:12.5px;margin-top:2px">แจ้งเมื่อ <?= thai_date(substr($q['submitted_at'], 0, 10)) ?></div>
          </div>
          <div style="text-align:right">
            <div class="muted" style="font-size:11.5px">ยอดที่แจ้ง</div>
            <div class="mono" style="font-size:18px;font-weight:600;color:var(--accent)"><?= baht($q['pay_amount']) ?></div>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:4px 12px;margin-top:12px;font-size:12.5px">
          <span class="faint">วิธีชำระ</span><span><?= e($q['method'] ?: '—') ?></span>
          <span class="faint">อ้างอิง</span><span><?= e($q['reference'] ?: '—') ?></span>
        </div>

        <?php if ($q['proof_path']): ?>
          <a href="<?= e(url('account/proof?id=' . (int)$q['payment_id'])) ?>" target="_blank" rel="noopener" style="display:block;margin-top:12px;border:1px solid var(--border);border-radius:11px;overflow:hidden;background:var(--sunk)">
            <?php if ($isPdf): ?>
              <div style="padding:16px;text-align:center;font-size:13px" class="muted"><?= icon('download', 18) ?> เปิดหลักฐาน (PDF)</div>
            <?php else: ?>
              <img src="<?= e(url('account/proof?id=' . (int)$q['payment_id'])) ?>" alt="หลักฐาน" style="width:100%;max-height:220px;object-fit:contain;display:block;background:#0a1220">
            <?php endif; ?>
          </a>
        <?php else: ?>
          <div class="faint" style="font-size:12.5px;margin-top:12px">— ไม่มีไฟล์หลักฐานแนบ —</div>
        <?php endif; ?>

        <div style="display:flex;gap:9px;margin-top:14px">
          <form method="post" action="<?= e(url('admin/payments/approve')) ?>" style="flex:1"
                data-confirm="อนุมัติการชำระเงินของ <?= e($q['contract_no']) ?>? สัญญาจะพร้อมใช้งานทันที" data-confirm-title="ยืนยันการอนุมัติ" data-confirm-ok="อนุมัติ">
            <?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int)$q['id'] ?>">
            <button class="btn btn-primary btn-block" type="submit"><?= icon('check', 15) ?>อนุมัติ</button>
          </form>
          <form method="post" action="<?= e(url('admin/payments/reject')) ?>"
                data-confirm="ปฏิเสธการชำระเงินของ <?= e($q['contract_no']) ?>? ลูกค้าต้องแจ้งชำระใหม่" data-confirm-title="ยืนยันการปฏิเสธ" data-confirm-ok="ปฏิเสธ" data-confirm-danger>
            <?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int)$q['id'] ?>">
            <button class="btn btn-danger" type="submit"><?= icon('x', 15) ?>ปฏิเสธ</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$queue): ?><div class="card card-pad muted" style="grid-column:1/-1;text-align:center">ไม่มีรายการรอตรวจสอบการชำระเงิน</div><?php endif; ?>
  </div>
</div>
<style>@media(max-width:900px){.pay-grid{grid-template-columns:1fr!important}}</style>

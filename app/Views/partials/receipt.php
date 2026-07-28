<?php
/**
 * Brief receipt for a purchase ledger entry. Rendered hidden and shown in a
 * modal by AIFIN.openHtml (which supplies the surrounding box + close button).
 *
 * @var array $c     contract
 * @var array $l     ledger entry (purchase)
 * @var int   $total subtotal (units * price_per_m)
 */
$units = (int) $l['amount'];
$price = (int) $c['price_per_m'];
$vat = (int) round($total * 0.07);
$grand = $total + $vat;
$rno = 'RCPT-' . $c['contract_no'] . '-' . (int) $l['id'];
?>
<div style="text-align:left">
  <div class="modal-icon i-primary">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1-2-1z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
  </div>
  <h3 class="modal-title" style="margin-bottom:2px">ใบเสร็จอย่างย่อ</h3>
  <div class="mono muted" style="font-size:12px;margin-bottom:14px"><?= e($rno) ?></div>

  <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:13px;margin-bottom:14px">
    <span class="muted">ลูกค้า</span><span style="text-align:right"><?= e($c['customer_name']) ?></span>
    <span class="muted">เลขที่สัญญา</span><span class="mono" style="text-align:right"><?= e($c['contract_no']) ?></span>
    <span class="muted">วันที่</span><span style="text-align:right"><?= thai_date($l['entry_date']) ?></span>
  </div>

  <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="display:grid;grid-template-columns:1fr auto;padding:10px 14px;background:var(--sunk);font-size:12px" class="muted">
      <span>รายการ</span><span>รวม</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr auto;gap:4px 12px;padding:12px 14px;font-size:13px;border-bottom:1px solid var(--border)">
      <span><?= e($l['description']) ?><div class="muted" style="font-size:12px;margin-top:2px"><?= $units ?> M × <?= e(baht($price)) ?>/หน่วย</div></span>
      <span class="mono" style="text-align:right;align-self:start"><?= e(baht($total)) ?></span>
    </div>
    <div style="padding:12px 14px;font-size:13px;display:grid;gap:6px">
      <div style="display:flex;justify-content:space-between"><span class="muted">ยอดรวม (ก่อน VAT)</span><span class="mono"><?= e(baht($total)) ?></span></div>
      <div style="display:flex;justify-content:space-between"><span class="muted">VAT 7%</span><span class="mono"><?= e(baht($vat)) ?></span></div>
      <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:8px;font-weight:600"><span>รวมทั้งสิ้น</span><span class="mono" style="color:var(--accent)"><?= e(baht($grand)) ?></span></div>
    </div>
  </div>
  <a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="<?= e(url('account/receipt?id=' . (int)$l['id'])) ?>" style="margin-top:14px"><?= icon('download', 15) ?>พิมพ์ใบเสร็จ (A4)</a>
  <div class="faint" style="font-size:11.5px;margin-top:10px">* เอกสารสรุปอย่างย่อสำหรับอ้างอิงภายในระบบ ไม่ใช่ใบกำกับภาษี</div>
</div>

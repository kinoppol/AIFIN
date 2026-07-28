<?php
/**
 * Quotation / proforma for a contract. Rendered hidden and shown via
 * AIFIN.openHtml (which supplies the modal box + close button).
 *
 * @var array $c contract
 */
$units = (int) $c['units_total'];
$gpu   = (int) $c['gpu_total'];
$price = (int) $c['price_per_m'];
$total = (int) $c['total_amount'];
$vat   = (int) round($total * 0.07);
$grand = $total + $vat;
$qno   = 'QUO-' . $c['contract_no'];

$lines = [];
if ($units > 0) {
    $lines[] = ['name' => 'หน่วย AI Pro (M)', 'qty' => $units . ' M', 'price' => baht($price), 'amount' => baht($units * $price)];
}
if ($gpu > 0) {
    if ($units > 0) {
        $lines[] = ['name' => 'การ์ด GPU (แถมฟรี)', 'qty' => $gpu . ' การ์ด', 'price' => '—', 'amount' => baht(0)];
    } else {
        $per = $gpu > 0 ? intdiv($total, $gpu) : 0;
        $lines[] = ['name' => 'การ์ด GPU', 'qty' => $gpu . ' การ์ด', 'price' => baht($per), 'amount' => baht($total)];
    }
}
?>
<div style="text-align:left">
  <div class="modal-icon i-primary">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>
  </div>
  <h3 class="modal-title" style="margin-bottom:2px">ใบเสนอราคา</h3>
  <div class="mono muted" style="font-size:12px;margin-bottom:14px"><?= e($qno) ?></div>

  <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 14px;font-size:13px;margin-bottom:14px">
    <span class="muted">ลูกค้า</span><span style="text-align:right"><?= e($c['customer_name']) ?></span>
    <span class="muted">เลขที่สัญญา</span><span class="mono" style="text-align:right"><?= e($c['contract_no']) ?></span>
    <span class="muted">วันที่</span><span style="text-align:right"><?= thai_date(date('Y-m-d')) ?></span>
  </div>

  <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="display:grid;grid-template-columns:1fr auto auto;gap:4px 12px;padding:10px 14px;background:var(--sunk);font-size:12px" class="muted">
      <span>รายการ</span><span style="text-align:right">ราคา/หน่วย</span><span style="text-align:right">รวม</span>
    </div>
    <?php foreach ($lines as $l): ?>
      <div style="display:grid;grid-template-columns:1fr auto auto;gap:4px 12px;padding:11px 14px;font-size:13px;border-bottom:1px solid var(--border)">
        <span><?= e($l['name']) ?><div class="muted" style="font-size:12px;margin-top:2px"><?= e($l['qty']) ?></div></span>
        <span class="mono" style="text-align:right;align-self:start"><?= $l['price'] ?></span>
        <span class="mono" style="text-align:right;align-self:start"><?= $l['amount'] ?></span>
      </div>
    <?php endforeach; ?>
    <div style="padding:12px 14px;font-size:13px;display:grid;gap:6px">
      <div style="display:flex;justify-content:space-between"><span class="muted">ยอดรวม (ก่อน VAT)</span><span class="mono"><?= e(baht($total)) ?></span></div>
      <div style="display:flex;justify-content:space-between"><span class="muted">VAT 7%</span><span class="mono"><?= e(baht($vat)) ?></span></div>
      <div style="display:flex;justify-content:space-between;border-top:1px solid var(--border);padding-top:8px;font-weight:600"><span>ยอดชำระทั้งสิ้น</span><span class="mono" style="color:var(--accent)"><?= e(baht($grand)) ?></span></div>
    </div>
  </div>

  <div style="margin-top:14px;border:1px dashed var(--border);border-radius:11px;background:var(--sunk);padding:12px 14px;font-size:12.5px;line-height:1.7">
    <b>ชำระเงินโดยโอนเข้าบัญชี</b><br>
    ธนาคารกรุงเทพ · เลขที่บัญชี 123-4-56789-0<br>
    ชื่อบัญชี บริษัท เอไอโปร คอนแทรกส์ จำกัด<br>
    <span class="faint">โอนแล้วแจ้งชำระเงินพร้อมแนบสลิปในระบบเพื่อเปิดใช้งานสัญญา</span>
  </div>
  <div class="faint" style="font-size:11.5px;margin-top:10px">* เอกสารสรุปอย่างย่อสำหรับอ้างอิงภายในระบบ ไม่ใช่ใบกำกับภาษี</div>
</div>

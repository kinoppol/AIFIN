<?php
/**
 * Standalone, print-optimised A4 quotation (no app layout).
 * @var array $c  @var bool $autoPrint
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
    $lines[] = ['name' => 'หน่วย AI Pro (M) — สิทธิ์ใช้งาน 30 วัน/หน่วย', 'qty' => number_format($units) . ' M', 'price' => $price, 'amount' => $units * $price];
}
if ($gpu > 0) {
    if ($units > 0) {
        $lines[] = ['name' => 'การ์ด GPU (ของแถม)', 'qty' => number_format($gpu) . ' การ์ด', 'price' => 0, 'amount' => 0];
    } else {
        $per = $gpu > 0 ? intdiv($total, $gpu) : 0;
        $lines[] = ['name' => 'การ์ด GPU — เช่าใช้ 30 วัน/การ์ด', 'qty' => number_format($gpu) . ' การ์ด', 'price' => $per, 'amount' => $total];
    }
}
$appName = e(config('app.name', 'AIPRO Contracts'));
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ใบเสนอราคา <?= e($qno) ?></title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; background: #f3f4f6; color: #111827; font-family: "IBM Plex Sans Thai", system-ui, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .mono { font-family: "JetBrains Mono", monospace; }
  .toolbar { position: sticky; top: 0; display: flex; gap: 10px; justify-content: center; padding: 14px; background: #111827; }
  .btn { border: 0; border-radius: 8px; padding: 10px 18px; font: 600 14px "IBM Plex Sans Thai"; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
  .btn-primary { background: #2f6dff; color: #fff; }
  .btn-light { background: #e5e7eb; color: #111827; }
  .sheet { width: 210mm; min-height: 297mm; margin: 18px auto; background: #fff; padding: 18mm 16mm; box-shadow: 0 6px 30px rgba(0,0,0,.15); }
  .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #12294b; padding-bottom: 16px; }
  .brand { display: flex; align-items: center; gap: 12px; }
  .logo { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(140deg,#2f6dff,#00b39a); }
  .company { font-weight: 700; font-size: 18px; color: #12294b; }
  .company small { display: block; font-weight: 400; font-size: 11.5px; color: #6b7280; margin-top: 2px; }
  .doc-title { text-align: right; }
  .doc-title h1 { margin: 0; font-size: 26px; letter-spacing: .04em; color: #12294b; }
  .doc-title .sub { font-size: 12px; color: #6b7280; letter-spacing: .2em; }
  .meta { display: flex; justify-content: space-between; gap: 24px; margin-top: 20px; font-size: 13px; }
  .meta .box { flex: 1; }
  .meta .label { color: #6b7280; font-size: 11.5px; margin-bottom: 3px; }
  table { width: 100%; border-collapse: collapse; margin-top: 22px; font-size: 13px; }
  thead th { background: #12294b; color: #fff; text-align: left; padding: 10px 12px; font-weight: 500; }
  thead th.r, tbody td.r { text-align: right; }
  tbody td { padding: 11px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  .totals { margin-top: 14px; margin-left: auto; width: 280px; font-size: 13px; }
  .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
  .totals .grand { border-top: 2px solid #12294b; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 15px; color: #12294b; }
  .terms { margin-top: 26px; display: flex; gap: 24px; font-size: 12.5px; }
  .terms .card { flex: 1; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; line-height: 1.7; }
  .terms h3 { margin: 0 0 6px; font-size: 13px; color: #12294b; }
  .sign { display: flex; justify-content: space-between; gap: 40px; margin-top: 48px; font-size: 12.5px; color: #374151; }
  .sign .col { flex: 1; text-align: center; }
  .sign .line { border-top: 1px dotted #9ca3af; margin: 40px 12px 8px; }
  .foot { margin-top: 24px; text-align: center; font-size: 11px; color: #9ca3af; }
  @media print {
    html, body { background: #fff; }
    .toolbar { display: none; }
    .sheet { margin: 0; width: auto; min-height: auto; box-shadow: none; padding: 0; }
    @page { size: A4; margin: 14mm; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <button class="btn btn-primary" onclick="window.print()">🖨 พิมพ์ / บันทึกเป็น PDF</button>
  <a class="btn btn-light" href="<?= e(url('account/contract?id=' . $c['id'])) ?>">← กลับ</a>
</div>

<div class="sheet">
  <div class="head">
    <div class="brand">
      <div class="logo"></div>
      <div class="company"><?= $appName ?><small>ระบบสัญญาซื้อขายสิทธิ์ AI ล่วงหน้า · โทร 02-000-0000 · sales@aipro.local</small></div>
    </div>
    <div class="doc-title">
      <div class="sub">QUOTATION</div>
      <h1>ใบเสนอราคา</h1>
    </div>
  </div>

  <div class="meta">
    <div class="box">
      <div class="label">เสนอราคาให้ (Bill To)</div>
      <div style="font-weight:600;font-size:15px"><?= e($c['customer_name']) ?></div>
    </div>
    <div class="box" style="text-align:right">
      <div class="label">เลขที่ใบเสนอราคา</div>
      <div class="mono" style="font-weight:600"><?= e($qno) ?></div>
      <div class="label" style="margin-top:8px">วันที่ / เลขที่สัญญา</div>
      <div><?= thai_date(date('Y-m-d')) ?> · <span class="mono"><?= e($c['contract_no']) ?></span></div>
    </div>
  </div>

  <table>
    <thead><tr><th style="width:34px">#</th><th>รายการ</th><th class="r" style="width:110px">จำนวน</th><th class="r" style="width:110px">ราคา/หน่วย</th><th class="r" style="width:120px">จำนวนเงิน</th></tr></thead>
    <tbody>
      <?php foreach ($lines as $i => $l): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($l['name']) ?></td>
          <td class="r"><?= e($l['qty']) ?></td>
          <td class="r mono"><?= $l['price'] > 0 ? e(baht($l['price'])) : '—' ?></td>
          <td class="r mono"><?= e(baht($l['amount'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>ยอดรวม (ก่อน VAT)</span><span class="mono"><?= e(baht($total)) ?></span></div>
    <div><span>ภาษีมูลค่าเพิ่ม 7%</span><span class="mono"><?= e(baht($vat)) ?></span></div>
    <div class="grand"><span>ยอดชำระทั้งสิ้น</span><span class="mono"><?= e(baht($grand)) ?></span></div>
  </div>

  <div class="terms">
    <div class="card">
      <h3>ชำระเงินโดยโอนเข้าบัญชี</h3>
      ธนาคารกรุงเทพ · เลขที่บัญชี 123-4-56789-0<br>
      ชื่อบัญชี บริษัท เอไอโปร คอนแทรกส์ จำกัด<br>
      โอนแล้วแจ้งชำระเงินพร้อมแนบสลิปในระบบเพื่อเปิดใช้งานสัญญา
    </div>
    <div class="card">
      <h3>เงื่อนไข</h3>
      • ใบเสนอราคามีอายุ 7 วันนับจากวันที่ออก<br>
      • สัญญาอายุ 1 ปี · 1 หน่วย (M) = สิทธิ์ AI Pro 30 วัน<br>
      • ราคานี้ยังไม่รวมส่วนลดพิเศษอื่น ๆ (ถ้ามี)
    </div>
  </div>

  <div class="sign">
    <div class="col"><div class="line"></div>ผู้เสนอราคา / วันที่</div>
    <div class="col"><div class="line"></div>ผู้อนุมัติสั่งซื้อ / วันที่</div>
  </div>

  <div class="foot">เอกสารนี้จัดทำโดยระบบ <?= $appName ?> · ใช้สำหรับอ้างอิงภายใน ไม่ใช่ใบกำกับภาษี</div>
</div>

<?php if (!empty($autoPrint)): ?>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
<?php endif; ?>
</body>
</html>

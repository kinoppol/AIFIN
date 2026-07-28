<?php
/**
 * Standalone, print-optimised brief receipt (no app layout).
 * @var array $c  @var array $l (purchase ledger entry)  @var int $total  @var bool $autoPrint
 */
$units = (int) $l['amount'];
$price = (int) $c['price_per_m'];
$vat   = (int) round($total * 0.07);
$grand = $total + $vat;
$rno   = 'RCPT-' . $c['contract_no'] . '-' . (int) $l['id'];
$appName = e(config('app.name', 'AIPRO Contracts'));
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ใบเสร็จอย่างย่อ <?= e($rno) ?></title>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; }
  html, body { margin: 0; background: #f3f4f6; color: #111827; font-family: "IBM Plex Sans Thai", system-ui, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .mono { font-family: "JetBrains Mono", monospace; }
  .toolbar { position: sticky; top: 0; display: flex; gap: 10px; justify-content: center; padding: 14px; background: #111827; }
  .btn { border: 0; border-radius: 8px; padding: 10px 18px; font: 600 14px "IBM Plex Sans Thai"; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
  .btn-primary { background: #2f6dff; color: #fff; }
  .btn-light { background: #e5e7eb; color: #111827; }
  .sheet { width: 210mm; min-height: 148mm; margin: 18px auto; background: #fff; padding: 16mm; box-shadow: 0 6px 30px rgba(0,0,0,.15); }
  .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #12294b; padding-bottom: 14px; }
  .brand { display: flex; align-items: center; gap: 12px; }
  .logo { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(140deg,#2f6dff,#00b39a); }
  .company { font-weight: 700; font-size: 17px; color: #12294b; }
  .company small { display: block; font-weight: 400; font-size: 11px; color: #6b7280; margin-top: 2px; }
  .doc-title { text-align: right; }
  .doc-title h1 { margin: 0; font-size: 22px; color: #12294b; }
  .doc-title .sub { font-size: 11px; color: #6b7280; letter-spacing: .2em; }
  .meta { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; margin-top: 18px; font-size: 13px; max-width: 460px; }
  .meta .label { color: #6b7280; }
  table { width: 100%; border-collapse: collapse; margin-top: 18px; font-size: 13px; }
  thead th { background: #12294b; color: #fff; text-align: left; padding: 9px 12px; font-weight: 500; }
  th.r, td.r { text-align: right; }
  tbody td { padding: 11px 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
  .totals { margin-top: 12px; margin-left: auto; width: 280px; font-size: 13px; }
  .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
  .totals .grand { border-top: 2px solid #12294b; margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 15px; color: #12294b; }
  .foot { margin-top: 26px; font-size: 11px; color: #9ca3af; }
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
      <div class="company"><?= $appName ?><small>ระบบสัญญาซื้อขายสิทธิ์ AI ล่วงหน้า</small></div>
    </div>
    <div class="doc-title"><div class="sub">RECEIPT</div><h1>ใบเสร็จอย่างย่อ</h1></div>
  </div>

  <div class="meta">
    <span class="label">เลขที่</span><span class="mono"><?= e($rno) ?></span>
    <span class="label">ลูกค้า</span><span><?= e($c['customer_name']) ?></span>
    <span class="label">เลขที่สัญญา</span><span class="mono"><?= e($c['contract_no']) ?></span>
    <span class="label">วันที่</span><span><?= thai_date($l['entry_date']) ?></span>
  </div>

  <table>
    <thead><tr><th>รายการ</th><th class="r" style="width:110px">ราคา/หน่วย</th><th class="r" style="width:130px">จำนวนเงิน</th></tr></thead>
    <tbody>
      <tr>
        <td><?= e($l['description']) ?><div style="color:#6b7280;font-size:12px;margin-top:2px"><?= number_format($units) ?> M × <?= e(baht($price)) ?>/หน่วย</div></td>
        <td class="r mono"><?= e(baht($price)) ?></td>
        <td class="r mono"><?= e(baht($total)) ?></td>
      </tr>
    </tbody>
  </table>

  <div class="totals">
    <div><span>ยอดรวม (ก่อน VAT)</span><span class="mono"><?= e(baht($total)) ?></span></div>
    <div><span>ภาษีมูลค่าเพิ่ม 7%</span><span class="mono"><?= e(baht($vat)) ?></span></div>
    <div class="grand"><span>รวมทั้งสิ้น</span><span class="mono"><?= e(baht($grand)) ?></span></div>
  </div>

  <div class="foot">เอกสารสรุปอย่างย่อสำหรับอ้างอิงภายในระบบ ไม่ใช่ใบกำกับภาษี · จัดทำโดยระบบ <?= $appName ?></div>
</div>

<?php if (!empty($autoPrint)): ?>
<script>window.addEventListener('load', function(){ setTimeout(function(){ window.print(); }, 300); });</script>
<?php endif; ?>
</body>
</html>

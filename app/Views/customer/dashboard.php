<?php
/** @var array $contracts @var array $packages */
$totalRemaining = array_sum(array_map(fn($c) => (int)$c['units_remaining'], $contracts));
$totalBought = array_sum(array_map(fn($c) => (int)$c['units_total'], $contracts));
$totalGpu = array_sum(array_map(fn($c) => (int)($c['gpu_remaining'] ?? 0), $contracts));
?>
<div style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px">
  <div>
    <h1 style="margin:0;font-size:26px">สัญญาของฉัน</h1>
    <p class="muted" style="margin:6px 0 0">จัดการสัญญาและคลังหน่วย AI Pro ของคุณ</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('account/buy')) ?>"><?= icon('cart') ?>ซื้อสัญญาเพิ่ม</a>
</div>

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px" class="c-kpi">
  <div class="card card-pad"><div class="muted" style="font-size:12.5px">หน่วย AI คงเหลือ</div><div class="big-num" style="margin-top:8px;color:var(--accent)"><?= units($totalRemaining) ?></div></div>
  <div class="card card-pad"><div class="muted" style="font-size:12.5px">การ์ด GPU คงเหลือ</div><div class="big-num" style="margin-top:8px;color:var(--accent2)"><?= number_format($totalGpu) ?> <span style="font-size:16px">G</span></div></div>
  <div class="card card-pad"><div class="muted" style="font-size:12.5px">ซื้อ AI สะสม</div><div class="big-num" style="margin-top:8px"><?= units($totalBought) ?></div></div>
  <div class="card card-pad"><div class="muted" style="font-size:12.5px">จำนวนสัญญา</div><div class="big-num" style="margin-top:8px"><?= count($contracts) ?></div></div>
</div>

<div class="card" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600">สัญญาของฉัน</div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>เลขที่สัญญา</th><th>คงเหลือ</th><th>อายุสัญญา</th><th>สถานะ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($contracts as $c): ?>
        <tr>
          <td class="mono" style="color:var(--accent);font-weight:600;font-size:12px"><?= e($c['contract_no']) ?></td>
          <td class="mono" style="font-weight:600"><?= balance_summary($c) ?></td>
          <td class="muted" style="font-size:12.5px"><?= thai_date($c['start_date']) ?> – <?= thai_date($c['end_date']) ?></td>
          <td><?= contract_status_pill($c) ?></td>
          <td style="white-space:nowrap;text-align:right">
            <?php if (($c['payment_status'] ?? 'paid') === 'unpaid'): ?>
              <a class="btn btn-primary btn-sm" href="<?= e(url('account/contract?id=' . $c['id'] . '&pay=1')) ?>"><?= icon('send', 14) ?>แจ้งชำระเงิน</a>
            <?php endif; ?>
            <a class="btn btn-light btn-sm" href="<?= e(url('account/contract?id=' . $c['id'])) ?>">เปิดสัญญา</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$contracts): ?>
        <tr><td colspan="5" class="muted" style="text-align:center;padding:30px">คุณยังไม่มีสัญญา — <a href="<?= e(url('account/buy')) ?>">ซื้อหน่วยแรกของคุณ</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<style>@media(max-width:800px){.c-kpi{grid-template-columns:repeat(2,1fr)!important}}@media(max-width:480px){.c-kpi{grid-template-columns:1fr!important}}</style>

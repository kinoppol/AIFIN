<?php /** @var array $wallets @var int $totalRemaining @var int $totalGpuRemaining @var int $liabilityDays @var int $expiring90 */ ?>
<div class="page-cols">
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px" class="w-kpi">
    <div class="card card-pad"><div class="muted" style="font-size:12.5px">หน่วย AI คงคลังรวม</div><div class="big-num" style="margin-top:8px"><?= units($totalRemaining) ?></div></div>
    <div class="card card-pad"><div class="muted" style="font-size:12.5px">การ์ด GPU คงคลังรวม</div><div class="big-num" style="margin-top:8px;color:var(--accent2)"><?= number_format($totalGpuRemaining) ?> <span style="font-size:16px">G</span></div></div>
    <div class="card card-pad"><div class="muted" style="font-size:12.5px">ภาระผูกพัน (วันใช้งาน)</div><div class="big-num" style="margin-top:8px"><?= number_format($liabilityDays) ?></div></div>
    <div class="card card-pad"><div class="muted" style="font-size:12.5px">หน่วยที่จะหมดอายุใน 90 วัน</div><div class="big-num" style="margin-top:8px;color:var(--warn)"><?= units($expiring90) ?></div></div>
  </div>

  <div class="card" style="overflow:hidden">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>บัญชีลูกค้า</th><th>ซื้อสะสม (M)</th><th>แลกไปแล้ว (M)</th><th>คงเหลือ (M)</th><th>การ์ด GPU</th><th>สัดส่วนการใช้ M</th></tr></thead>
        <tbody>
        <?php foreach ($wallets as $w):
          $pct = (int)$w['bought'] > 0 ? round((int)$w['used'] / (int)$w['bought'] * 100) : 0; ?>
          <tr>
            <td><div><?= e($w['customer']) ?></div><div class="mono faint" style="font-size:11.5px;margin-top:2px"><?= e($w['email']) ?></div></td>
            <td class="mono" style="font-weight:600"><?= (int)$w['bought'] ? units($w['bought']) : '<span class="faint">—</span>' ?></td>
            <td class="mono muted" style="font-weight:600"><?= (int)$w['bought'] ? units($w['used']) : '<span class="faint">—</span>' ?></td>
            <td class="mono" style="font-weight:600;color:var(--accent)"><?= (int)$w['bought'] ? units($w['remaining']) : '<span class="faint">—</span>' ?></td>
            <td class="mono" style="font-weight:600;color:var(--accent2)"><?= (int)$w['gpu_bought'] ? ((int)$w['gpu_remaining'] . ' / ' . (int)$w['gpu_bought'] . ' G') : '<span class="faint">—</span>' ?></td>
            <td style="min-width:140px"><div class="progress"><span style="width:<?= $pct ?>%"></span></div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$wallets): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:26px">ยังไม่มีบัญชีที่มีหน่วย</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<style>@media(max-width:900px){.w-kpi{grid-template-columns:repeat(2,1fr)!important}}</style>

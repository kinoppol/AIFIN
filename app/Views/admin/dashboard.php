<?php
/** @var array $kpis @var array $chart @var int $chartMax @var array $expiring @var array $redeems */
$k = $kpis;
?>
<div class="page-cols">
  <div class="kpi-grid">
    <div class="kpi"><div class="label">หน่วยขายเดือนนี้</div><div class="value"><?= units($k['sold']) ?></div><div style="font-size:12px;color:var(--ok);margin-top:6px">รวมทุกสัญญาในเดือนปัจจุบัน</div></div>
    <div class="kpi"><div class="label">หน่วยที่ถูกแลกใช้</div><div class="value"><?= units($k['redeemed']) ?></div><div style="font-size:12px;color:var(--ok);margin-top:6px">แลกเป็นสิทธิ์เดือนนี้</div></div>
    <div class="kpi"><div class="label">สัญญาที่ยังใช้งาน</div><div class="value"><?= (int)$k['active'] ?></div><div style="font-size:12px;color:var(--warn);margin-top:6px"><?= (int)$k['expiring'] ?> สัญญาใกล้หมดอายุ</div></div>
    <div class="kpi"><div class="label">รายได้รับล่วงหน้า</div><div class="value"><?= baht($k['revenue']) ?></div><div class="muted" style="font-size:12px;margin-top:6px">ภาระผูกพัน <?= number_format($k['liability']) ?> วัน</div></div>
  </div>

  <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:18px" class="dash-row">
    <div class="card card-pad">
      <div style="display:flex;justify-content:space-between;align-items:baseline">
        <div style="font-weight:600;font-size:15px">หน่วยที่ขาย vs. หน่วยที่ถูกแลก</div>
        <div class="muted" style="font-size:12px;display:flex;gap:14px"><span>◼ ขาย</span><span style="color:var(--accent2)">◼ แลกใช้</span></div>
      </div>
      <div class="bars">
        <?php foreach ($chart as $c):
          $ha = round(($c['sold'] / $chartMax) * 100);
          $hb = round(($c['redeemed'] / $chartMax) * 100);
          $label = thai_date($c['ym'] . '-01');
          $label = explode(' ', $label)[1] ?? $c['ym'];
        ?>
          <div class="col">
            <div class="pair">
              <div class="bar barA" style="height:<?= max(2,$ha) ?>%" title="ขาย <?= units($c['sold']) ?>"></div>
              <div class="bar barB" style="height:<?= max(2,$hb) ?>%" title="แลก <?= units($c['redeemed']) ?>"></div>
            </div>
            <div class="faint" style="font-size:11.5px"><?= e($label) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">สัญญาใกล้หมดอายุ (90 วัน)</div>
      <div class="stack" style="gap:12px;margin-top:16px">
        <?php if (!$expiring): ?>
          <div class="muted" style="font-size:13px">ไม่มีสัญญาใกล้หมดอายุ</div>
        <?php endif; ?>
        <?php foreach ($expiring as $e):
          $daysLeft = max(0, (int) round((strtotime($e['end_date']) - time()) / 86400)); ?>
          <a href="<?= e(url('admin/contracts/show?id=' . $e['id'])) ?>" style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:11px 12px;border:1px solid var(--border);border-radius:11px;background:var(--sunk);color:inherit">
            <div style="min-width:0">
              <div class="mono" style="font-size:12px;color:var(--accent)"><?= e($e['contract_no']) ?></div>
              <div style="font-size:13px;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($e['customer_name']) ?></div>
            </div>
            <div style="text-align:right;white-space:nowrap">
              <div class="mono" style="font-size:13px;font-weight:600"><?= (int)$e['units_total'] > 0 ? units($e['units_remaining']) : (int)$e['gpu_remaining'] . ' G' ?></div>
              <div class="muted" style="font-size:11.5px">เหลือ <?= $daysLeft ?> วัน</div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card table-wrap" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <div style="font-weight:600;font-size:15px">คำขอแลกสิทธิ์ล่าสุด</div>
      <a class="btn btn-light btn-sm" href="<?= e(url('admin/redeem')) ?>">ดูทั้งหมด</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>เลขที่คำขอ</th><th>ลูกค้า</th><th>อีเมลที่ผูก</th><th>หน่วย</th><th>วันที่ขอ</th><th>สถานะ</th></tr></thead>
        <tbody>
        <?php foreach ($redeems as $r): ?>
          <tr>
            <td class="mono" style="color:var(--accent);font-weight:600;font-size:12px"><?= e($r['redeem_no']) ?></td>
            <td><?= e($r['customer_name']) ?></td>
            <td class="muted" style="font-size:12.5px"><?= e($r['email']) ?></td>
            <td class="mono" style="font-weight:600"><?= units($r['units']) ?></td>
            <td class="muted"><?= thai_date(substr($r['requested_at'], 0, 10)) ?></td>
            <td><?= pill('redeem', $r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$redeems): ?><tr><td colspan="6" class="muted" style="text-align:center">ยังไม่มีคำขอแลกสิทธิ์</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<style>@media(max-width:900px){.dash-row{grid-template-columns:1fr!important}}</style>

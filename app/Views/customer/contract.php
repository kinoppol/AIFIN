<?php
/** @var array $c @var array $ledger @var array $seats @var array $redeems @var array $exts @var int $maxExt */
$usedMonths = (int) $c['extension_months_used'];
?>
<a class="muted" style="font-size:13px" href="<?= e(url('account')) ?>">← กลับบัญชีของฉัน</a>

<div class="card-navy" style="padding:26px;margin-top:12px;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center">
  <div>
    <div class="mono" style="font-size:12px;color:#8ba1c4;letter-spacing:.1em"><?= e($c['contract_no']) ?></div>
    <div style="font-size:22px;font-weight:600;margin-top:6px"><?= e($c['customer_name']) ?></div>
    <div style="display:flex;gap:22px;margin-top:16px;font-size:13px;color:#a9bcd8;flex-wrap:wrap">
      <div><div style="color:#7b90b3;font-size:11.5px">เริ่มสัญญา</div><?= thai_date($c['start_date']) ?></div>
      <div><div style="color:#7b90b3;font-size:11.5px">สิ้นสุด</div><?= thai_date($c['end_date']) ?></div>
      <div><div style="color:#7b90b3;font-size:11.5px">ขยายแล้ว</div><?= $usedMonths ?>/<?= $maxExt ?> เดือน</div>
    </div>
  </div>
  <div style="text-align:right">
    <div class="mono" style="font-size:40px;font-weight:600"><?= units($c['units_remaining']) ?></div>
    <div style="font-size:12.5px;color:#8ba1c4">คงเหลือจาก <?= units($c['units_total']) ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-top:16px" class="cc-row">
  <div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600">ประวัติหน่วย</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>วันที่</th><th>รายการ</th><th>จำนวน</th><th style="text-align:right">คงเหลือ</th></tr></thead>
        <tbody>
        <?php foreach ($ledger as $l):
          $amt=(int)$l['amount']; $color=$amt>0?'var(--ok)':($amt<0?'var(--danger)':'var(--muted)'); ?>
          <tr>
            <td class="muted" style="font-size:12.5px"><?= thai_date($l['entry_date']) ?></td>
            <td style="font-size:13px"><?= e($l['description']) ?></td>
            <td class="mono" style="font-weight:600;color:<?= $color ?>"><?= ($amt>0?'+':'').$amt ?> M</td>
            <td class="mono muted" style="text-align:right;font-size:12.5px"><?= (int)$l['balance'] ?> M</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="stack" style="align-content:start">
    <!-- redeem -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">แลกหน่วยเป็นสิทธิ์</div>
      <form method="post" action="<?= e(url('account/redeem')) ?>" style="margin-top:14px">
        <?= csrf_field() ?>
        <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
        <div class="field"><label>อีเมลที่จะผูกสิทธิ์</label><input class="input" type="email" name="email" required></div>
        <div class="field"><label>จำนวนหน่วย (คงเหลือ <?= (int)$c['units_remaining'] ?> M)</label>
          <input class="input" type="number" name="units" min="1" max="<?= (int)$c['units_remaining'] ?>" required data-redeem-units data-unit-days="<?= (int)$c['unit_days'] ?>"></div>
        <div class="muted" style="font-size:12.5px;margin:2px 0 12px">= สิทธิ์ <b data-redeem-days style="color:var(--text)">0</b> วัน</div>
        <button class="btn btn-primary btn-block" type="submit" <?= (int)$c['units_remaining']<1?'disabled':'' ?>>ส่งคำขอแลก</button>
      </form>
    </div>

    <!-- request extension -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">ขอขยายอายุสัญญา</div>
      <div class="muted" style="font-size:12.5px;margin-top:4px">ใช้ไป <?= $usedMonths ?> จาก <?= $maxExt ?> เดือน</div>
      <?php if ($usedMonths < $maxExt): ?>
        <form method="post" action="<?= e(url('account/extend')) ?>" style="margin-top:12px">
          <?= csrf_field() ?>
          <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
          <div class="field"><label>จำนวนเดือน (สูงสุด <?= $maxExt - $usedMonths ?>)</label><input class="input" type="number" name="months" min="1" max="<?= $maxExt - $usedMonths ?>" required></div>
          <div class="field"><label>เหตุผล</label><textarea class="input" name="reason" required></textarea></div>
          <button class="btn btn-light btn-block" type="submit">ส่งคำขอขยายอายุ</button>
        </form>
      <?php else: ?>
        <div class="faint" style="font-size:12.5px;margin-top:10px">ใช้โควตาการขยายครบ <?= $maxExt ?> เดือนแล้ว</div>
      <?php endif; ?>
      <?php $openExts = array_filter($exts, fn($x)=>in_array($x['status'],['pending','reviewing'],true)); ?>
      <?php if ($openExts): ?>
        <div class="muted" style="font-size:12.5px;margin-top:12px">คำขอที่รออนุมัติ: <?= count($openExts) ?> รายการ</div>
      <?php endif; ?>
    </div>

    <!-- seats -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">สิทธิ์ที่จัดหาแล้ว</div>
      <div class="stack" style="gap:10px;margin-top:12px">
        <?php foreach ($seats as $s): ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:var(--sunk)">
            <span class="muted" style="font-size:12.5px"><?= e($s['email']) ?></span>
            <span class="mono" style="font-size:12px">ถึง <?= thai_date($s['until_date']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (!$seats): ?><div class="faint" style="font-size:12.5px">ยังไม่มีสิทธิ์ที่จัดหาสำเร็จ</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<style>@media(max-width:800px){.cc-row{grid-template-columns:1fr!important}}</style>

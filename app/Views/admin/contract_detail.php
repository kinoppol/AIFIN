<?php
/** @var array $c @var array $ledger @var array $seats @var array $exts @var int $maxExt */
$usedMonths = (int) $c['extension_months_used'];
$liabilityDays = (int) $c['units_remaining'] * (int) $c['unit_days'];
?>
<div class="page-cols">
  <a href="<?= e(url('admin/contracts')) ?>" class="muted" style="font-size:13px">← กลับรายการสัญญา</a>

  <div class="card-navy" style="padding:26px;display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center">
    <div>
      <div class="mono" style="font-size:12px;color:#8ba1c4;letter-spacing:.1em"><?= e($c['contract_no']) ?></div>
      <div style="font-size:24px;font-weight:600;margin-top:6px"><?= e($c['customer_name']) ?></div>
      <div style="display:flex;gap:26px;margin-top:18px;font-size:13px;color:#a9bcd8;flex-wrap:wrap">
        <div><div style="color:#7b90b3;font-size:11.5px">เริ่มสัญญา</div><?= thai_date($c['start_date']) ?></div>
        <div><div style="color:#7b90b3;font-size:11.5px">สิ้นสุด (เดิม)</div><?= thai_date($c['base_end_date']) ?></div>
        <div><div style="color:#7b90b3;font-size:11.5px">ขยายแล้ว</div><?= $usedMonths ?> เดือน (เหลือ <?= max(0, $maxExt - $usedMonths) ?>)</div>
        <div><div style="color:#7b90b3;font-size:11.5px">สิ้นสุดปัจจุบัน</div><?= thai_date($c['end_date']) ?></div>
      </div>
    </div>
    <div style="text-align:right">
      <?php $hasM = (int)$c['units_total'] > 0; $hasG = (int)$c['gpu_total'] > 0; ?>
      <?php if ($hasM): ?>
        <div class="mono" style="font-size:44px;font-weight:600"><?= units($c['units_remaining']) ?></div>
        <div style="font-size:12.5px;color:#8ba1c4">คงเหลือจาก <?= units($c['units_total']) ?> · มูลค่า <?= number_format($liabilityDays) ?> วันใช้งาน</div>
      <?php endif; ?>
      <?php if ($hasG): ?>
        <div class="mono" style="font-size:<?= $hasM ? '22' : '44' ?>px;font-weight:600;color:#7fe0cf;margin-top:<?= $hasM ? '8' : '0' ?>px"><?= (int)$c['gpu_remaining'] ?> / <?= (int)$c['gpu_total'] ?> G</div>
        <div style="font-size:12.5px;color:#8ba1c4">การ์ดจอคงเหลือ</div>
      <?php endif; ?>
      <?php if (!$hasM && !$hasG): ?><div class="mono" style="font-size:44px;font-weight:600">0</div><?php endif; ?>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px" class="detail-row">
    <div class="card" style="overflow:hidden">
      <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:15px">บัญชีแยกประเภทหน่วย (Unit ledger)</div>
      <div class="table-wrap">
        <?= (new App\Core\View())->partial('partials/ledger_table', ['ledger' => $ledger, 'c' => $c]) ?>
      </div>
    </div>

    <div class="stack" style="align-content:start">
      <!-- redeem on behalf of customer -->
      <div class="card card-pad">
        <div style="font-weight:600;font-size:15px">แลกหน่วยแทนลูกค้า</div>
        <form method="post" action="<?= e(url('admin/contracts/redeem')) ?>" style="margin-top:14px"
              data-confirm="ยืนยันแลกหน่วยแทนลูกค้าตามอีเมลและจำนวนที่ระบุ?&#10;อีเมลจะถูกผูกกับสิทธิ์และเปลี่ยนไม่ได้ภายหลัง" data-confirm-title="ยืนยันการแลกหน่วย" data-confirm-ok="ยืนยันการแลก">
          <?= csrf_field() ?>
          <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
          <div class="field"><label>อีเมลที่ต้องการผูกบัญชี</label><input class="input" type="email" name="email" required placeholder="user@example.com"></div>
          <?php $maxRedeem = contract_max_redeem($c); $expired = contract_is_expired($c); $cap = (int) config('app.max_redeem_units', 12); ?>
          <div class="field"><label>จำนวนหน่วยที่แลก (แลกได้สูงสุด <?= $maxRedeem ?> M ต่อครั้ง)</label>
            <input class="input" type="number" name="units" min="1" max="<?= $maxRedeem ?>" required
                   data-redeem-units data-unit-days="<?= (int)$c['unit_days'] ?>" <?= $maxRedeem<1?'disabled':'' ?>></div>
          <div class="muted" style="font-size:12.5px;margin:2px 0 2px">= สิทธิ์ AI Pro <b data-redeem-days style="color:var(--text)">0</b> วัน · เริ่มนับเมื่อจัดหาสำเร็จ</div>
          <div data-redeem-warn style="display:none;color:var(--danger);font-size:12px;margin-bottom:6px"></div>
          <button class="btn btn-primary btn-block" style="margin-top:8px" type="submit" <?= $maxRedeem<1?'disabled':'' ?>>ยืนยันการแลกและส่งเข้าคิว</button>
          <?php if ($expired): ?>
            <div class="faint" style="font-size:11.5px;margin-top:8px;color:var(--warn)">* สัญญาหมดอายุแล้ว แลกหน่วยที่เหลือไม่ได้ (สิทธิ์ที่แลกไปแล้วยังใช้ได้จนครบ)</div>
          <?php else: ?>
            <div class="faint" style="font-size:11.5px;margin-top:8px">* แลกได้ครั้งละไม่เกิน <?= $cap ?> หน่วย · สิทธิ์การใช้งานคงอยู่แม้สัญญาหมดอายุ</div>
          <?php endif; ?>
        </form>
      </div>

      <!-- extension quota -->
      <div class="card card-pad">
        <div style="font-weight:600;font-size:15px">โควตาการขยายอายุ</div>
        <div style="display:flex;gap:6px;margin-top:16px">
          <?php for ($n = 1; $n <= $maxExt; $n++): $on = $n <= $usedMonths; ?>
            <div class="mono" style="flex:1;height:36px;display:grid;place-items:center;border-radius:8px;font-size:13px;font-weight:600;background:<?= $on ? 'var(--navy)' : 'var(--surface2)' ?>;color:<?= $on ? '#fff' : 'var(--faint)' ?>"><?= $n ?></div>
          <?php endfor; ?>
        </div>
        <div class="muted" style="font-size:12.5px;margin-top:12px;line-height:1.6">ใช้ไป <?= $usedMonths ?> จาก <?= $maxExt ?> เดือน — การขยายอายุมีผลกับหน่วยคงเหลือทั้งหมดในสัญญา</div>
      </div>

      <!-- pending extension approvals for this contract -->
      <?php $pendingExts = array_filter($exts, fn($x) => in_array($x['status'], ['pending','reviewing'], true)); ?>
      <?php if ($pendingExts): ?>
        <div class="card card-pad">
          <div style="font-weight:600;font-size:15px">คำขอขยายอายุที่รออนุมัติ</div>
          <div class="stack" style="gap:10px;margin-top:12px">
            <?php foreach ($pendingExts as $x): ?>
              <div style="border:1px solid var(--border);border-radius:10px;padding:12px;background:var(--sunk)">
                <div style="display:flex;justify-content:space-between;align-items:center">
                  <span class="mono" style="font-size:12px;color:var(--accent)"><?= e($x['ext_no']) ?></span>
                  <span class="mono" style="font-size:12.5px">+<?= (int)$x['months_requested'] ?> เดือน</span>
                </div>
                <div class="muted" style="font-size:12.5px;margin:8px 0">เหตุผล: <?= e($x['reason']) ?></div>
                <div style="display:flex;gap:8px">
                  <form method="post" action="<?= e(url('admin/extensions/approve')) ?>" style="flex:1" data-confirm="อนุมัติคำขอขยายอายุ <?= e($x['ext_no']) ?> (+<?= (int)$x['months_requested'] ?> เดือน)?" data-confirm-title="ยืนยันการอนุมัติ" data-confirm-ok="อนุมัติ"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-primary btn-sm btn-block" type="submit">อนุมัติ</button></form>
                  <form method="post" action="<?= e(url('admin/extensions/reject')) ?>" data-confirm="ปฏิเสธคำขอขยายอายุ <?= e($x['ext_no']) ?>?" data-confirm-title="ยืนยันการปฏิเสธ" data-confirm-ok="ปฏิเสธ" data-confirm-danger><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$x['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">ปฏิเสธ</button></form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- bound seats -->
      <div class="card card-pad">
        <div style="font-weight:600;font-size:15px">อีเมลที่ผูกสิทธิ์แล้ว</div>
        <div class="stack" style="gap:10px;margin-top:14px">
          <?php foreach ($seats as $s): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:var(--sunk)">
              <span class="muted" style="font-size:12.5px"><?= e($s['email']) ?></span>
              <span class="mono" style="font-size:12px">ถึง <?= thai_date($s['until_date']) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if (!$seats): ?><div class="muted" style="font-size:12.5px">ยังไม่มีสิทธิ์ที่จัดหาสำเร็จ</div><?php endif; ?>
        </div>
      </div>

      <!-- GPU cards & API keys -->
      <?php if ((int)$c['gpu_total'] > 0 || $apikeys): ?>
      <div class="card card-pad">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-weight:600;font-size:15px">การ์ด GPU &amp; API Keys</div>
          <span class="mono" style="font-size:13px">เหลือ <?= (int)$c['gpu_remaining'] ?>/<?= (int)$c['gpu_total'] ?> การ์ด</span>
        </div>
        <div class="stack" style="gap:10px;margin-top:12px">
          <?php foreach ($apikeys as $k):
            $ks = ['requested'=>['pill-wait','รอจัดหา'],'provisioning'=>['pill-info','กำลังจัดหา'],'active'=>['pill-ok','ใช้งานได้'],'failed'=>['pill-bad','ล้มเหลว']][$k['status']] ?? ['pill-off',$k['status']]; ?>
            <div style="border:1px solid var(--border);border-radius:10px;background:var(--sunk);padding:10px 12px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span class="mono faint" style="font-size:12px"><?= e($k['key_no']) ?><?= $k['label'] ? ' · ' . e($k['label']) : '' ?></span>
                <span class="pill <?= $ks[0] ?>"><?= e($ks[1]) ?></span>
              </div>
              <?php if ($k['status'] === 'active'): ?>
                <div class="mono muted" style="font-size:11.5px;margin-top:6px;word-break:break-all"><?= e($k['base_url']) ?></div>
                <div class="faint" style="font-size:11px;margin-top:3px">หมดอายุ <?= $k['expires_at'] ? thai_date($k['expires_at']) : '—' ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (!$apikeys): ?><div class="muted" style="font-size:12.5px">ยังไม่มีคำขอ API Key</div><?php endif; ?>
        </div>
        <a class="btn btn-light btn-sm" style="margin-top:12px" href="<?= e(url('admin/gpu')) ?>">ไปหน้าจัดหา API Key →</a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<style>@media(max-width:900px){.detail-row{grid-template-columns:1fr!important}}</style>

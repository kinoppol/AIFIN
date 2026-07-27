<?php
/** @var array $c @var array $ledger @var array $seats @var array $redeems @var array $exts @var int $maxExt */
$usedMonths = (int) $c['extension_months_used'];
$daysLeft   = contract_days_left($c);
$extWindow  = (int) config('app.extension_window_days', 180);
$quotaLeft  = $maxExt - $usedMonths;
$openExts   = array_filter($exts, fn($x) => in_array($x['status'], ['pending', 'reviewing'], true));
// Extension can be requested only inside the renewal window and with quota left.
$canExtend  = $daysLeft < $extWindow && $quotaLeft > 0;
?>
<a class="muted" style="font-size:13px" href="<?= e(url('account')) ?>">← กลับสัญญาของฉัน</a>

<div class="card-navy" style="padding:26px;margin-top:12px;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center">
  <div>
    <div class="mono" style="font-size:12px;color:#8ba1c4;letter-spacing:.1em"><?= e($c['contract_no']) ?></div>
    <div style="font-size:22px;font-weight:600;margin-top:6px"><?= e($c['customer_name']) ?></div>
    <div style="display:flex;gap:22px;margin-top:16px;font-size:13px;color:#a9bcd8;flex-wrap:wrap;align-items:flex-start">
      <div><div style="color:#7b90b3;font-size:11.5px">เริ่มสัญญา</div><?= thai_date($c['start_date']) ?></div>
      <div>
        <div style="color:#7b90b3;font-size:11.5px">สิ้นสุด</div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <span><?= thai_date($c['end_date']) ?></span>
          <?php if ($canExtend): ?>
            <button type="button" class="btn btn-sm" style="background:rgba(255,255,255,.16);color:#eaf1ff;border:1px solid rgba(255,255,255,.22);padding:4px 10px" onclick="document.getElementById('ext-modal').showModal()"><?= icon('calendar-plus', 14) ?>ขอขยายอายุ</button>
          <?php elseif ($quotaLeft > 0 && $daysLeft >= $extWindow): ?>
            <span style="font-size:11px;color:#7b90b3">(ขอขยายได้เมื่อเหลือ &lt; <?= $extWindow ?> วัน · เหลือ <?= $daysLeft ?> วัน)</span>
          <?php endif; ?>
        </div>
      </div>
      <div><div style="color:#7b90b3;font-size:11.5px">ขยายแล้ว</div><?= $usedMonths ?>/<?= $maxExt ?> เดือน</div>
    </div>
    <?php if ($openExts): ?>
      <div style="margin-top:12px;font-size:12px;color:#e2b23c">มีคำขอขยายอายุรออนุมัติ <?= count($openExts) ?> รายการ</div>
    <?php endif; ?>
  </div>
  <div style="text-align:right">
    <?php $hasM = (int)$c['units_total'] > 0; $hasG = (int)$c['gpu_total'] > 0; ?>
    <?php if ($hasM): ?>
      <div class="mono" style="font-size:40px;font-weight:600"><?= units($c['units_remaining']) ?></div>
      <div style="font-size:12.5px;color:#8ba1c4">คงเหลือจาก <?= units($c['units_total']) ?></div>
    <?php endif; ?>
    <?php if ($hasG): ?>
      <div class="mono" style="font-size:<?= $hasM ? '22' : '40' ?>px;font-weight:600;color:#7fe0cf;margin-top:<?= $hasM ? '8' : '0' ?>px"><?= (int)$c['gpu_remaining'] ?> G</div>
      <div style="font-size:12.5px;color:#8ba1c4">การ์ดจอ คงเหลือจาก <?= (int)$c['gpu_total'] ?> ตัว</div>
    <?php endif; ?>
    <?php if (!$hasM && !$hasG): ?><div class="mono" style="font-size:40px;font-weight:600">0</div><?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-top:16px" class="cc-row">
  <div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600">ประวัติหน่วย</div>
    <div class="table-wrap">
      <?= (new App\Core\View())->partial('partials/ledger_table', ['ledger' => $ledger, 'c' => $c]) ?>
    </div>
  </div>

  <div class="stack" style="align-content:start">
    <!-- redeem -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">แลกหน่วยเป็นสิทธิ์</div>
      <form method="post" action="<?= e(url('account/redeem')) ?>" style="margin-top:14px"
            data-confirm="ยืนยันการแลกหน่วยเป็นสิทธิ์ตามจำนวนและอีเมลที่ระบุ?&#10;อีเมลจะถูกผูกกับสิทธิ์นี้และเปลี่ยนไม่ได้ภายหลัง" data-confirm-title="ยืนยันการแลกหน่วย" data-confirm-ok="ยืนยันการแลก">
        <?= csrf_field() ?>
        <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
        <div class="field"><label>อีเมลที่จะผูกสิทธิ์</label><input class="input" type="email" name="email" required></div>
        <?php $maxRedeem = contract_max_redeem($c); $expired = contract_is_expired($c); $cap = (int) config('app.max_redeem_units', 12); ?>
        <div class="field"><label>จำนวนหน่วย (แลกได้สูงสุด <?= $maxRedeem ?> M ต่อครั้ง)</label>
          <input class="input" type="number" name="units" min="1" max="<?= $maxRedeem ?>" required data-redeem-units data-unit-days="<?= (int)$c['unit_days'] ?>" <?= $maxRedeem<1?'disabled':'' ?>></div>
        <div class="muted" style="font-size:12.5px;margin:2px 0 2px">= สิทธิ์ <b data-redeem-days style="color:var(--text)">0</b> วัน · เริ่มนับเมื่อจัดหาสำเร็จ</div>
        <div data-redeem-warn style="display:none;color:var(--danger);font-size:12px;margin-bottom:6px"></div>
        <button class="btn btn-primary btn-block" style="margin-top:8px" type="submit" <?= $maxRedeem<1?'disabled':'' ?>><?= icon('redeem', 15) ?>ส่งคำขอแลก</button>
        <?php if ($expired): ?>
          <div class="faint" style="font-size:11.5px;margin-top:8px;color:var(--warn)">* สัญญาหมดอายุแล้ว แลกหน่วยที่เหลือไม่ได้ (สิทธิ์ที่แลกไปแล้วยังใช้ได้จนครบ)</div>
        <?php else: ?>
          <div class="faint" style="font-size:11.5px;margin-top:8px">* แลกได้ครั้งละไม่เกิน <?= $cap ?> หน่วย · สิทธิ์การใช้งานคงอยู่แม้สัญญาหมดอายุ</div>
        <?php endif; ?>
      </form>
    </div>

    <!-- redemptions & provisioned seats -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">คำขอแลกสิทธิ์ & สถานะ</div>
      <div class="muted" style="font-size:12px;margin-top:3px">รายการที่ "จัดหาสำเร็จ" คือสิทธิ์ที่เปิดใช้งานได้แล้ว</div>
      <div class="stack" style="gap:10px;margin-top:12px">
        <?php foreach ($redeems as $r): ?>
          <div style="padding:11px 12px;border:1px solid var(--border);border-radius:10px;background:var(--sunk)">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
              <span class="muted" style="font-size:12.5px;word-break:break-all"><?= e($r['email']) ?></span>
              <?= pill('redeem', $r['status']) ?>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:11.5px">
              <span class="mono faint"><?= e($r['redeem_no']) ?> · <?= units($r['units']) ?> · <?= (int)$r['days'] ?> วัน</span>
              <span class="mono <?= $r['status']==='success'?'':'faint' ?>">
                <?= $r['status']==='success' ? 'ถึง ' . thai_date($r['expires_at']) : 'รอจัดหา' ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$redeems): ?><div class="faint" style="font-size:12.5px">ยังไม่มีคำขอแลกสิทธิ์</div><?php endif; ?>
      </div>
    </div>

    <!-- GPU cards & API keys -->
    <?php $gpuTotal = (int) $c['gpu_total']; $gpuRemaining = (int) $c['gpu_remaining']; ?>
    <?php if ($gpuTotal > 0 || $apikeys): ?>
    <div class="card card-pad">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <div style="font-weight:600;font-size:15px">การ์ด GPU &amp; API Keys</div>
        <span class="mono" style="font-size:13px">เหลือ <?= $gpuRemaining ?>/<?= $gpuTotal ?> การ์ด</span>
      </div>
      <div class="muted" style="font-size:12px;margin-top:3px">1 การ์ด GPU (G) = 30 วันใช้งาน · เลือกจำนวนการ์ดต่อ 1 API Key ได้</div>
      <?php if ($gpuRemaining >= 1): ?>
        <form method="post" action="<?= e(url('account/apikey')) ?>" style="margin-top:12px"
              data-confirm="ยืนยันขอสร้าง API Key ตามจำนวนการ์ด GPU ที่ระบุ?" data-confirm-title="ขอสร้าง API Key" data-confirm-ok="ขอสร้าง">
          <?= csrf_field() ?>
          <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
          <div class="field"><label>ป้ายกำกับ (ไม่บังคับ)</label><input class="input" name="label" placeholder="เช่น production, dev"></div>
          <div class="field"><label>จำนวนการ์ด GPU (คงเหลือ <?= $gpuRemaining ?>)</label>
            <input class="input" type="number" name="gpu_units" min="1" max="<?= $gpuRemaining ?>" value="1" data-gpu-units data-unit-days="<?= (int)$c['unit_days'] ?>"></div>
          <div class="muted" style="font-size:12px;margin-bottom:8px">= ใช้งานได้ <b data-gpu-days style="color:var(--text)"><?= (int)$c['unit_days'] ?></b> วัน</div>
          <button class="btn btn-primary btn-block" type="submit"><?= icon('key', 15) ?>ขอสร้าง API Key</button>
        </form>
      <?php else: ?>
        <div class="faint" style="font-size:12.5px;margin-top:10px">การ์ด GPU ถูกใช้ครบแล้ว — ซื้อเพิ่มได้ที่หน้า “ซื้อหน่วย”</div>
      <?php endif; ?>
      <?php if ($apikeys): ?>
        <div class="stack" style="gap:10px;margin-top:14px">
          <?php foreach ($apikeys as $k):
            $ks = ['requested'=>['pill-wait','รอจัดหา'],'provisioning'=>['pill-info','กำลังจัดหา'],'active'=>['pill-ok','ใช้งานได้'],'failed'=>['pill-bad','ล้มเหลว']][$k['status']] ?? ['pill-off',$k['status']]; ?>
            <div style="border:1px solid var(--border);border-radius:10px;background:var(--sunk);padding:11px 12px">
              <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span class="mono faint" style="font-size:12px"><?= e($k['key_no']) ?><?= $k['label'] ? ' · ' . e($k['label']) : '' ?></span>
                <span class="pill <?= $ks[0] ?>"><?= e($ks[1]) ?></span>
              </div>
              <div class="faint" style="font-size:11px;margin-top:3px"><?= (int)($k['gpu_units'] ?? 1) ?> การ์ด · <?= (int)($k['days'] ?? 30) ?> วัน</div>
              <?php if ($k['status'] === 'active'): ?>
                <div style="margin-top:8px;display:grid;gap:6px;font-size:12px">
                  <div><span class="faint">BASE URL:</span> <span class="mono" style="word-break:break-all"><?= e($k['base_url']) ?></span></div>
                  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="faint">API Key:</span>
                    <code class="mono" data-secret style="word-break:break-all;filter:blur(4px)"><?= e($k['api_key']) ?></code>
                    <button type="button" class="btn btn-light btn-sm" data-reveal>แสดง</button>
                  </div>
                  <div><span class="faint">หมดอายุ:</span> <span class="mono"><?= $k['expires_at'] ? thai_date($k['expires_at']) : '—' ?></span></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</div>
<?php if ($canExtend): ?>
<dialog id="ext-modal" data-persistent class="card" style="border:1px solid var(--border);max-width:420px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('account/extend')) ?>" style="padding:22px"
        data-confirm="ยืนยันส่งคำขอขยายอายุสัญญาตามจำนวนเดือนที่ระบุ?" data-confirm-title="ยืนยันคำขอขยายอายุ" data-confirm-ok="ส่งคำขอ">
    <?= csrf_field() ?>
    <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
    <div class="modal-head" style="margin-bottom:4px">
      <h3 style="margin:0;font-size:17px">ขอขยายอายุสัญญา</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <p class="muted" style="margin:0 0 14px;font-size:12.5px">สัญญาเหลือ <?= $daysLeft ?> วัน · ใช้โควตาไป <?= $usedMonths ?> จาก <?= $maxExt ?> เดือน</p>
    <div class="field"><label>จำนวนเดือน (สูงสุด <?= $quotaLeft ?>)</label><input class="input" type="number" name="months" min="1" max="<?= $quotaLeft ?>" required></div>
    <div class="field"><label>เหตุผล</label><textarea class="input" name="reason" required></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button type="submit" class="btn btn-primary">ส่งคำขอ</button>
    </div>
  </form>
</dialog>
<?php endif; ?>
<style>@media(max-width:800px){.cc-row{grid-template-columns:1fr!important}}</style>

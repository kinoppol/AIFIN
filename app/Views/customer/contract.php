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
        <button class="btn btn-primary btn-block" style="margin-top:8px" type="submit" <?= $maxRedeem<1?'disabled':'' ?>>ส่งคำขอแลก</button>
        <?php if ($expired): ?>
          <div class="faint" style="font-size:11.5px;margin-top:8px;color:var(--warn)">* สัญญาหมดอายุแล้ว แลกหน่วยที่เหลือไม่ได้ (สิทธิ์ที่แลกไปแล้วยังใช้ได้จนครบ)</div>
        <?php else: ?>
          <div class="faint" style="font-size:11.5px;margin-top:8px">* แลกได้ครั้งละไม่เกิน <?= $cap ?> หน่วย · สิทธิ์การใช้งานคงอยู่แม้สัญญาหมดอายุ</div>
        <?php endif; ?>
      </form>
    </div>

    <!-- request extension -->
    <div class="card card-pad">
      <div style="font-weight:600;font-size:15px">ขอขยายอายุสัญญา</div>
      <div class="muted" style="font-size:12.5px;margin-top:4px">ใช้ไป <?= $usedMonths ?> จาก <?= $maxExt ?> เดือน</div>
      <?php if ($usedMonths < $maxExt): ?>
        <form method="post" action="<?= e(url('account/extend')) ?>" style="margin-top:12px"
              data-confirm="ยืนยันส่งคำขอขยายอายุสัญญาตามจำนวนเดือนที่ระบุ?" data-confirm-title="ยืนยันคำขอขยายอายุ" data-confirm-ok="ส่งคำขอ">
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
      <div class="muted" style="font-size:12px;margin-top:3px">1 การ์ดจอ = 1 API Key</div>
      <?php if ($gpuRemaining >= 1): ?>
        <form method="post" action="<?= e(url('account/apikey')) ?>" style="margin-top:12px"
              data-confirm="ยืนยันขอสร้าง API Key (ใช้การ์ด GPU 1 ตัว)?" data-confirm-title="ขอสร้าง API Key" data-confirm-ok="ขอสร้าง">
          <?= csrf_field() ?>
          <input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>">
          <div class="field"><label>ป้ายกำกับ (ไม่บังคับ)</label><input class="input" name="label" placeholder="เช่น production, dev"></div>
          <button class="btn btn-primary btn-block" type="submit">ขอสร้าง API Key</button>
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
              <?php if ($k['status'] === 'active'): ?>
                <div style="margin-top:8px;display:grid;gap:6px;font-size:12px">
                  <div><span class="faint">BASE URL:</span> <span class="mono" style="word-break:break-all"><?= e($k['base_url']) ?></span></div>
                  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="faint">API Key:</span>
                    <code class="mono" data-secret style="word-break:break-all;filter:blur(4px)"><?= e($k['api_key']) ?></code>
                    <button type="button" class="btn btn-light btn-sm" data-reveal>แสดง</button>
                  </div>
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
<style>@media(max-width:800px){.cc-row{grid-template-columns:1fr!important}}</style>

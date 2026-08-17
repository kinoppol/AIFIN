<?php
/** @var array $c @var array $ledger @var array $seats @var array $exts @var int $maxExt */
$usedMonths = (int) $c['extension_months_used'];
$liabilityDays = (int) $c['units_remaining'] * (int) $c['unit_days'];
?>
<div class="page-cols">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <a href="<?= e(url('admin/contracts')) ?>" class="muted" style="font-size:13px">← กลับรายการสัญญา</a>
    <button type="button" class="btn btn-light btn-sm" onclick="document.getElementById('contract-edit-modal').showModal()">แก้ไขรายละเอียดสัญญา</button>
  </div>

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

  <?php $ps = $c['payment_status'] ?? 'paid'; if ($ps !== 'paid'): ?>
    <div class="card card-pad" style="border:1px solid <?= $ps === 'unpaid' ? 'var(--warn)' : 'var(--accent)' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <div>
          <div style="font-weight:600;font-size:15px"><?= $ps === 'unpaid' ? 'ลูกค้ายังไม่แจ้งชำระเงิน' : 'รอตรวจสอบการชำระเงิน' ?></div>
          <div class="muted" style="font-size:12.5px;margin-top:3px">ยอดสัญญา <?= baht($c['total_amount']) ?> (ก่อน VAT)<?php if ($payment): ?> · แจ้งเมื่อ <?= thai_date(substr($payment['submitted_at'], 0, 10)) ?><?= $payment['method'] ? ' · ' . e($payment['method']) : '' ?><?php endif; ?></div>
        </div>
        <?php if ($ps === 'submitted' && $payment): ?>
          <div style="display:flex;gap:9px">
            <?php if ($payment['proof_path']): ?><a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="<?= e(url('account/proof?id=' . (int)$payment['id'])) ?>">ดูหลักฐาน</a><?php endif; ?>
            <form method="post" action="<?= e(url('admin/payments/approve')) ?>" data-confirm="อนุมัติการชำระเงินของ <?= e($c['contract_no']) ?>?" data-confirm-title="ยืนยัน" data-confirm-ok="อนุมัติ"><?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-primary btn-sm" type="submit"><?= icon('check', 14) ?>อนุมัติ</button></form>
            <form method="post" action="<?= e(url('admin/payments/reject')) ?>" data-confirm="ปฏิเสธการชำระเงินของ <?= e($c['contract_no']) ?>?" data-confirm-title="ยืนยัน" data-confirm-ok="ปฏิเสธ" data-confirm-danger><?= csrf_field() ?><input type="hidden" name="contract_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"><?= icon('x', 14) ?>ปฏิเสธ</button></form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

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
          <div class="field"><label>แพ็กเกจ AI ที่ต้องการ</label>
            <?php if ($plans): ?>
              <select class="input" name="plan_id" required>
                <option value="">— เลือกแพ็กเกจ AI —</option>
                <?php foreach ($plans as $p): ?>
                  <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?><?= $p['vendor'] ? ' — ' . e($p['vendor']) : '' ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <div class="muted" style="font-size:12.5px">ยังไม่มีแพ็กเกจ AI ที่เปิดใช้งาน — เพิ่มได้ที่เมนู "แพ็กเกจ AI"</div>
            <?php endif; ?>
          </div>
          <div class="field"><label>อีเมลที่ต้องการผูกบัญชี (เฉพาะอีเมลที่ลูกค้าลงทะเบียนไว้)</label>
            <?php if ($emails): ?>
              <select class="input" name="email" required>
                <option value="">— เลือกอีเมล —</option>
                <?php foreach ($emails as $m): ?>
                  <option value="<?= e($m['email']) ?>"><?= e($m['email']) ?><?= $m['label'] ? ' (' . e($m['label']) . ')' : '' ?></option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <div class="muted" style="font-size:12.5px">ลูกค้ายังไม่ได้ลงทะเบียนอีเมล — ต้องให้ลูกค้าลงทะเบียนอีเมลก่อนจึงจะแลกสิทธิ์ได้</div>
            <?php endif; ?>
          </div>
          <?php $maxRedeem = contract_max_redeem($c); $expired = contract_is_expired($c); $cap = (int) config('app.max_redeem_units', 12); ?>
          <div class="field"><label>จำนวนหน่วยที่แลก (แลกได้สูงสุด <?= $maxRedeem ?> M ต่อครั้ง)</label>
            <input class="input" type="number" name="units" min="1" max="<?= $maxRedeem ?>" required
                   data-redeem-units data-unit-days="<?= (int)$c['unit_days'] ?>" <?= $maxRedeem<1?'disabled':'' ?>></div>
          <div class="muted" style="font-size:12.5px;margin:2px 0 2px">= สิทธิ์ AI Pro <b data-redeem-days style="color:var(--text)">0</b> วัน · เริ่มนับเมื่อจัดหาสำเร็จ</div>
          <div data-redeem-warn style="display:none;color:var(--danger);font-size:12px;margin-bottom:6px"></div>
          <button class="btn btn-primary btn-block" style="margin-top:8px" type="submit" <?= ($maxRedeem<1 || !$emails || !$plans)?'disabled':'' ?>>ยืนยันการแลกและส่งเข้าคิว</button>
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
              <span class="muted" style="font-size:12.5px"><?= e($s['email']) ?><?php if (!empty($s['plan_name'])): ?><span class="faint"> · <?= e($s['plan_name']) ?></span><?php endif; ?></span>
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
<dialog id="contract-edit-modal" data-persistent class="card" style="border:1px solid var(--border);max-width:520px;width:94%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('admin/contracts/update')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    <div class="modal-head" style="margin-bottom:4px">
      <h3 style="margin:0;font-size:17px">แก้ไขรายละเอียดสัญญา</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <p class="muted" style="margin:0 0 14px;font-size:12.5px"><?= e($c['contract_no']) ?> · จำนวนหน่วยแก้ที่นี่ไม่ได้ (ควบคุมโดยบัญชีแยกประเภท) — ใช้การซื้อ/แลกหน่วยแทน</p>
    <div class="field"><label>ชื่อลูกค้า</label><input class="input" type="text" name="customer_name" required maxlength="190" value="<?= e($c['customer_name']) ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div class="field"><label>ราคาต่อหน่วย (บาท/M)</label><input class="input" type="number" name="price_per_m" min="0" value="<?= (int)$c['price_per_m'] ?>"></div>
      <div class="field"><label>ยอดสัญญา (ก่อน VAT)</label><input class="input" type="number" name="total_amount" min="0" value="<?= (int)$c['total_amount'] ?>"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div class="field"><label>วันเริ่มสัญญา</label><input class="input" type="date" name="start_date" required value="<?= e($c['start_date']) ?>"></div>
      <div class="field"><label>วันสิ้นสุด (เดิม)</label><input class="input" type="date" name="base_end_date" required value="<?= e($c['base_end_date']) ?>"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div class="field"><label>วันสิ้นสุดปัจจุบัน</label><input class="input" type="date" name="end_date" required value="<?= e($c['end_date']) ?>"></div>
      <div class="field"><label>จำกัดการแลก (M/เดือน, 0 = ไม่จำกัด)</label><input class="input" type="number" name="monthly_redeem_limit" min="0" value="<?= (int)($c['monthly_redeem_limit'] ?? 0) ?>"></div>
    </div>
    <div class="faint" style="font-size:11.5px;margin-top:6px">* วันสิ้นสุดปัจจุบันต้องไม่เกินวันสิ้นสุดเดิม + โควตาที่อนุมัติแล้ว (<?= $usedMonths ?> เดือน) · สถานะสัญญาจะคำนวณใหม่หลังบันทึก</div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit">บันทึก</button>
    </div>
  </form>
</dialog>
<style>@media(max-width:900px){.detail-row{grid-template-columns:1fr!important}}</style>

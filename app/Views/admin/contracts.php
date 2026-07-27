<?php /** @var array $contracts @var array $counts @var array $customers @var array $packages */ ?>
<div class="page-cols">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <div style="display:flex;background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;font-size:13px">
      <span style="padding:9px 14px;background:var(--navy);color:#fff">ทั้งหมด <?= $counts['all'] ?></span>
      <span class="muted" style="padding:9px 14px">ใช้งาน <?= $counts['active'] ?></span>
      <span class="muted" style="padding:9px 14px">ใกล้หมดอายุ <?= $counts['expiring'] ?></span>
      <span class="muted" style="padding:9px 14px">ขยายแล้ว <?= $counts['extended'] ?></span>
      <span class="muted" style="padding:9px 14px">หมดอายุ <?= $counts['expired'] ?></span>
    </div>
    <div style="margin-left:auto;display:flex;gap:10px">
      <button class="btn btn-primary" type="button" onclick="document.getElementById('new-contract').showModal()">+ สร้างสัญญา</button>
    </div>
  </div>

  <div class="card table-wrap" style="overflow:hidden">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>เลขที่สัญญา</th><th>ลูกค้า</th><th>หน่วยคงเหลือ</th><th>อายุสัญญา</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($contracts as $c):
          $pct = (int)$c['units_total'] > 0 ? round((int)$c['units_remaining'] / (int)$c['units_total'] * 100) : 0; ?>
          <tr>
            <td class="mono" style="color:var(--accent);font-weight:600;font-size:12px"><?= e($c['contract_no']) ?></td>
            <td><div><?= e($c['customer_name']) ?></div></td>
            <td>
              <?php if ((int)$c['units_total'] > 0): ?>
                <div class="mono" style="font-weight:600;font-size:13px"><?= (int)$c['units_remaining'] ?> / <?= (int)$c['units_total'] ?> M</div>
                <div class="progress" style="width:110px;margin-top:6px"><span style="width:<?= $pct ?>%"></span></div>
              <?php endif; ?>
              <?php if ((int)$c['gpu_total'] > 0): ?>
                <div class="mono" style="font-weight:600;font-size:13px;color:var(--accent2);margin-top:<?= (int)$c['units_total']>0?'6':'0' ?>px"><?= (int)$c['gpu_remaining'] ?> / <?= (int)$c['gpu_total'] ?> G</div>
              <?php endif; ?>
              <?php if ((int)$c['units_total']==0 && (int)$c['gpu_total']==0): ?><span class="faint">—</span><?php endif; ?>
            </td>
            <td class="muted" style="font-size:12.5px">
              <?= thai_date($c['start_date']) ?> – <?= thai_date($c['end_date']) ?>
              <div class="faint" style="font-size:11.5px;margin-top:3px">ขยายแล้ว <?= (int)$c['extension_months_used'] ?> เดือน</div>
            </td>
            <td><?= pill('contract', $c['status']) ?></td>
            <td><a class="btn btn-light btn-sm" href="<?= e(url('admin/contracts/show?id=' . $c['id'])) ?>">รายละเอียด</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$contracts): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:30px">ยังไม่มีสัญญา — กด “สร้างสัญญา” เพื่อเริ่ม</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<dialog id="new-contract" data-persistent class="card" style="border:1px solid var(--border);max-width:440px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('admin/contracts')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <div class="modal-head" style="margin-bottom:16px">
      <h3 style="margin:0;font-size:17px">สร้างสัญญาใหม่</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="field">
      <label>ลูกค้า</label>
      <select class="input" name="user_id" required>
        <option value="">— เลือกลูกค้า —</option>
        <?php foreach ($customers as $u): ?>
          <option value="<?= (int)$u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
        <?php endforeach; ?>
      </select>
      <?php if (!$customers): ?><small class="faint">ยังไม่มีบัญชีลูกค้า — ให้ลูกค้าลงทะเบียนก่อน</small><?php endif; ?>
    </div>
    <div class="field">
      <label>แพ็กเกจ (จะเติมจำนวนหน่วย/ราคาให้อัตโนมัติ)</label>
      <select class="input" name="package_id" id="pkg-select">
        <option value="">— กำหนดเอง —</option>
        <?php foreach ($packages as $p): ?>
          <option value="<?= (int)$p['id'] ?>" data-units="<?= (int)$p['units'] ?>" data-price="<?= (int)$p['sale_price'] ?>"><?= e($p['name']) ?> · <?= (int)$p['units'] ?> M @ <?= baht($p['sale_price']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex;gap:12px">
      <div class="field" style="flex:1"><label>จำนวนหน่วย (M)</label><input class="input" type="number" name="units" id="c-units" min="1" required></div>
      <div class="field" style="flex:1"><label>ราคา/หน่วย (บาท)</label><input class="input" type="number" name="price_per_m" id="c-price" min="0" required></div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">ยกเลิก</button>
      <button type="submit" class="btn btn-primary">สร้างสัญญา</button>
    </div>
  </form>
</dialog>
<script>
document.getElementById('pkg-select').addEventListener('change', function(){
  var o = this.selectedOptions[0];
  if (o && o.dataset.units){ document.getElementById('c-units').value=o.dataset.units; document.getElementById('c-price').value=o.dataset.price; }
});
</script>

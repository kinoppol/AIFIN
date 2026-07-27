<?php /** @var array $packages @var int $unitDays */ ?>
<div class="page-cols">
  <div class="card card-pad" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
    <div class="muted" style="font-size:13.5px;line-height:1.7">ปรับได้เฉพาะ “ราคาต่อหน่วย” เท่านั้น — มูลค่าการใช้งานของ 1 หน่วยถูกล็อกไว้ที่ <b style="color:var(--text)"><?= $unitDays ?> วัน</b> ในระดับระบบ</div>
    <button class="btn btn-primary" type="button" onclick="document.getElementById('new-pack').showModal()">+ สร้างแพ็กเกจ</button>
  </div>

  <div class="card" style="overflow:hidden">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>แพ็กเกจ</th><th>หน่วย</th><th>ราคาปกติ/M</th><th>ราคาขาย/M</th><th>ช่วงโปรโมชั่น</th><th>สถานะ</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($packages as $p): ?>
          <tr>
            <td><div style="font-weight:600"><?= e($p['name']) ?></div><div class="faint" style="font-size:12px;margin-top:2px"><?= e($p['note']) ?></div></td>
            <td class="mono" style="font-weight:600"><?= units($p['units']) ?></td>
            <td class="mono faint" style="text-decoration:line-through"><?= baht($p['list_price']) ?></td>
            <td class="mono" style="font-weight:600;color:var(--accent)"><?= baht($p['sale_price']) ?></td>
            <td class="muted" style="font-size:12.5px">
              <?php if ($p['window_start'] || $p['window_end']): ?>
                <?= thai_date($p['window_start']) ?> – <?= thai_date($p['window_end']) ?>
              <?php else: ?>ราคามาตรฐาน<?php endif; ?>
            </td>
            <td>
              <?php $st = ['active'=>['pill-ok','เปิดขาย'],'promo'=>['pill-info','กำลังโปรฯ'],'closed'=>['pill-off','ปิดแล้ว']][$p['status']] ?? ['pill-off',$p['status']]; ?>
              <span class="pill <?= $st[0] ?>"><?= e($st[1]) ?></span>
            </td>
            <td><button class="btn btn-light btn-sm" type="button" onclick='editPack(<?= json_encode(["id"=>(int)$p["id"],"name"=>$p["name"],"sale"=>(int)$p["sale_price"],"promo"=>$p["promo_label"],"status"=>$p["status"]], JSON_UNESCAPED_UNICODE|JSON_HEX_APOS) ?>)'>แก้ราคา</button></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$packages): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:26px">ยังไม่มีแพ็กเกจ</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- create package -->
<dialog id="new-pack" data-persistent class="card" style="border:1px solid var(--border);max-width:460px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('admin/packages')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <div class="modal-head" style="margin-bottom:16px">
      <h3 style="margin:0;font-size:17px">สร้างแพ็กเกจใหม่</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <div style="display:flex;gap:12px">
      <div class="field" style="flex:1"><label>รหัส (code)</label><input class="input" name="code" required></div>
      <div class="field" style="flex:2"><label>ชื่อแพ็กเกจ</label><input class="input" name="name" required></div>
    </div>
    <div class="field"><label>คำอธิบายสั้น</label><input class="input" name="note"></div>
    <div style="display:flex;gap:12px">
      <div class="field" style="flex:1"><label>จำนวนหน่วย (M)</label><input class="input" type="number" name="units" min="1" required></div>
      <div class="field" style="flex:1"><label>ราคาปกติ/M</label><input class="input" type="number" name="list_price" min="0" required></div>
      <div class="field" style="flex:1"><label>ราคาขาย/M</label><input class="input" type="number" name="sale_price" min="0" required></div>
    </div>
    <div style="display:flex;gap:12px">
      <div class="field" style="flex:1"><label>ป้ายโปรฯ (ถ้ามี)</label><input class="input" name="promo_label"></div>
      <div class="field" style="flex:1"><label>สถานะ</label><select class="input" name="status"><option value="active">เปิดขาย</option><option value="promo">กำลังโปรฯ</option><option value="closed">ปิด</option></select></div>
    </div>
    <div style="display:flex;gap:12px">
      <div class="field" style="flex:1"><label>เริ่มโปรฯ</label><input class="input" type="date" name="window_start"></div>
      <div class="field" style="flex:1"><label>สิ้นสุดโปรฯ</label><input class="input" type="date" name="window_end"></div>
      <div class="field" style="flex:1"><label>ลำดับ</label><input class="input" type="number" name="sort_order" value="0"></div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">ยกเลิก</button>
      <button type="submit" class="btn btn-primary">สร้างแพ็กเกจ</button>
    </div>
  </form>
</dialog>

<!-- edit price -->
<dialog id="edit-pack" data-persistent class="card" style="border:1px solid var(--border);max-width:400px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('admin/packages/update')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="ep-id">
    <div class="modal-head">
      <h3 style="margin:0 0 4px;font-size:17px">แก้ราคาแพ็กเกจ</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <p class="muted" id="ep-name" style="margin:0 0 16px;font-size:13px"></p>
    <div class="field"><label>ราคาขาย/M (บาท)</label><input class="input" type="number" name="sale_price" id="ep-sale" min="0" required></div>
    <div class="field"><label>ป้ายโปรฯ</label><input class="input" name="promo_label" id="ep-promo"></div>
    <div class="field"><label>สถานะ</label><select class="input" name="status" id="ep-status"><option value="active">เปิดขาย</option><option value="promo">กำลังโปรฯ</option><option value="closed">ปิด</option></select></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px">
      <button type="button" class="btn btn-ghost" onclick="this.closest('dialog').close()">ยกเลิก</button>
      <button type="submit" class="btn btn-primary">บันทึก</button>
    </div>
  </form>
</dialog>
<script>
function editPack(p){
  document.getElementById('ep-id').value=p.id;
  document.getElementById('ep-name').textContent=p.name;
  document.getElementById('ep-sale').value=p.sale;
  document.getElementById('ep-promo').value=p.promo||'';
  document.getElementById('ep-status').value=p.status;
  document.getElementById('edit-pack').showModal();
}
</script>

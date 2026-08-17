<?php /** @var array $plans each row plus a 'used' redemption count */ ?>
<div class="page-cols">
  <div class="card card-pad">
    <div style="font-weight:600;font-size:15px">เพิ่มแพ็กเกจ AI</div>
    <div class="muted" style="font-size:12.5px;margin-top:3px">แผนรายเดือนระดับเดียวกับ Claude Pro ที่ลูกค้าเลือกได้ตอนแลกสิทธิ์</div>
    <form method="post" action="<?= e(url('admin/plans')) ?>" style="display:grid;grid-template-columns:1.2fr .8fr 1.4fr .5fr .6fr auto;gap:10px;align-items:end;margin-top:14px" class="detail-row">
      <?= csrf_field() ?>
      <div class="field"><label>ชื่อแพ็กเกจ</label><input class="input" type="text" name="name" required maxlength="120" placeholder="เช่น Claude Pro"></div>
      <div class="field"><label>ผู้ให้บริการ</label><input class="input" type="text" name="vendor" maxlength="80" placeholder="Anthropic"></div>
      <div class="field"><label>หมายเหตุ</label><input class="input" type="text" name="note" maxlength="190" placeholder="รายละเอียดสั้น ๆ"></div>
      <div class="field"><label>ลำดับ</label><input class="input" type="number" name="sort_order" value="0"></div>
      <div class="field"><label>สถานะ</label>
        <select class="input" name="status"><option value="active">ใช้งาน</option><option value="suspended">ระงับ</option></select>
      </div>
      <button class="btn btn-primary" type="submit">เพิ่ม</button>
    </form>
  </div>

  <div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600;font-size:15px">รายการแพ็กเกจ AI (<?= count($plans) ?>)</div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>ลำดับ</th><th>ชื่อแพ็กเกจ</th><th>ผู้ให้บริการ</th><th>หมายเหตุ</th><th>สถานะ</th><th>ใช้แลกแล้ว</th><th style="text-align:right">การจัดการ</th></tr></thead>
        <tbody>
        <?php foreach ($plans as $p): $active = ($p['status'] ?? 'active') === 'active'; $used = (int) $p['used']; ?>
          <tr>
            <td class="mono faint" style="font-size:12px"><?= (int) $p['sort_order'] ?></td>
            <td style="font-weight:600"><?= e($p['name']) ?></td>
            <td class="muted" style="font-size:12.5px"><?= $p['vendor'] ? e($p['vendor']) : '<span class="faint">—</span>' ?></td>
            <td class="muted" style="font-size:12.5px"><?= $p['note'] ? e($p['note']) : '<span class="faint">—</span>' ?></td>
            <td><span class="pill <?= $active ? 'pill-ok' : 'pill-off' ?>"><?= $active ? 'ใช้งาน' : 'ระงับ' ?></span></td>
            <td class="mono"><?= $used ?></td>
            <td style="text-align:right">
              <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
                <button type="button" class="btn btn-sm" onclick="document.getElementById('edit-plan-<?= (int) $p['id'] ?>').showModal()">แก้ไข</button>
                <form method="post" action="<?= e(url('admin/plans/status')) ?>"
                      data-confirm="<?= $active ? 'ระงับแพ็กเกจนี้? ลูกค้าจะเลือกไม่ได้ (สิทธิ์เดิมไม่กระทบ)' : 'เปิดใช้งานแพ็กเกจนี้อีกครั้ง?' ?>" data-confirm-title="ยืนยัน" data-confirm-ok="ยืนยัน">
                  <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button class="btn btn-sm" type="submit"><?= $active ? 'ระงับ' : 'เปิดใช้งาน' ?></button>
                </form>
                <?php if ($used === 0): ?>
                  <form method="post" action="<?= e(url('admin/plans/delete')) ?>" data-confirm="ลบแพ็กเกจนี้?" data-confirm-title="ยืนยันการลบ" data-confirm-ok="ลบ" data-confirm-danger>
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button class="btn btn-sm" type="submit" style="color:var(--danger)">ลบ</button>
                  </form>
                <?php else: ?>
                  <span class="faint" style="font-size:12px;align-self:center">ลบไม่ได้</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$plans): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:26px">ยังไม่มีแพ็กเกจ AI</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php foreach ($plans as $p): $active = ($p['status'] ?? 'active') === 'active'; ?>
<dialog id="edit-plan-<?= (int) $p['id'] ?>" data-persistent class="card" style="border:1px solid var(--border);max-width:440px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('admin/plans/update')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
    <div class="modal-head" style="margin-bottom:10px">
      <h3 style="margin:0;font-size:17px">แก้ไขแพ็กเกจ AI</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="field"><label>ชื่อแพ็กเกจ</label><input class="input" type="text" name="name" required maxlength="120" value="<?= e($p['name']) ?>"></div>
    <div class="field"><label>ผู้ให้บริการ</label><input class="input" type="text" name="vendor" maxlength="80" value="<?= e((string) $p['vendor']) ?>"></div>
    <div class="field"><label>หมายเหตุ</label><input class="input" type="text" name="note" maxlength="190" value="<?= e((string) $p['note']) ?>"></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div class="field"><label>ลำดับ</label><input class="input" type="number" name="sort_order" value="<?= (int) $p['sort_order'] ?>"></div>
      <div class="field"><label>สถานะ</label>
        <select class="input" name="status">
          <option value="active" <?= $active ? 'selected' : '' ?>>ใช้งาน</option>
          <option value="suspended" <?= $active ? '' : 'selected' ?>>ระงับ</option>
        </select>
      </div>
    </div>
    <?php if ((int) $p['used'] > 0): ?>
      <div class="faint" style="font-size:11.5px;margin-top:4px">* แพ็กเกจนี้ถูกใช้แลกสิทธิ์แล้ว <?= (int) $p['used'] ?> รายการ — การเปลี่ยนชื่อจะไม่กระทบชื่อที่บันทึกไว้ในคำขอเดิม</div>
    <?php endif; ?>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit">บันทึก</button>
    </div>
  </form>
</dialog>
<?php endforeach; ?>

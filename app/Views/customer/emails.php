<?php
/** @var array $emails registered emails (each with a 'used' seat count and a 'plans' breakdown)
 *  @var string $q current search term
 *  @var int $total total registered emails (before filtering) */
?>
<div style="margin-bottom:22px">
  <h1 style="margin:0;font-size:26px">อีเมลที่ลงทะเบียน</h1>
  <p class="muted" style="margin:6px 0 0">ลงทะเบียนอีเมลที่จะใช้ผูกกับบัญชี AI Pro ไว้ล่วงหน้า — ตอนแลกสิทธิ์จะเลือกได้เฉพาะอีเมลที่ "ใช้งาน" ในรายการนี้เท่านั้น</p>
</div>

<div class="card" style="padding:20px;margin-bottom:22px;max-width:640px">
  <div style="font-weight:600;margin-bottom:12px">เพิ่มอีเมลใหม่</div>
  <form method="post" action="<?= e(url('account/emails')) ?>" style="display:grid;gap:12px">
    <?= csrf_field() ?>
    <div class="field"><label>อีเมล</label><input class="input" type="email" name="email" required placeholder="user@example.com"></div>
    <div class="field"><label>ชื่อเรียก (ไม่บังคับ)</label><input class="input" type="text" name="label" maxlength="120" placeholder="เช่น ครูสมชาย / ห้องคอม 1"></div>
    <div><button class="btn btn-primary" type="submit">ลงทะเบียนอีเมล</button></div>
  </form>
</div>

<div class="card" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-weight:600">รายการอีเมล (<?= count($emails) ?><?= $q !== '' ? ' จาก ' . (int) $total : '' ?>)</span>
    <form method="get" action="<?= e(url('account/emails')) ?>" style="margin-left:auto;display:flex;gap:8px">
      <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาอีเมล / ชื่อเรียก" style="min-width:220px;padding:7px 10px">
      <button class="btn btn-sm" type="submit">ค้นหา</button>
      <?php if ($q !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('account/emails')) ?>">ล้าง</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>อีเมล</th><th>ชื่อเรียก</th><th>แพ็กเกจที่ผูกอยู่ / ช่วงเวลาใช้งาน</th><th>สถานะ</th><th>ใช้แลกสิทธิ์แล้ว</th><th>วันที่ลงทะเบียน</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($emails as $m): $active = ($m['status'] ?? 'active') === 'active'; $used = (int) $m['used']; ?>
        <tr>
          <td style="word-break:break-all"><?= e($m['email']) ?></td>
          <td class="muted" style="font-size:12.5px"><?= $m['label'] ? e($m['label']) : '<span class="faint">—</span>' ?></td>
          <td>
            <?php if ($m['plans']): ?>
              <div class="stack" style="gap:6px">
                <?php foreach ($m['plans'] as $pl): ?>
                  <div style="font-size:12.5px">
                    <span style="font-weight:600"><?= e($pl['plan_name']) ?></span>
                    <span class="faint">· <?= units($pl['units']) ?> · <?= (int) $pl['cnt'] ?> คำขอ</span>
                    <div class="muted" style="font-size:11.5px;margin-top:2px">
                      <?php if ($pl['since']): ?>
                        เริ่ม <?= thai_date(substr((string) $pl['since'], 0, 10)) ?>
                        <?= $pl['until'] ? ' → สิ้นสุด ' . thai_date($pl['until']) : ' → รอกำหนดวันสิ้นสุด' ?>
                      <?php else: ?>
                        <span class="faint">ยังไม่จัดหา — ช่วงเวลาเริ่มนับเมื่อจัดหาสำเร็จ</span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <span class="faint" style="font-size:12.5px">ยังไม่ผูกกับแพ็กเกจใด</span>
            <?php endif; ?>
          </td>
          <td><span class="pill <?= $active ? 'pill-ok' : 'pill-off' ?>"><?= $active ? 'ใช้งาน' : 'ระงับ' ?></span></td>
          <td class="mono"><?= $used ?> ครั้ง</td>
          <td class="muted" style="font-size:12.5px"><?= thai_date(substr((string) $m['created_at'], 0, 10)) ?></td>
          <td style="text-align:right">
            <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
              <button type="button" class="btn btn-sm" onclick="document.getElementById('edit-email-<?= (int) $m['id'] ?>').showModal()">แก้ไข</button>
              <form method="post" action="<?= e(url('account/emails/status')) ?>"
                    onsubmit="return confirm('<?= $active ? 'ระงับการใช้งานอีเมลนี้? จะเลือกแลกสิทธิ์ไม่ได้จนกว่าจะเปิดใช้อีกครั้ง' : 'เปิดใช้งานอีเมลนี้อีกครั้ง?' ?>')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <button class="btn btn-sm" type="submit"><?= $active ? 'ระงับ' : 'เปิดใช้งาน' ?></button>
              </form>
              <?php if ($used === 0): ?>
                <form method="post" action="<?= e(url('account/emails/delete')) ?>" onsubmit="return confirm('ลบอีเมลนี้?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                  <button class="btn btn-sm" type="submit" style="color:var(--danger)">ลบ</button>
                </form>
              <?php else: ?>
                <span class="faint" style="font-size:12px;align-self:center">ลบไม่ได้</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$emails): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:26px"><?= $q !== '' ? 'ไม่พบอีเมลที่ค้นหา' : 'ยังไม่มีอีเมลที่ลงทะเบียน' ?></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach ($emails as $m): $used = (int) $m['used']; ?>
<dialog id="edit-email-<?= (int) $m['id'] ?>" data-persistent class="card" style="border:1px solid var(--border);max-width:420px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('account/emails/update')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
    <div class="modal-head" style="margin-bottom:10px">
      <h3 style="margin:0;font-size:17px">แก้ไขอีเมล</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="field"><label>อีเมล</label>
      <input class="input" type="email" name="email" value="<?= e($m['email']) ?>" <?= $used > 0 ? 'readonly' : 'required' ?>>
      <?php if ($used > 0): ?>
        <div class="faint" style="font-size:11.5px;margin-top:6px">* อีเมลนี้ผูกกับสิทธิ์ที่แลกไปแล้ว <?= $used ?> รายการ จึงแก้ไขที่อยู่อีเมลไม่ได้ — แก้ไขได้เฉพาะชื่อเรียก</div>
      <?php endif; ?>
    </div>
    <div class="field"><label>ชื่อเรียก (ไม่บังคับ)</label><input class="input" type="text" name="label" maxlength="120" value="<?= e((string) $m['label']) ?>"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit">บันทึก</button>
    </div>
  </form>
</dialog>
<?php endforeach; ?>

<?php
/** @var array $assistants assistant user rows
 *  @var string $q current search term
 *  @var int $total total assistants (before filtering) */
?>
<div style="margin-bottom:22px">
  <h1 style="margin:0;font-size:26px">ผู้ช่วยของฉัน</h1>
  <p class="muted" style="margin:6px 0 0">เพิ่มผู้ใช้ที่ช่วยดูแลบัญชีของคุณ — ผู้ช่วยเข้าสู่ระบบด้วยอีเมลของตัวเอง และทำงานกับสัญญา/อีเมล/API Key ชุดเดียวกับคุณ (แต่จัดการรายชื่อผู้ช่วยไม่ได้)</p>
</div>

<div class="card" style="padding:20px;margin-bottom:22px;max-width:640px">
  <div style="font-weight:600;margin-bottom:12px">เพิ่มผู้ช่วยใหม่</div>
  <form method="post" action="<?= e(url('account/team')) ?>" style="display:grid;gap:12px">
    <?= csrf_field() ?>
    <div class="field"><label>ชื่อผู้ช่วย</label><input class="input" type="text" name="name" required maxlength="190" placeholder="เช่น คุณสมหญิง (ฝ่ายธุรการ)"></div>
    <div class="field"><label>อีเมลสำหรับเข้าสู่ระบบ</label><input class="input" type="email" name="email" required placeholder="assistant@example.com"></div>
    <div class="field"><label>รหัสผ่านเริ่มต้น (อย่างน้อย 6 ตัวอักษร)</label><input class="input" type="password" name="password" required minlength="6"></div>
    <div><button class="btn btn-primary" type="submit">เพิ่มผู้ช่วย</button></div>
  </form>
</div>

<div class="card" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-weight:600">รายชื่อผู้ช่วย (<?= count($assistants) ?><?= $q !== '' ? ' จาก ' . (int) $total : '' ?>)</span>
    <form method="get" action="<?= e(url('account/team')) ?>" style="margin-left:auto;display:flex;gap:8px">
      <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อ / อีเมล" style="min-width:220px;padding:7px 10px">
      <button class="btn btn-sm" type="submit">ค้นหา</button>
      <?php if ($q !== ''): ?><a class="btn btn-sm btn-ghost" href="<?= e(url('account/team')) ?>">ล้าง</a><?php endif; ?>
    </form>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>ชื่อ</th><th>อีเมล</th><th>สถานะ</th><th>เพิ่มเมื่อ</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($assistants as $a): $active = ($a['status'] ?? 'active') === 'active'; ?>
        <tr>
          <td><?= e($a['name']) ?></td>
          <td style="word-break:break-all"><?= e($a['email']) ?></td>
          <td><span class="pill <?= $active ? 'pill-ok' : 'pill-off' ?>"><?= $active ? 'ใช้งาน' : 'ระงับ' ?></span></td>
          <td class="muted" style="font-size:12.5px"><?= thai_date(substr((string) $a['created_at'], 0, 10)) ?></td>
          <td style="text-align:right">
            <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
              <button type="button" class="btn btn-sm" onclick="document.getElementById('edit-user-<?= (int) $a['id'] ?>').showModal()">แก้ไข</button>
              <form method="post" action="<?= e(url('account/team/status')) ?>"
                    onsubmit="return confirm('<?= $active ? 'ระงับผู้ช่วยคนนี้? จะเข้าสู่ระบบไม่ได้จนกว่าจะเปิดใช้อีกครั้ง' : 'เปิดใช้งานผู้ช่วยคนนี้อีกครั้ง?' ?>')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button class="btn btn-sm" type="submit"><?= $active ? 'ระงับ' : 'เปิดใช้งาน' ?></button>
              </form>
              <form method="post" action="<?= e(url('account/team/delete')) ?>" onsubmit="return confirm('ลบผู้ช่วยคนนี้? บัญชีจะเข้าสู่ระบบไม่ได้อีก')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                <button class="btn btn-sm" type="submit" style="color:var(--danger)">ลบ</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$assistants): ?>
        <tr><td colspan="5" class="muted" style="text-align:center;padding:26px"><?= $q !== '' ? 'ไม่พบผู้ช่วยที่ค้นหา' : 'ยังไม่มีผู้ช่วย' ?></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach ($assistants as $a): ?>
<dialog id="edit-user-<?= (int) $a['id'] ?>" data-persistent class="card" style="border:1px solid var(--border);max-width:420px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('account/team/update')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
    <div class="modal-head" style="margin-bottom:10px">
      <h3 style="margin:0;font-size:17px">แก้ไขผู้ช่วย</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <div class="field"><label>ชื่อผู้ช่วย</label><input class="input" type="text" name="name" required maxlength="190" value="<?= e($a['name']) ?>"></div>
    <div class="field"><label>อีเมล</label><input class="input" type="email" value="<?= e($a['email']) ?>" readonly>
      <div class="faint" style="font-size:11.5px;margin-top:6px">* อีเมลเข้าสู่ระบบเปลี่ยนไม่ได้ — หากต้องการเปลี่ยน ให้ลบแล้วเพิ่มใหม่</div>
    </div>
    <div class="field"><label>ตั้งรหัสผ่านใหม่ (เว้นว่างหากไม่เปลี่ยน)</label><input class="input" type="password" name="password" minlength="6" autocomplete="new-password"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit">บันทึก</button>
    </div>
  </form>
</dialog>
<?php endforeach; ?>

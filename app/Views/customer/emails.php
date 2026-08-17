<?php
/** @var array $emails registered emails (each with a 'used' seat count) */
?>
<div style="margin-bottom:22px">
  <h1 style="margin:0;font-size:26px">อีเมลที่ลงทะเบียน</h1>
  <p class="muted" style="margin:6px 0 0">ลงทะเบียนอีเมลที่จะใช้ผูกกับบัญชี AI Pro ไว้ล่วงหน้า — ตอนแลกสิทธิ์จะเลือกได้เฉพาะอีเมลในรายการนี้เท่านั้น</p>
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
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);font-weight:600">รายการอีเมล (<?= count($emails) ?>)</div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>อีเมล</th><th>ชื่อเรียก</th><th>ใช้แลกสิทธิ์แล้ว</th><th>วันที่ลงทะเบียน</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($emails as $m): ?>
        <tr>
          <td style="word-break:break-all"><?= e($m['email']) ?></td>
          <td class="muted" style="font-size:12.5px"><?= $m['label'] ? e($m['label']) : '<span class="faint">—</span>' ?></td>
          <td class="mono"><?= (int) $m['used'] ?> ครั้ง</td>
          <td class="muted" style="font-size:12.5px"><?= thai_date(substr((string) $m['created_at'], 0, 10)) ?></td>
          <td style="text-align:right">
            <?php if ((int) $m['used'] === 0): ?>
              <form method="post" action="<?= e(url('account/emails/delete')) ?>" onsubmit="return confirm('ลบอีเมลนี้?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                <button class="btn btn-sm" type="submit">ลบ</button>
              </form>
            <?php else: ?>
              <span class="faint" style="font-size:12px">ลบไม่ได้</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$emails): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:26px">ยังไม่มีอีเมลที่ลงทะเบียน</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

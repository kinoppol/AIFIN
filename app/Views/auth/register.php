<h2 style="margin:0 0 4px;font-size:20px">ลงทะเบียน</h2>
<p class="muted" style="margin:0 0 18px;font-size:13.5px">เปิดบัญชีลูกค้าเพื่อทำสัญญาซื้อหน่วย AI Pro</p>
<form method="post" action="<?= e(url('register')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="name">ชื่อ / บริษัท</label>
    <input class="input" type="text" id="name" name="name" required autofocus value="<?= old('name') ?>">
  </div>
  <div class="field">
    <label for="email">อีเมล</label>
    <input class="input" type="email" id="email" name="email" required value="<?= old('email') ?>">
  </div>
  <div class="field">
    <label for="password">รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)</label>
    <input class="input" type="password" id="password" name="password" required minlength="6">
  </div>
  <button class="btn btn-primary btn-block" type="submit">สร้างบัญชี</button>
</form>
<p class="muted" style="text-align:center;margin:16px 0 0;font-size:13px">
  มีบัญชีอยู่แล้ว? <a href="<?= e(url('login')) ?>">เข้าสู่ระบบ</a>
</p>

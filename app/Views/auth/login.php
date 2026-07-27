<h2 style="margin:0 0 4px;font-size:20px">เข้าสู่ระบบ</h2>
<p class="muted" style="margin:0 0 18px;font-size:13.5px">เข้าสู่แผงบริหารจัดการหรือบัญชีลูกค้าของคุณ</p>
<form method="post" action="<?= e(url('login')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">อีเมล</label>
    <input class="input" type="email" id="email" name="email" required autofocus value="<?= old('email') ?>">
  </div>
  <div class="field">
    <label for="password">รหัสผ่าน</label>
    <input class="input" type="password" id="password" name="password" required>
  </div>
  <button class="btn btn-primary btn-block" type="submit">เข้าสู่ระบบ</button>
</form>
<p class="muted" style="text-align:center;margin:16px 0 0;font-size:13px">
  ยังไม่มีบัญชี? <a href="<?= e(url('register')) ?>">ลงทะเบียน</a>
</p>

<?php
/** @var array $domains allowed email domains (empty = any domain)
 *  @var int $emailCount how many emails are registered today */
?>
<?= (new App\Core\View())->partial('partials/settings_tabs', ['tab' => 'domains']) ?>

<div class="card" style="padding:20px;max-width:640px">
  <div style="font-weight:600">จำกัดโดเมนที่ลงทะเบียนได้</div>
  <div class="muted" style="font-size:12.5px;margin-top:3px">
    <?php if ($domains): ?>
      ลงทะเบียนอีเมลได้เฉพาะโดเมนในรายการนี้ (รวมโดเมนย่อย) — ลบให้หมดหากต้องการอนุญาตทุกโดเมน
    <?php else: ?>
      ขณะนี้ยังไม่จำกัดโดเมน — เพิ่มโดเมนของหน่วยงานเพื่อให้ลงทะเบียนได้เฉพาะอีเมลของหน่วยงานเท่านั้น
    <?php endif; ?>
  </div>
  <?php if ($domains): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px">
      <?php foreach ($domains as $d): ?>
        <form method="post" action="<?= e(url('account/domains/delete')) ?>" style="display:inline-flex"
              data-confirm="ลบโดเมน <?= e($d['domain']) ?> ออกจากรายการ?" data-confirm-title="ยืนยันการลบ" data-confirm-ok="ลบ" data-confirm-danger>
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
          <button class="pill pill-info" type="submit" style="border:0;cursor:pointer;font:inherit;display:inline-flex;align-items:center;gap:6px">
            @<?= e($d['domain']) ?> <span class="faint">✕</span>
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('account/domains')) ?>" style="display:flex;gap:8px;align-items:end;margin-top:12px">
    <?= csrf_field() ?>
    <div class="field" style="flex:1;margin:0"><label>โดเมนของหน่วยงาน</label>
      <input class="input" type="text" name="domain" required maxlength="190" placeholder="เช่น rvc.ac.th"></div>
    <button class="btn btn-primary" type="submit">เพิ่มโดเมน</button>
  </form>
  <div class="faint" style="font-size:11.5px;margin-top:12px">
    * มีอีเมลที่ลงทะเบียนไว้แล้ว <?= (int) $emailCount ?> รายการ — การเพิ่มโดเมนมีผลกับการลงทะเบียน/แก้ไขครั้งต่อไป ไม่ลบอีเมลเดิมที่อยู่นอกโดเมน (หากไม่ต้องการให้ใช้ ให้ระงับที่แท็บอีเมลที่ลงทะเบียน)
  </div>
</div>

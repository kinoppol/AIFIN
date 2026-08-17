<?php
/** @var array $domains allowed email domains (empty = any domain)
 *  @var int $emailCount how many emails are registered today */
?>
<?= (new App\Core\View())->partial('partials/settings_tabs', ['tab' => 'domains']) ?>

<div class="card" style="padding:20px;max-width:640px">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span style="font-weight:600">จำกัดโดเมนที่ลงทะเบียนได้</span>
    <button type="button" class="btn btn-primary btn-sm" style="margin-left:auto" onclick="document.getElementById('add-domain-modal').showModal()">+ เพิ่มโดเมน</button>
  </div>
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
  <div class="faint" style="font-size:11.5px;margin-top:12px">
    * มีอีเมลที่ลงทะเบียนไว้แล้ว <?= (int) $emailCount ?> รายการ — การเพิ่มโดเมนมีผลกับการลงทะเบียน/แก้ไขครั้งต่อไป ไม่ลบอีเมลเดิมที่อยู่นอกโดเมน (หากไม่ต้องการให้ใช้ ให้ระงับที่แท็บอีเมลที่ลงทะเบียน)
  </div>
</div>

<dialog id="add-domain-modal" data-persistent class="card" style="border:1px solid var(--border);max-width:420px;width:92%;padding:0;color:var(--text)">
  <form method="post" action="<?= e(url('account/domains')) ?>" style="padding:22px">
    <?= csrf_field() ?>
    <div class="modal-head" style="margin-bottom:10px">
      <h3 style="margin:0;font-size:17px">เพิ่มโดเมนที่อนุญาต</h3>
      <button type="button" class="modal-x" data-dialog-close aria-label="ปิด"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
    </div>
    <p class="muted" style="margin:0 0 12px;font-size:12.5px">เมื่อมีโดเมนในรายการ จะลงทะเบียนอีเมลได้เฉพาะโดเมนนั้นและโดเมนย่อยเท่านั้น</p>
    <div class="field"><label>โดเมนของหน่วยงาน</label>
      <input class="input" type="text" name="domain" required maxlength="190" placeholder="เช่น rvc.ac.th"></div>
    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
      <button type="button" class="btn btn-ghost" data-dialog-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit">เพิ่มโดเมน</button>
    </div>
  </form>
</dialog>

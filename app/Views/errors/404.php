<div style="min-height:70vh;display:grid;place-items:center;text-align:center;padding:40px">
  <div>
    <div class="mono" style="font-size:48px;color:var(--accent)">404</div>
    <h2 style="margin:8px 0 6px">ไม่พบหน้าที่ต้องการ</h2>
    <p class="muted" style="margin:0 0 18px">เส้นทาง <code><?= e($path ?? '') ?></code> ไม่มีอยู่ในระบบ</p>
    <a class="btn btn-primary" href="<?= e(url('')) ?>">กลับหน้าแรก</a>
  </div>
</div>

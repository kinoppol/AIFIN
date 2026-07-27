<?php /** @var array $status @var array $pending */ ?>
<div class="page-cols">
  <div class="card card-pad" style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
    <div class="muted" style="font-size:13.5px;line-height:1.7">
      จัดการการอัปเดตโครงสร้างฐานข้อมูล — วางไฟล์ migration ใหม่ (เช่น <code>002_xxx.php</code>) ในโฟลเดอร์ <code>/migrations</code> แล้วกด “รัน migration ที่รอดำเนินการ”
      <?php if ($pending): ?><br><b style="color:var(--warn)">มี <?= count($pending) ?> migration ที่ยังไม่ถูกรัน</b><?php else: ?><br><b style="color:var(--ok)">ฐานข้อมูลเป็นเวอร์ชันล่าสุด</b><?php endif; ?>
    </div>
    <form method="post" action="<?= e(url('admin/migrations/run')) ?>" data-confirm="รัน migration ที่รอดำเนินการทั้งหมดตอนนี้?&#10;การเปลี่ยนโครงสร้างฐานข้อมูลควรสำรองข้อมูลก่อน" data-confirm-title="ยืนยันการรัน Migration" data-confirm-ok="รันเลย"><?= csrf_field() ?>
      <button class="btn btn-primary" type="submit" <?= $pending ? '' : 'disabled' ?>><?= icon('database', 15) ?>รัน migration ที่รอดำเนินการ</button>
    </form>
  </div>

  <div class="card" style="overflow:hidden">
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>Migration</th><th>สถานะ</th><th>Batch</th><th>เวลาที่รัน</th></tr></thead>
        <tbody>
        <?php foreach ($status as $m): ?>
          <tr>
            <td class="mono" style="font-size:12.5px"><?= e($m['name']) ?></td>
            <td><?= $m['applied']
                  ? '<span class="pill pill-ok">รันแล้ว</span>'
                  : '<span class="pill pill-wait">รอดำเนินการ</span>' ?></td>
            <td class="mono muted"><?= $m['batch'] !== null ? (int)$m['batch'] : '—' ?></td>
            <td class="muted" style="font-size:12.5px"><?= $m['applied_at'] ? e($m['applied_at']) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$status): ?><tr><td colspan="4" class="muted" style="text-align:center;padding:26px">ไม่พบไฟล์ migration</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

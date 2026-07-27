<?php /** @var array $queue @var int $pending @var int $today */ ?>
<div class="page-cols">
  <div class="card" style="overflow:hidden">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <div style="font-weight:600;font-size:15px">คิวจัดหาสิทธิ์ AI</div>
      <div class="muted" style="font-size:12.5px">รอดำเนินการ <?= (int)$pending ?> · วันนี้ <?= (int)$today ?></div>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead><tr><th>เลขที่คำขอ</th><th>อีเมลที่ผูก / ลูกค้า</th><th>หน่วย</th><th>สถานะ</th><th style="text-align:right">การจัดการ</th></tr></thead>
        <tbody>
        <?php foreach ($queue as $q): ?>
          <tr>
            <td class="mono" style="color:var(--accent);font-weight:600;font-size:12px"><?= e($q['redeem_no']) ?></td>
            <td><div class="muted" style="font-size:12.5px"><?= e($q['email']) ?></div><div class="faint" style="font-size:12px;margin-top:2px"><?= e($q['customer_name']) ?></div></td>
            <td class="mono" style="font-weight:600"><?= units($q['units']) ?> · <?= (int)$q['days'] ?> วัน</td>
            <td><?= pill('redeem', $q['status']) ?></td>
            <td style="text-align:right">
              <?php if (in_array($q['status'], ['pending','provisioning','awaiting_email'], true)): ?>
                <form method="post" action="<?= e(url('admin/redeem/status')) ?>" style="display:inline-flex;gap:6px;justify-content:flex-end">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                  <select name="status" class="input" style="width:auto;padding:6px 8px;font-size:12.5px">
                    <?php foreach ([['provisioning','กำลังจัดหา'],['awaiting_email','รอยืนยันอีเมล'],['success','จัดหาสำเร็จ'],['failed','ล้มเหลว']] as $opt): ?>
                      <option value="<?= $opt[0] ?>" <?= $q['status']===$opt[0]?'selected':'' ?>><?= e($opt[1]) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-primary btn-sm" type="submit">อัปเดต</button>
                </form>
              <?php else: ?>
                <span class="faint" style="font-size:12.5px"><?= $q['status']==='success' ? 'เสร็จสิ้น' : '—' ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$queue): ?><tr><td colspan="5" class="muted" style="text-align:center;padding:26px">ยังไม่มีคำขอแลกสิทธิ์ในคิว</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

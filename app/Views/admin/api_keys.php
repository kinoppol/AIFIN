<?php
/** @var array $queue @var int $requested */
$statusPill = function (string $s): string {
    $map = [
        'requested'    => ['pill-wait', 'รอจัดหา'],
        'provisioning' => ['pill-info', 'กำลังจัดหา'],
        'active'       => ['pill-ok', 'ใช้งานได้'],
        'failed'       => ['pill-bad', 'ล้มเหลว'],
    ];
    [$c, $l] = $map[$s] ?? ['pill-off', $s];
    return '<span class="pill ' . $c . '">' . e($l) . '</span>';
};
?>
<div class="page-cols">
  <div class="card card-pad muted" style="font-size:13.5px;line-height:1.7">
    คำขอสร้าง API Key จากลูกค้า — <b style="color:var(--text)">1 การ์ดจอ = 1 API Key</b> จัดหาโดยระบุ <b style="color:var(--text)">BASE URL</b> และ <b style="color:var(--text)">API Key</b> แล้วส่งให้ลูกค้า
    <?php if ($requested): ?><br><b style="color:var(--warn)">รอจัดหา <?= (int)$requested ?> รายการ</b><?php endif; ?>
  </div>

  <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px" class="ak-grid">
    <?php foreach ($queue as $k):
      $pending = in_array($k['status'], ['requested', 'provisioning'], true); ?>
      <div class="card card-pad">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
          <div>
            <div class="mono" style="font-size:12px;color:var(--accent)"><?= e($k['key_no']) ?></div>
            <div style="font-weight:600;font-size:15px;margin-top:4px"><?= e($k['customer_name']) ?></div>
            <div class="muted" style="font-size:12.5px;margin-top:2px"><?= e($k['contract_no']) ?><?= $k['label'] ? ' · ' . e($k['label']) : '' ?></div>
          </div>
          <?= $statusPill($k['status']) ?>
        </div>

        <?php if ($pending): ?>
          <form method="post" action="<?= e(url('admin/gpu/provision')) ?>" style="margin-top:14px"
                data-confirm="ยืนยันส่งมอบ API Key นี้ให้ลูกค้า?" data-confirm-title="ยืนยันการจัดหา" data-confirm-ok="ส่งมอบ">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
            <div class="field"><label>BASE URL</label><input class="input mono" type="url" name="base_url" placeholder="https://gpu.example.com/v1" required></div>
            <div class="field"><label>API Key</label><input class="input mono" type="text" name="api_key" placeholder="sk-..." required></div>
            <div style="display:flex;gap:9px">
              <button class="btn btn-primary" style="flex:1" type="submit"><?= icon('send', 15) ?>ส่งมอบให้ลูกค้า</button>
              <button class="btn btn-danger" type="submit" formaction="<?= e(url('admin/gpu/status')) ?>" name="status" value="failed"
                      formnovalidate data-confirm="ทำเครื่องหมายว่าล้มเหลวและคืนการ์ดให้ลูกค้า?" data-confirm-title="ยืนยัน" data-confirm-ok="ล้มเหลว" data-confirm-danger>ล้มเหลว</button>
            </div>
          </form>
        <?php else: ?>
          <div style="margin-top:14px;border:1px solid var(--border);border-radius:11px;background:var(--sunk);padding:12px 14px;display:grid;gap:8px">
            <div><div class="faint" style="font-size:11.5px">BASE URL</div><div class="mono" style="font-size:12.5px;word-break:break-all"><?= e($k['base_url']) ?></div></div>
            <div><div class="faint" style="font-size:11.5px">API Key</div><div class="mono" style="font-size:12.5px;word-break:break-all"><?= e($k['api_key']) ?></div></div>
            <div style="display:flex;justify-content:space-between;gap:10px">
              <span class="faint" style="font-size:11.5px">จัดหาเมื่อ <?= $k['provisioned_at'] ? e($k['provisioned_at']) : '—' ?></span>
              <span class="faint" style="font-size:11.5px">หมดอายุ <?= $k['expires_at'] ? thai_date($k['expires_at']) : '—' ?></span>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$queue): ?><div class="card card-pad muted" style="grid-column:1/-1;text-align:center">ยังไม่มีคำขอสร้าง API Key</div><?php endif; ?>
  </div>
</div>
<style>@media(max-width:900px){.ak-grid{grid-template-columns:1fr!important}}</style>

<?php
/** @var array $seats  all redemptions (AI Pro email seats) across the user's contracts
 *  @var array $apikeys all GPU API keys across the user's contracts */
$akStatus = function (string $s): array {
    return [
        'requested'    => ['pill-wait', 'รอจัดหา'],
        'provisioning' => ['pill-info', 'กำลังจัดหา'],
        'active'       => ['pill-ok', 'ใช้งานได้'],
        'failed'       => ['pill-bad', 'ล้มเหลว'],
    ][$s] ?? ['pill-off', $s];
};
?>
<div style="margin-bottom:22px">
  <h1 style="margin:0;font-size:26px">AI ของฉัน</h1>
  <p class="muted" style="margin:6px 0 0">รวมบัญชีสิทธิ์ AI Pro และ API Key ทั้งหมดจากทุกสัญญา พร้อมวันที่ได้รับและวันสิ้นสุดการใช้งาน</p>
</div>

<!-- AI Pro email seats -->
<div class="card" style="overflow:hidden;margin-bottom:22px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
    <?= icon('redeem', 18) ?><span style="font-weight:600">บัญชีสิทธิ์ AI Pro (อีเมล)</span>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>อีเมล</th><th>แพ็กเกจ AI</th><th>สัญญา</th><th>สิทธิ์</th><th>สถานะ</th><th>วันที่ได้รับ</th><th>สิ้นสุดการใช้งาน</th></tr></thead>
      <tbody>
      <?php foreach ($seats as $s): ?>
        <tr>
          <td style="word-break:break-all"><?= e($s['email']) ?></td>
          <td class="muted" style="font-size:12.5px"><?= !empty($s['plan_name']) ? e($s['plan_name']) : '<span class="faint">—</span>' ?></td>
          <td class="mono faint" style="font-size:12px"><?= e($s['contract_no']) ?></td>
          <td class="mono" style="font-weight:600"><?= units($s['units']) ?> · <?= (int)$s['days'] ?> วัน</td>
          <td><?= pill('redeem', $s['status']) ?></td>
          <td class="muted" style="font-size:12.5px"><?= $s['provisioned_at'] ? thai_date(substr($s['provisioned_at'],0,10)) : '<span class="faint">—</span>' ?></td>
          <td class="mono" style="font-size:12.5px"><?= $s['expires_at'] ? thai_date($s['expires_at']) : '<span class="faint">—</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$seats): ?><tr><td colspan="7" class="muted" style="text-align:center;padding:26px">ยังไม่มีบัญชีสิทธิ์ AI Pro — แลกหน่วยจากสัญญาเพื่อขอสิทธิ์</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- GPU API keys -->
<div class="card" style="overflow:hidden">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
    <?= icon('key', 18) ?><span style="font-weight:600">API Keys (GPU)</span>
    <span class="muted" style="font-size:12px;margin-left:auto">1 การ์ด GPU (G) = 30 วันใช้งาน</span>
  </div>
  <div class="table-wrap">
    <table class="data">
      <thead><tr><th>API Key</th><th>สัญญา</th><th>BASE URL / Key</th><th>สถานะ</th><th>วันที่ได้รับ</th><th>สิ้นสุดการใช้งาน</th></tr></thead>
      <tbody>
      <?php foreach ($apikeys as $k): [$cls, $lbl] = $akStatus($k['status']); ?>
        <tr>
          <td class="mono" style="font-weight:600;font-size:12px;color:var(--accent)"><?= e($k['key_no']) ?><?php if ($k['label']): ?><div class="faint" style="font-weight:400;margin-top:2px"><?= e($k['label']) ?></div><?php endif; ?><div class="faint" style="font-weight:400;margin-top:2px"><?= (int)($k['gpu_units'] ?? 1) ?> การ์ด · <?= (int)($k['days'] ?? 30) ?> วัน</div></td>
          <td class="mono faint" style="font-size:12px"><?= e($k['contract_no']) ?></td>
          <td>
            <?php if ($k['status'] === 'active'): ?>
              <div class="mono muted" style="font-size:11.5px;word-break:break-all"><?= e($k['base_url']) ?></div>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
                <code class="mono" data-secret style="font-size:11.5px;word-break:break-all;filter:blur(4px)"><?= e($k['api_key']) ?></code>
                <button type="button" class="btn btn-light btn-sm" style="padding:2px 8px;font-size:11px" data-reveal>แสดง</button>
              </div>
            <?php else: ?><span class="faint" style="font-size:12px">—</span><?php endif; ?>
          </td>
          <td><span class="pill <?= $cls ?>"><?= e($lbl) ?></span></td>
          <td class="muted" style="font-size:12.5px"><?= $k['provisioned_at'] ? thai_date(substr($k['provisioned_at'],0,10)) : '<span class="faint">—</span>' ?></td>
          <td class="mono" style="font-size:12.5px"><?= $k['expires_at'] ? thai_date($k['expires_at']) : '<span class="faint">—</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$apikeys): ?><tr><td colspan="6" class="muted" style="text-align:center;padding:26px">ยังไม่มี API Key — ขอสร้างได้จากสัญญาที่มีการ์ด GPU</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

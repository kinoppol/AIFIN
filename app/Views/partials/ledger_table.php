<?php
/**
 * Shared unit-ledger table. Purchase rows show the total baht value and a
 * "ใบเสร็จ" (brief receipt) button that opens a modal.
 *
 * @var array $ledger  ledger entries
 * @var array $c       the contract (for price/customer/receipt header)
 */
?>
<table class="data">
  <thead><tr><th>วันที่</th><th>รายการ</th><th>จำนวน</th><th style="text-align:right">คงเหลือ</th></tr></thead>
  <tbody>
  <?php foreach ($ledger as $l):
    $amt = (int) $l['amount'];
    $color = $amt > 0 ? 'var(--ok)' : ($amt < 0 ? 'var(--danger)' : 'var(--muted)');
    $isPurchase = ($l['type'] === 'purchase');
    $total = $isPurchase ? $amt * (int) $c['price_per_m'] : 0;
    $rid = 'receipt-' . (int) $l['id'];
  ?>
    <tr>
      <td class="muted" style="font-size:12.5px;white-space:nowrap"><?= thai_date($l['entry_date']) ?></td>
      <td style="font-size:13px">
        <?= e($l['description']) ?>
        <?php if ($isPurchase): ?>
          <div style="display:flex;align-items:center;gap:10px;margin-top:5px">
            <span class="muted" style="font-size:12px">มูลค่า <b style="color:var(--text)"><?= e(baht($total)) ?></b></span>
            <button type="button" class="btn btn-light btn-sm" style="padding:3px 9px;font-size:11.5px"
                    data-receipt-open="<?= $rid ?>">ใบเสร็จ</button>
          </div>
          <div id="<?= $rid ?>" hidden>
            <?= (new App\Core\View())->partial('partials/receipt', ['c' => $c, 'l' => $l, 'total' => $total]) ?>
          </div>
        <?php endif; ?>
      </td>
      <td class="mono" style="font-weight:600;color:<?= $color ?>;white-space:nowrap"><?= ($amt > 0 ? '+' : '') . $amt ?> M</td>
      <td class="mono muted" style="text-align:right;font-size:12.5px;white-space:nowrap"><?= (int) $l['balance'] ?> M</td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$ledger): ?><tr><td colspan="4" class="muted" style="text-align:center">ยังไม่มีรายการ</td></tr><?php endif; ?>
  </tbody>
</table>

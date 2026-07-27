<?php
/**
 * Track GPU movements in the unit ledger so GPU rows (bonus, card purchase,
 * API-key creation) show a G quantity/balance instead of "0 M".
 *
 * Callable so it can ADD COLUMN IF NOT EXISTS and backfill existing rows by
 * parsing their descriptions — safe to re-run.
 */
return [
    'name' => '004_ledger_gpu_columns',
    'up'   => function (PDO $db) {
        $db->exec("ALTER TABLE unit_ledger ADD COLUMN IF NOT EXISTS gpu_amount INT NOT NULL DEFAULT 0 AFTER balance");
        $db->exec("ALTER TABLE unit_ledger ADD COLUMN IF NOT EXISTS gpu_balance INT NOT NULL DEFAULT 0 AFTER gpu_amount");

        // Backfill gpu_amount from known GPU descriptions.
        $rows = $db->query("SELECT id, description FROM unit_ledger")->fetchAll();
        $upd = $db->prepare("UPDATE unit_ledger SET gpu_amount = ? WHERE id = ?");
        foreach ($rows as $r) {
            $g = 0;
            if (preg_match('/(?:แถมการ์ด GPU|ซื้อการ์ด GPU)\s*(\d+)/u', $r['description'], $m)) {
                $g = (int) $m[1];
            } elseif (mb_strpos($r['description'], 'API Key') !== false && mb_strpos($r['description'], 'GPU') !== false) {
                $g = -1;
            }
            $upd->execute([$g, $r['id']]);
        }

        // Recompute the running gpu_balance per contract in id order.
        $rows = $db->query("SELECT id, contract_id, gpu_amount FROM unit_ledger ORDER BY contract_id, id")->fetchAll();
        $balUpd = $db->prepare("UPDATE unit_ledger SET gpu_balance = ? WHERE id = ?");
        $running = [];
        foreach ($rows as $r) {
            $cid = (int) $r['contract_id'];
            $running[$cid] = ($running[$cid] ?? 0) + (int) $r['gpu_amount'];
            $balUpd->execute([$running[$cid], $r['id']]);
        }
    },
];

<?php
/**
 * An API key can now spend multiple GPU cards (G): 1 G = 30 days of access.
 * The key is valid for (gpu_units * unit_days) days from its provision date,
 * instead of until the contract end.
 *
 * Callable: ADD COLUMN IF NOT EXISTS + re-point existing active keys to a
 * provision-date + days expiry. Safe to re-run.
 */
return [
    'name' => '006_apikey_gpu_units',
    'up'   => function (PDO $db) {
        $db->exec("ALTER TABLE api_keys ADD COLUMN IF NOT EXISTS gpu_units INT NOT NULL DEFAULT 1 AFTER contract_id");
        $db->exec("ALTER TABLE api_keys ADD COLUMN IF NOT EXISTS days INT NOT NULL DEFAULT 30 AFTER gpu_units");
        // Existing active keys: expire days from their provision date.
        $db->exec(
            "UPDATE api_keys
             SET expires_at = DATE_ADD(DATE(provisioned_at), INTERVAL days DAY)
             WHERE status = 'active' AND provisioned_at IS NOT NULL"
        );
    },
];

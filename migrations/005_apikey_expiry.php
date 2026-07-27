<?php
/**
 * Give API keys an expiry date. A GPU card is rented for the contract term, so
 * a provisioned key is valid until the contract's end date.
 *
 * Callable: ADD COLUMN IF NOT EXISTS + backfill existing active keys from their
 * contract end date. Safe to re-run.
 */
return [
    'name' => '005_apikey_expiry',
    'up'   => function (PDO $db) {
        $db->exec("ALTER TABLE api_keys ADD COLUMN IF NOT EXISTS expires_at DATE NULL AFTER api_key");
        $db->exec(
            "UPDATE api_keys k
             JOIN contracts c ON c.id = k.contract_id
             SET k.expires_at = c.end_date
             WHERE k.status = 'active' AND k.expires_at IS NULL"
        );
    },
];

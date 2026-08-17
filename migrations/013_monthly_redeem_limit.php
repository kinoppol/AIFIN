<?php
/**
 * Per-contract monthly redeem cap, set by the customer.
 *
 * Guards against burning the wallet too quickly: at most N units may be
 * redeemed within one calendar month (failed redemptions still count — they
 * are refunded manually, same as the wallet balance). 0/NULL = no cap.
 */
return [
    'name' => '013_monthly_redeem_limit',
    'up'   => [
        "ALTER TABLE contracts ADD COLUMN IF NOT EXISTS monthly_redeem_limit INT UNSIGNED NOT NULL DEFAULT 0 AFTER unit_days",
    ],
];

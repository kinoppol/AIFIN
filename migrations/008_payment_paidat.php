<?php
/**
 * Record the amount and date/time the customer says they paid.
 * (payments.amount already exists; add the paid-at timestamp.)
 */
return [
    'name' => '008_payment_paidat',
    'up'   => [
        "ALTER TABLE payments ADD COLUMN IF NOT EXISTS paid_at DATETIME NULL AFTER reference",
    ],
];

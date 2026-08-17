<?php
/**
 * Let customers suspend a registered email instead of deleting it.
 *
 * A suspended address stays on record (and keeps any seats it already owns)
 * but can no longer be chosen when redeeming units.
 */
return [
    'name' => '010_customer_email_status',
    'up'   => [
        "ALTER TABLE customer_emails ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER label",
    ],
];

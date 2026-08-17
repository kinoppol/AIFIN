<?php
/**
 * Pre-registered emails for AI seats.
 *
 * A customer must register (ลงทะเบียน) the email addresses that may be bound to
 * an AI Pro account before redeeming units. The redeem form then only offers
 * these addresses — free-typed emails are rejected by ContractService::redeem().
 *
 * Existing redemptions are backfilled so emails already in use stay selectable.
 */
return [
    'name' => '009_customer_emails',
    'up'   => function (PDO $db) {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS customer_emails (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                email VARCHAR(190) NOT NULL,
                label VARCHAR(120) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_customer_email (user_id, email),
                KEY idx_customer_emails_user (user_id),
                CONSTRAINT fk_customer_emails_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        // Backfill from emails that were already redeemed before this rule existed.
        $db->exec(
            "INSERT IGNORE INTO customer_emails (user_id, email)
             SELECT c.user_id, r.email
             FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             WHERE c.user_id IS NOT NULL AND r.email <> ''"
        );
    },
];

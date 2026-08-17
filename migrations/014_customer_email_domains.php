<?php
/**
 * Allowed email domains per customer.
 *
 * A customer can restrict which addresses may be registered for AI seats to
 * their own organisation's domain(s). While the list is empty any domain is
 * accepted; once one or more domains are listed, only those are allowed
 * (enforced in Customer\AccountController when registering/editing an email).
 */
return [
    'name' => '014_customer_email_domains',
    'up'   => function (PDO $db) {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS customer_email_domains (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                domain VARCHAR(190) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_customer_domain (user_id, domain),
                KEY idx_customer_domains_user (user_id),
                CONSTRAINT fk_customer_domains_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },
];

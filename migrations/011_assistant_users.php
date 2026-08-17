<?php
/**
 * Assistant (ผู้ช่วย) logins under a customer account.
 *
 * A customer can create extra users that log in with their own email/password
 * but work on the *owner's* data: users.parent_user_id points at the account
 * owner, and every customer-side query runs against Auth::ownerId(). Suspended
 * users cannot log in.
 */
return [
    'name' => '011_assistant_users',
    'up'   => function (PDO $db) {
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS parent_user_id INT UNSIGNED NULL AFTER role");
        $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('active','suspended') NOT NULL DEFAULT 'active' AFTER parent_user_id");
        // Older MariaDB has no "ADD CONSTRAINT IF NOT EXISTS"; ignore a re-run.
        try {
            $db->exec(
                "ALTER TABLE users ADD CONSTRAINT fk_users_parent
                 FOREIGN KEY (parent_user_id) REFERENCES users(id) ON DELETE CASCADE"
            );
        } catch (PDOException $e) {
            // constraint already present
        }
    },
];

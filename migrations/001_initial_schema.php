<?php
/**
 * Initial schema for AIPRO Contracts.
 *
 * Domain: customers buy prepaid AI Pro "units" (M) — 1 M = 30 days of access —
 * under a 1-year contract (extendable up to +6 months). Units sit in the
 * contract wallet until redeemed against an email, which queues provisioning.
 */
return [
    'name' => '001_initial_schema',
    'up'   => [
        // Key/value application settings.
        "CREATE TABLE IF NOT EXISTS settings (
            `key`  VARCHAR(64) NOT NULL PRIMARY KEY,
            `value` TEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Users: admins and customers.
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(190) NOT NULL,
            role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Sellable packages / promotions. unit_days is locked at 30 system-wide;
        // only the per-M price varies.
        "CREATE TABLE IF NOT EXISTS packages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(40) NOT NULL,
            name VARCHAR(120) NOT NULL,
            note VARCHAR(190) NULL,
            units INT UNSIGNED NOT NULL DEFAULT 0,
            unit_days INT UNSIGNED NOT NULL DEFAULT 30,
            list_price INT UNSIGNED NOT NULL DEFAULT 0,
            sale_price INT UNSIGNED NOT NULL DEFAULT 0,
            promo_label VARCHAR(60) NULL,
            window_start DATE NULL,
            window_end DATE NULL,
            status ENUM('active','promo','closed') NOT NULL DEFAULT 'active',
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_packages_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Contracts: a customer's prepaid-unit agreement.
        "CREATE TABLE IF NOT EXISTS contracts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contract_no VARCHAR(30) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            package_id INT UNSIGNED NULL,
            customer_name VARCHAR(190) NOT NULL,
            units_total INT UNSIGNED NOT NULL DEFAULT 0,
            units_remaining INT UNSIGNED NOT NULL DEFAULT 0,
            unit_days INT UNSIGNED NOT NULL DEFAULT 30,
            price_per_m INT UNSIGNED NOT NULL DEFAULT 0,
            start_date DATE NOT NULL,
            base_end_date DATE NOT NULL,
            end_date DATE NOT NULL,
            extension_months_used INT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('active','expiring','pending_ext','extended','expired') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_contracts_no (contract_no),
            KEY idx_contracts_user (user_id),
            CONSTRAINT fk_contracts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_contracts_pkg FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Unit ledger: append-only movements per contract.
        "CREATE TABLE IF NOT EXISTS unit_ledger (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            contract_id INT UNSIGNED NOT NULL,
            entry_date DATE NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount INT NOT NULL,
            balance INT NOT NULL,
            type ENUM('purchase','redeem','extension','adjust') NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ledger_contract (contract_id),
            CONSTRAINT fk_ledger_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Redemptions: unit → AI Pro seat provisioning queue.
        "CREATE TABLE IF NOT EXISTS redemptions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            redeem_no VARCHAR(30) NOT NULL,
            contract_id INT UNSIGNED NOT NULL,
            email VARCHAR(190) NOT NULL,
            units INT UNSIGNED NOT NULL,
            days INT UNSIGNED NOT NULL,
            status ENUM('pending','provisioning','awaiting_email','success','failed') NOT NULL DEFAULT 'pending',
            requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            provisioned_at TIMESTAMP NULL,
            expires_at DATE NULL,
            UNIQUE KEY uniq_redeem_no (redeem_no),
            KEY idx_redeem_contract (contract_id),
            CONSTRAINT fk_redeem_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // Extension requests against a contract's 6-month quota.
        "CREATE TABLE IF NOT EXISTS extension_requests (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ext_no VARCHAR(30) NOT NULL,
            contract_id INT UNSIGNED NOT NULL,
            months_requested INT UNSIGNED NOT NULL,
            months_used_before INT UNSIGNED NOT NULL DEFAULT 0,
            reason TEXT NULL,
            new_end_date DATE NULL,
            status ENUM('pending','reviewing','over_quota','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            decided_at TIMESTAMP NULL,
            UNIQUE KEY uniq_ext_no (ext_no),
            KEY idx_ext_contract (contract_id),
            CONSTRAINT fk_ext_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
];

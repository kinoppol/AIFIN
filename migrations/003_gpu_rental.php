<?php
/**
 * GPU rental subsystem.
 *
 * Adds a parallel resource to the AI "M" units: GPU cards ("G"). 1 G = one
 * mid-range GPU card. GPU cards can be sold via dedicated GPU packages, or
 * bundled free with an AI package (packages.bonus_gpu). A card is turned into
 * an API key on request (1 card = 1 key); the admin supplies BASE URL + API key
 * when provisioning.
 *
 * Written as a callable so it can use ADD COLUMN IF NOT EXISTS and conditional
 * seeding — safe to re-run after a partial (thread-pool crash) apply.
 */
return [
    'name' => '003_gpu_rental',
    'up'   => function (PDO $db) {
        // Package kind + bundled-GPU count.
        $db->exec("ALTER TABLE packages ADD COLUMN IF NOT EXISTS kind ENUM('ai','gpu') NOT NULL DEFAULT 'ai' AFTER code");
        $db->exec("ALTER TABLE packages ADD COLUMN IF NOT EXISTS bonus_gpu INT UNSIGNED NOT NULL DEFAULT 0 AFTER units");

        // GPU card balance on a contract (mirrors units_total/units_remaining).
        $db->exec("ALTER TABLE contracts ADD COLUMN IF NOT EXISTS gpu_total INT UNSIGNED NOT NULL DEFAULT 0 AFTER units_remaining");
        $db->exec("ALTER TABLE contracts ADD COLUMN IF NOT EXISTS gpu_remaining INT UNSIGNED NOT NULL DEFAULT 0 AFTER gpu_total");

        // API keys — one per GPU card.
        $db->exec(
            "CREATE TABLE IF NOT EXISTS api_keys (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                key_no VARCHAR(30) NOT NULL,
                contract_id INT UNSIGNED NOT NULL,
                label VARCHAR(190) NULL,
                status ENUM('requested','provisioning','active','failed') NOT NULL DEFAULT 'requested',
                base_url VARCHAR(255) NULL,
                api_key VARCHAR(255) NULL,
                requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                provisioned_at TIMESTAMP NULL,
                UNIQUE KEY uniq_apikey_no (key_no),
                KEY idx_apikey_contract (contract_id),
                KEY idx_apikey_status (status),
                CONSTRAINT fk_apikey_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        // Seed default GPU packages once (price is per card; ~300 THB reference).
        $has = (int) $db->query("SELECT COUNT(*) FROM packages WHERE kind='gpu'")->fetchColumn();
        if ($has === 0) {
            $ins = $db->prepare(
                "INSERT INTO packages (code, kind, name, note, units, bonus_gpu, unit_days, list_price, sale_price, promo_label, status, sort_order)
                 VALUES (?, 'gpu', ?, ?, ?, 0, 30, ?, ?, ?, ?, ?)"
            );
            $ins->execute(['gpu-solo',  'GPU Solo',  'การ์ดจอระดับกลาง 1 ตัว',        1,  300, 300, null,   'active', 20]);
            $ins->execute(['gpu-team',  'GPU Team',  'ทีมพัฒนา / ฝึกโมเดลขนาดกลาง',   5,  300, 270, null,   'active', 21]);
            $ins->execute(['gpu-scale', 'GPU Scale', 'คลัสเตอร์สำหรับองค์กร',          20, 300, 240, '-20%', 'promo',  22]);
        }
    },
];

<?php
/**
 * AI plans that a redeemed seat can be provisioned on.
 *
 * These are the Claude-Pro-tier monthly consumer plans (Claude Pro, ChatGPT
 * Plus, Gemini AI Pro, …). The customer picks one when redeeming units; the
 * chosen plan name is snapshotted on the redemption so the queue still shows
 * what was ordered even if the plan list changes later.
 */
return [
    'name' => '012_ai_plans',
    'up'   => function (PDO $db) {
        $db->exec(
            "CREATE TABLE IF NOT EXISTS ai_plans (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                vendor VARCHAR(80) NULL,
                note VARCHAR(190) NULL,
                status ENUM('active','suspended') NOT NULL DEFAULT 'active',
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_ai_plans_name (name),
                KEY idx_ai_plans_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $db->exec("ALTER TABLE redemptions ADD COLUMN IF NOT EXISTS plan_id INT UNSIGNED NULL AFTER email");
        $db->exec("ALTER TABLE redemptions ADD COLUMN IF NOT EXISTS plan_name VARCHAR(120) NULL AFTER plan_id");

        // Seed the Claude-Pro-tier monthly plans (admin can edit/suspend later).
        $seed = [
            ['Claude Pro', 'Anthropic', 'แผนรายเดือนระดับ Pro ของ Claude', 10],
            ['ChatGPT Plus', 'OpenAI', 'แผนรายเดือนระดับ Plus ของ ChatGPT', 20],
            ['Google Gemini AI Pro', 'Google', 'แผนรายเดือนระดับ Pro ของ Gemini', 30],
            ['Microsoft 365 Copilot Pro', 'Microsoft', 'แผนรายเดือนระดับ Pro ของ Copilot', 40],
            ['GitHub Copilot Pro', 'GitHub', 'ผู้ช่วยเขียนโค้ดรายเดือนระดับ Pro', 50],
            ['Perplexity Pro', 'Perplexity', 'แผนค้นหา/วิจัยรายเดือนระดับ Pro', 60],
            ['SuperGrok', 'xAI', 'แผนรายเดือนระดับ Pro ของ Grok', 70],
            ['Cursor Pro', 'Cursor', 'AI IDE รายเดือนระดับ Pro', 80],
        ];
        $stmt = $db->prepare(
            "INSERT IGNORE INTO ai_plans (name, vendor, note, sort_order) VALUES (?, ?, ?, ?)"
        );
        foreach ($seed as $row) {
            $stmt->execute($row);
        }

        // Existing redemptions predate the plan choice — label them as Claude Pro.
        $db->exec("UPDATE redemptions SET plan_name = 'Claude Pro' WHERE plan_name IS NULL");
        $db->exec(
            "UPDATE redemptions r
             JOIN ai_plans p ON p.name = r.plan_name
             SET r.plan_id = p.id
             WHERE r.plan_id IS NULL"
        );
    },
];

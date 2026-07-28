<?php
/**
 * Contract payment/approval workflow.
 *
 * A new contract starts 'unpaid' (รอการชำระเงิน). The customer notifies payment
 * with a proof file → 'submitted' (รอตรวจสอบ). An admin approves → 'paid', which
 * makes the contract usable (redeem / API keys are blocked until paid).
 *
 * Existing contracts default to 'paid' so they stay usable.
 */
return [
    'name' => '007_contract_payments',
    'up'   => function (PDO $db) {
        $db->exec("ALTER TABLE contracts ADD COLUMN IF NOT EXISTS payment_status ENUM('unpaid','submitted','paid') NOT NULL DEFAULT 'paid' AFTER status");
        $db->exec("ALTER TABLE contracts ADD COLUMN IF NOT EXISTS total_amount INT UNSIGNED NOT NULL DEFAULT 0 AFTER payment_status");
        // Best-effort backfill of the contract value for existing rows.
        $db->exec("UPDATE contracts SET total_amount = units_total * price_per_m WHERE total_amount = 0");

        $db->exec(
            "CREATE TABLE IF NOT EXISTS payments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                contract_id INT UNSIGNED NOT NULL,
                amount INT UNSIGNED NOT NULL DEFAULT 0,
                method VARCHAR(60) NULL,
                reference VARCHAR(190) NULL,
                proof_path VARCHAR(255) NULL,
                status ENUM('submitted','approved','rejected') NOT NULL DEFAULT 'submitted',
                note VARCHAR(255) NULL,
                submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                verified_at TIMESTAMP NULL,
                KEY idx_payments_contract (contract_id),
                KEY idx_payments_status (status),
                CONSTRAINT fk_payments_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    },
];

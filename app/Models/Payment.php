<?php
namespace App\Models;

use App\Core\Model;

class Payment extends Model
{
    protected static string $table = 'payments';

    /** Latest payment record for a contract. */
    public static function latestForContract(int $contractId): ?array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM payments WHERE contract_id = ? ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetch() ?: null;
    }

    /** Contracts awaiting payment verification (with the submitted payment). */
    public static function pendingVerification(): array
    {
        return static::db()->query(
            "SELECT c.*, p.id AS payment_id, p.amount AS pay_amount, p.method, p.reference,
                    p.proof_path, p.submitted_at
             FROM contracts c
             JOIN payments p ON p.contract_id = c.id AND p.status = 'submitted'
             WHERE c.payment_status = 'submitted'
             ORDER BY p.submitted_at ASC"
        )->fetchAll();
    }

    public static function countPending(): int
    {
        return (int) static::db()->query("SELECT COUNT(*) FROM payments WHERE status='submitted'")->fetchColumn();
    }
}

<?php
namespace App\Models;

use App\Core\Model;

class ApiKey extends Model
{
    protected static string $table = 'api_keys';

    /** A hard-to-guess key reference, e.g. AK-7K3QF9 (unambiguous alphabet). */
    public static function nextNo(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = strlen($alphabet);
        $check = static::db()->prepare("SELECT 1 FROM api_keys WHERE key_no = ?");
        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) {
                $rand .= $alphabet[random_int(0, $len - 1)];
            }
            $no = 'AK-' . $rand;
            $check->execute([$no]);
        } while ($check->fetchColumn());
        return $no;
    }

    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM api_keys WHERE contract_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }

    /** All of a customer's API keys across every contract (with contract_no). */
    public static function forUser(int $userId): array
    {
        $stmt = static::db()->prepare(
            "SELECT k.*, c.contract_no FROM api_keys k
             JOIN contracts c ON c.id = k.contract_id
             WHERE c.user_id = ? ORDER BY k.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Provisioning queue with contract/customer info. */
    public static function queue(): array
    {
        return static::db()->query(
            "SELECT k.*, c.contract_no, c.customer_name
             FROM api_keys k
             JOIN contracts c ON c.id = k.contract_id
             ORDER BY FIELD(k.status,'requested','provisioning','active','failed'), k.id DESC"
        )->fetchAll();
    }

    public static function countByStatus(string $status): int
    {
        return static::count('status = ?', [$status]);
    }

    /**
     * Per-customer API-key summary: how many keys were asked for (not counting
     * cancelled/failed) vs how many are provisioned (active). A mismatch means
     * there is still work to do.
     */
    public static function summaryByCustomer(): array
    {
        return static::db()->query(
            "SELECT u.id, u.name AS customer, u.email,
                    SUM(CASE WHEN k.status IN ('requested','provisioning','active') THEN 1 ELSE 0 END) AS asked,
                    SUM(CASE WHEN k.status = 'active' THEN 1 ELSE 0 END) AS provided
             FROM api_keys k
             JOIN contracts c ON c.id = k.contract_id
             JOIN users u ON u.id = c.user_id
             GROUP BY u.id, u.name, u.email
             HAVING asked > 0
             ORDER BY (SUM(CASE WHEN k.status IN ('requested','provisioning','active') THEN 1 ELSE 0 END)
                       - SUM(CASE WHEN k.status = 'active' THEN 1 ELSE 0 END)) DESC, u.name ASC"
        )->fetchAll();
    }
}

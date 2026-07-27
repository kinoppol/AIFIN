<?php
namespace App\Models;

use App\Core\Model;

class ApiKey extends Model
{
    protected static string $table = 'api_keys';

    public static function nextNo(): string
    {
        $stmt = static::db()->query("SELECT key_no FROM api_keys ORDER BY id DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        $seq = $last ? ((int) substr($last, 3)) + 1 : 1001;
        return sprintf('AK-%04d', $seq);
    }

    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM api_keys WHERE contract_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$contractId]);
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
}

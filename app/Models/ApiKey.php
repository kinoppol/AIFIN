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
}

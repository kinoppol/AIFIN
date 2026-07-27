<?php
namespace App\Models;

use App\Core\Model;

class ExtensionRequest extends Model
{
    protected static string $table = 'extension_requests';

    public static function nextNo(): string
    {
        $year = date('Y');
        $stmt = static::db()->prepare(
            "SELECT ext_no FROM extension_requests WHERE ext_no LIKE ? ORDER BY ext_no DESC LIMIT 1"
        );
        $stmt->execute(["EX-{$year}-%"]);
        $last = $stmt->fetchColumn();
        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;
        return sprintf('EX-%s-%03d', $year, $seq);
    }

    public static function open(): array
    {
        return static::db()->query(
            "SELECT x.*, c.contract_no, c.customer_name, c.units_remaining
             FROM extension_requests x
             JOIN contracts c ON c.id = x.contract_id
             ORDER BY FIELD(x.status,'pending','reviewing','over_quota','approved','rejected'), x.id DESC"
        )->fetchAll();
    }

    public static function countPending(): int
    {
        return static::count("status IN ('pending','reviewing','over_quota')");
    }

    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM extension_requests WHERE contract_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
}

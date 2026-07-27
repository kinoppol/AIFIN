<?php
namespace App\Models;

use App\Core\Model;

class UnitLedger extends Model
{
    protected static string $table = 'unit_ledger';

    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM unit_ledger WHERE contract_id = ? ORDER BY id ASC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }
}

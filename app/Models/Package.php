<?php
namespace App\Models;

use App\Core\Model;

class Package extends Model
{
    protected static string $table = 'packages';

    public static function allSorted(): array
    {
        return static::db()
            ->query("SELECT * FROM packages ORDER BY sort_order ASC, id ASC")
            ->fetchAll();
    }

    /** Packages shown on the public pricing grid. */
    public static function sellable(): array
    {
        return static::db()
            ->query("SELECT * FROM packages WHERE status IN ('active','promo') ORDER BY sort_order ASC, id ASC")
            ->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM packages WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }
}

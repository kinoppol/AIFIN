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

    /** Sellable packages of a given kind ('ai' | 'gpu'). */
    public static function sellableKind(string $kind): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM packages WHERE status IN ('active','promo') AND kind = ? ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$kind]);
        return $stmt->fetchAll();
    }

    /** Packages shown on the public pricing grid (AI/M packages). */
    public static function sellable(): array
    {
        return self::sellableKind('ai');
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM packages WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }
}

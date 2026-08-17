<?php
namespace App\Models;

use App\Core\Model;

/**
 * Monthly AI plans (Claude Pro tier) a redeemed seat can be provisioned on.
 */
class AiPlan extends Model
{
    protected static string $table = 'ai_plans';

    /** @return array<int,array> */
    public static function allSorted(): array
    {
        return static::db()
            ->query("SELECT * FROM ai_plans ORDER BY status ASC, sort_order ASC, name ASC")
            ->fetchAll();
    }

    /** Plans a customer may pick. @return array<int,array> */
    public static function selectable(): array
    {
        return static::db()
            ->query("SELECT * FROM ai_plans WHERE status = 'active' ORDER BY sort_order ASC, name ASC")
            ->fetchAll();
    }

    public static function findByName(string $name): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM ai_plans WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    /** How many redemptions already reference this plan (blocks deletion). */
    public static function usageCount(int $id): int
    {
        $stmt = static::db()->prepare("SELECT COUNT(*) FROM redemptions WHERE plan_id = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $stmt = static::db()->prepare("DELETE FROM ai_plans WHERE id = ?");
        $stmt->execute([$id]);
    }
}

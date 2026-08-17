<?php
namespace App\Models;

use App\Core\Model;

/**
 * Emails a customer registered up-front for binding AI Pro seats.
 * Only these addresses may be used when redeeming units.
 */
class CustomerEmail extends Model
{
    protected static string $table = 'customer_emails';

    /**
     * All of a customer's emails, optionally filtered by a free-text search
     * over the address and its label.
     *
     * @return array<int,array>
     */
    public static function forUser(int $userId, string $search = ''): array
    {
        $sql = "SELECT * FROM customer_emails WHERE user_id = ?";
        $params = [$userId];
        if ($search !== '') {
            $sql .= " AND (email LIKE ? OR label LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $stmt = static::db()->prepare($sql . " ORDER BY status ASC, email ASC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Selectable (non-suspended) emails of a customer. @return array<int,array> */
    public static function activeForUser(int $userId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM customer_emails WHERE user_id = ? AND status = 'active' ORDER BY email ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Selectable emails of the owner of a contract. @return array<int,array> */
    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT ce.* FROM customer_emails ce
             JOIN contracts c ON c.user_id = ce.user_id
             WHERE c.id = ? AND ce.status = 'active' ORDER BY ce.email ASC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }

    public static function findForUser(int $userId, string $email): ?array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM customer_emails WHERE user_id = ? AND email = ? LIMIT 1"
        );
        $stmt->execute([$userId, $email]);
        return $stmt->fetch() ?: null;
    }

    /** Registered *and* not suspended — the condition for redeeming. */
    public static function isRegistered(int $userId, string $email): bool
    {
        $row = static::findForUser($userId, $email);
        return $row !== null && ($row['status'] ?? 'active') === 'active';
    }

    /** How many seats already use this email (blocks deletion). */
    public static function usageCount(int $userId, string $email): int
    {
        $stmt = static::db()->prepare(
            "SELECT COUNT(*) FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             WHERE c.user_id = ? AND r.email = ?"
        );
        $stmt->execute([$userId, $email]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Which AI plans each of a customer's emails is bound to, from the
     * redemptions made against it.
     *
     * @return array<string,array<int,array{plan_name:string,units:int,cnt:int,until:?string,active:int}>>
     *         keyed by email
     */
    public static function planUsageByEmail(int $userId): array
    {
        $stmt = static::db()->prepare(
            "SELECT r.email,
                    COALESCE(r.plan_name, 'ไม่ระบุแพ็กเกจ') AS plan_name,
                    COUNT(*) AS cnt,
                    SUM(r.units) AS units,
                    SUM(r.status = 'success') AS active,
                    MIN(r.provisioned_at) AS since,
                    MAX(r.expires_at) AS until
             FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             WHERE c.user_id = ?
             GROUP BY r.email, plan_name
             ORDER BY plan_name ASC"
        );
        $stmt->execute([$userId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['email']][] = [
                'plan_name' => (string) $row['plan_name'],
                'units'     => (int) $row['units'],
                'cnt'       => (int) $row['cnt'],
                'active'    => (int) $row['active'],
                'since'     => $row['since'],
                'until'     => $row['until'],
            ];
        }
        return $out;
    }

    public static function deleteForUser(int $id, int $userId): void
    {
        $stmt = static::db()->prepare("DELETE FROM customer_emails WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
}

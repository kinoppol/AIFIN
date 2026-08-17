<?php
namespace App\Models;

use App\Core\Model;

class Redemption extends Model
{
    protected static string $table = 'redemptions';

    public static function nextNo(): string
    {
        $stmt = static::db()->query(
            "SELECT redeem_no FROM redemptions ORDER BY id DESC LIMIT 1"
        );
        $last = $stmt->fetchColumn();
        $seq = $last ? ((int) substr($last, 3)) + 1 : 4801;
        return sprintf('RX-%04d', $seq);
    }

    public static function recent(int $limit = 10): array
    {
        $stmt = static::db()->prepare(
            "SELECT r.*, c.contract_no, c.customer_name
             FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             ORDER BY r.id DESC LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Provisioning queue (everything, newest first) with customer info. */
    public static function queue(): array
    {
        return static::db()->query(
            "SELECT r.*, c.contract_no, c.customer_name
             FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             ORDER BY FIELD(r.status,'pending','provisioning','awaiting_email','success','failed'), r.id DESC"
        )->fetchAll();
    }

    /**
     * Units redeemed against a contract within one calendar month.
     * $month is 'Y-m'; defaults to the current month.
     */
    public static function unitsInMonth(int $contractId, ?string $month = null): int
    {
        $month = $month ?: date('Y-m');
        $stmt = static::db()->prepare(
            "SELECT COALESCE(SUM(units), 0) FROM redemptions
             WHERE contract_id = ? AND DATE_FORMAT(requested_at, '%Y-%m') = ?"
        );
        $stmt->execute([$contractId, $month]);
        return (int) $stmt->fetchColumn();
    }

    public static function forContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM redemptions WHERE contract_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }

    /** All of a customer's redemptions across every contract (with contract_no). */
    public static function forUser(int $userId): array
    {
        $stmt = static::db()->prepare(
            "SELECT r.*, c.contract_no FROM redemptions r
             JOIN contracts c ON c.id = r.contract_id
             WHERE c.user_id = ? ORDER BY r.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Successful redemptions = provisioned seats (bound emails). */
    public static function seatsForContract(int $contractId): array
    {
        $stmt = static::db()->prepare(
            "SELECT email, plan_name, MAX(expires_at) AS until_date
             FROM redemptions
             WHERE contract_id = ? AND status = 'success'
             GROUP BY email, plan_name ORDER BY until_date DESC"
        );
        $stmt->execute([$contractId]);
        return $stmt->fetchAll();
    }

    public static function countByStatus(string $status): int
    {
        return static::count('status = ?', [$status]);
    }
}

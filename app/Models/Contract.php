<?php
namespace App\Models;

use App\Core\Model;

class Contract extends Model
{
    protected static string $table = 'contracts';

    public static function forUser(int $userId): array
    {
        return static::where('user_id', $userId, 'created_at DESC');
    }

    public static function findByNo(string $no): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM contracts WHERE contract_no = ?");
        $stmt->execute([$no]);
        return $stmt->fetch() ?: null;
    }

    /**
     * A hard-to-guess contract number, e.g. CT-2026-7K3QF9. The year stays
     * visible; the suffix is random (unambiguous alphabet, no 0/O/1/I) and
     * checked for uniqueness so contract codes can't be enumerated.
     */
    public static function nextNo(): string
    {
        $year = date('Y');
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $len = strlen($alphabet);
        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) {
                $rand .= $alphabet[random_int(0, $len - 1)];
            }
            $no = "CT-{$year}-{$rand}";
        } while (static::findByNo($no) !== null);
        return $no;
    }

    /** Contracts ending within N days that still have units. */
    public static function expiringWithin(int $days): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM contracts
             WHERE status <> 'expired'
               AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY end_date ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    /** Recompute a derived status from remaining units and end date. */
    public static function refreshStatus(int $id): void
    {
        $c = static::find($id);
        if (!$c) {
            return;
        }
        // Keep an explicit pending_ext/extended status if it was set and not expired.
        $daysLeft = (strtotime($c['end_date']) - strtotime(date('Y-m-d'))) / 86400;
        if ($daysLeft < 0) {
            $status = 'expired';
        } elseif ((int) $c['extension_months_used'] > 0) {
            $status = $daysLeft <= 90 ? 'expiring' : 'extended';
        } elseif ($daysLeft <= 90) {
            $status = 'expiring';
        } else {
            $status = 'active';
        }
        // Don't override a request that's awaiting review.
        if ($c['status'] === 'pending_ext' && $status !== 'expired') {
            return;
        }
        static::update($id, ['status' => $status]);
    }
}

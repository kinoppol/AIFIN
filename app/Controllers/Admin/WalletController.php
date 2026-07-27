<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class WalletController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $db = Database::instance();
        $unitDays = (int) config('app.unit_days', 30);

        // Aggregate per customer account.
        $wallets = $db->query(
            "SELECT u.id AS user_id, u.name AS customer, u.email,
                    COALESCE(SUM(c.units_total),0) AS bought,
                    COALESCE(SUM(c.units_total - c.units_remaining),0) AS used,
                    COALESCE(SUM(c.units_remaining),0) AS remaining,
                    COALESCE(SUM(c.gpu_total),0) AS gpu_bought,
                    COALESCE(SUM(c.gpu_remaining),0) AS gpu_remaining
             FROM users u
             JOIN contracts c ON c.user_id = u.id
             WHERE u.role='customer'
             GROUP BY u.id, u.name, u.email
             ORDER BY (COALESCE(SUM(c.units_total),0) + COALESCE(SUM(c.gpu_total),0)) DESC"
        )->fetchAll();

        $totalRemaining = (int) $db->query("SELECT COALESCE(SUM(units_remaining),0) FROM contracts")->fetchColumn();
        $totalGpuRemaining = (int) $db->query("SELECT COALESCE(SUM(gpu_remaining),0) FROM contracts")->fetchColumn();
        $expiring90 = (int) $db->query(
            "SELECT COALESCE(SUM(units_remaining),0) FROM contracts
             WHERE end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"
        )->fetchColumn();

        $this->render('admin/wallets', [
            'title'          => 'คลังหน่วยลูกค้า',
            'active'         => 'wallet',
            'wallets'          => $wallets,
            'totalRemaining'   => $totalRemaining,
            'totalGpuRemaining'=> $totalGpuRemaining,
            'liabilityDays'    => $totalRemaining * $unitDays,
            'expiring90'       => $expiring90,
            'badges'         => DashboardController::badges(),
        ]);
    }
}

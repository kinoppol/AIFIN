<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Models\Contract;
use App\Models\ExtensionRequest;
use App\Models\Redemption;

class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $db = Database::instance();

        $unitDays = (int) config('app.unit_days', 30);

        // KPIs
        $soldThisMonth = (int) $db->query(
            "SELECT COALESCE(SUM(amount),0) FROM unit_ledger
             WHERE type='purchase' AND MONTH(entry_date)=MONTH(CURDATE()) AND YEAR(entry_date)=YEAR(CURDATE())"
        )->fetchColumn();

        $redeemedThisMonth = (int) $db->query(
            "SELECT COALESCE(-SUM(amount),0) FROM unit_ledger
             WHERE type='redeem' AND MONTH(entry_date)=MONTH(CURDATE()) AND YEAR(entry_date)=YEAR(CURDATE())"
        )->fetchColumn();

        $activeContracts = Contract::count("status IN ('active','expiring','extended','pending_ext')");
        $expiringCount   = count(Contract::expiringWithin(90));

        // Prepaid revenue = units sold * price locked, summed from ledger purchases.
        $prepaidRevenue = (int) $db->query(
            "SELECT COALESCE(SUM(l.amount * c.price_per_m),0)
             FROM unit_ledger l JOIN contracts c ON c.id=l.contract_id
             WHERE l.type='purchase'"
        )->fetchColumn();

        $remainingUnits = (int) $db->query("SELECT COALESCE(SUM(units_remaining),0) FROM contracts")->fetchColumn();
        $liabilityDays = $remainingUnits * $unitDays;

        // 8-month sold-vs-redeemed chart.
        $chart = [];
        for ($i = 7; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("first day of -{$i} month"));
            $sold = (int) $db->query(
                "SELECT COALESCE(SUM(amount),0) FROM unit_ledger WHERE type='purchase' AND DATE_FORMAT(entry_date,'%Y-%m')='{$ym}'"
            )->fetchColumn();
            $red = (int) $db->query(
                "SELECT COALESCE(-SUM(amount),0) FROM unit_ledger WHERE type='redeem' AND DATE_FORMAT(entry_date,'%Y-%m')='{$ym}'"
            )->fetchColumn();
            $chart[] = ['ym' => $ym, 'sold' => $sold, 'redeemed' => $red];
        }
        $max = max(1, max(array_map(fn($c) => max($c['sold'], $c['redeemed']), $chart)));

        $this->render('admin/dashboard', [
            'title'    => 'แดชบอร์ด',
            'active'   => 'dash',
            'kpis'     => [
                'sold'      => $soldThisMonth,
                'redeemed'  => $redeemedThisMonth,
                'active'    => $activeContracts,
                'expiring'  => $expiringCount,
                'revenue'   => $prepaidRevenue,
                'liability' => $liabilityDays,
            ],
            'chart'    => $chart,
            'chartMax' => $max,
            'expiring' => Contract::expiringWithin(90),
            'redeems'  => Redemption::recent(5),
            'badges'   => $this->badges(),
        ]);
    }

    /** Sidebar counters shared by the admin layout. */
    public static function badges(): array
    {
        return [
            'ext'    => ExtensionRequest::countPending(),
            'redeem' => Redemption::countByStatus('pending') + Redemption::countByStatus('awaiting_email'),
            'gpu'    => \App\Models\ApiKey::countByStatus('requested'),
        ];
    }
}

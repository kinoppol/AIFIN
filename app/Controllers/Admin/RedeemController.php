<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Redemption;
use App\Services\ContractService;

class RedeemController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/redeem', [
            'title'   => 'คำขอแลกสิทธิ์ AI',
            'active'  => 'redeem',
            'queue'   => Redemption::queue(),
            'pending' => Redemption::countByStatus('pending'),
            'today'   => Redemption::count("DATE(requested_at)=CURDATE()"),
            'badges'  => DashboardController::badges(),
        ]);
    }

    public function updateStatus(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $id = (int) $this->input('id');
        $status = (string) $this->input('status');
        try {
            (new ContractService())->setRedemptionStatus($id, $status);
            $this->flash('success', 'อัปเดตสถานะคำขอแล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/redeem');
    }
}

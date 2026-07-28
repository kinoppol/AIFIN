<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Payment;
use App\Services\ContractService;

class PaymentController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/payments', [
            'title'   => 'ตรวจสอบการชำระเงิน',
            'active'  => 'payments',
            'queue'   => Payment::pendingVerification(),
            'badges'  => DashboardController::badges(),
        ]);
    }

    public function approve(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        try {
            (new ContractService())->approvePayment($contractId);
            $this->flash('success', 'อนุมัติการชำระเงินแล้ว สัญญาพร้อมใช้งาน');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->back('admin/payments');
    }

    public function reject(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        try {
            (new ContractService())->rejectPayment($contractId, trim((string) $this->input('note')));
            $this->flash('success', 'ปฏิเสธการชำระเงินแล้ว ลูกค้าต้องแจ้งชำระใหม่');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->back('admin/payments');
    }
}

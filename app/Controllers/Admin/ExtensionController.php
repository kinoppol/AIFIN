<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ExtensionRequest;
use App\Services\ContractService;

class ExtensionController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/extensions', [
            'title'   => 'คำขอขยายอายุสัญญา',
            'active'  => 'ext',
            'reqs'    => ExtensionRequest::open(),
            'maxExt'  => (int) config('app.max_extension_months', 6),
            'badges'  => DashboardController::badges(),
        ]);
    }

    public function approve(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $id = (int) $this->input('id');
        try {
            (new ContractService())->approveExtension($id);
            $this->flash('success', 'อนุมัติการขยายอายุสัญญาแล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/extensions');
    }

    public function reject(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $id = (int) $this->input('id');
        try {
            (new ContractService())->rejectExtension($id);
            $this->flash('success', 'ปฏิเสธคำขอแล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/extensions');
    }
}

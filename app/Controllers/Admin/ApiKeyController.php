<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ApiKey;
use App\Services\ContractService;

class ApiKeyController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/api_keys', [
            'title'    => 'GPU & API Keys',
            'active'   => 'gpu',
            'queue'    => ApiKey::queue(),
            'requested'=> ApiKey::countByStatus('requested'),
            'badges'   => DashboardController::badges(),
        ]);
    }

    public function provision(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $id = (int) $this->input('id');
        try {
            (new ContractService())->provisionApiKey(
                $id,
                trim((string) $this->input('base_url')),
                (string) $this->input('api_key')
            );
            $this->flash('success', 'ส่งมอบ API Key ให้ลูกค้าแล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/gpu');
    }

    public function updateStatus(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        try {
            (new ContractService())->setApiKeyStatus((int) $this->input('id'), (string) $this->input('status'));
            $this->flash('success', 'อัปเดตสถานะ API Key แล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/gpu');
    }
}

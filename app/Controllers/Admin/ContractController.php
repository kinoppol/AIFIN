<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\AiPlan;
use App\Models\ApiKey;
use App\Models\Contract;
use App\Models\CustomerEmail;
use App\Models\Payment;
use App\Models\ExtensionRequest;
use App\Models\Package;
use App\Models\Redemption;
use App\Models\UnitLedger;
use App\Models\User;
use App\Services\ContractService;

class ContractController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $contracts = Contract::all('created_at DESC');
        // Keep derived statuses fresh.
        foreach ($contracts as $c) {
            Contract::refreshStatus((int) $c['id']);
        }
        $contracts = Contract::all('created_at DESC');

        $counts = [
            'all'      => count($contracts),
            'active'   => Contract::count("status IN ('active','extended')"),
            'expiring' => Contract::count("status='expiring'"),
            'extended' => Contract::count("status='extended'"),
            'expired'  => Contract::count("status='expired'"),
        ];

        $this->render('admin/contracts', [
            'title'     => 'รายการสัญญา',
            'active'    => 'contracts',
            'contracts' => $contracts,
            'counts'    => $counts,
            'packages'  => Package::sellable(),
            'customers' => User::where('role', 'customer', 'name ASC'),
            'badges'    => DashboardController::badges(),
        ]);
    }

    public function show(): void
    {
        $this->requireAdmin();
        $id = (int) $this->input('id');
        $contract = Contract::find($id);
        if (!$contract) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('admin/contracts');
        }

        $this->render('admin/contract_detail', [
            'title'    => 'รายละเอียดสัญญา',
            'active'   => 'contracts',
            'c'        => $contract,
            'ledger'   => UnitLedger::forContract($id),
            'seats'    => Redemption::seatsForContract($id),
            'apikeys'  => ApiKey::forContract($id),
            'payment'  => Payment::latestForContract($id),
            'plans'    => AiPlan::selectable(),
            'emails'   => CustomerEmail::forContract($id),
            'exts'     => ExtensionRequest::forContract($id),
            'maxExt'   => (int) config('app.max_extension_months', 6),
            'badges'   => DashboardController::badges(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $customerId = (int) $this->input('user_id');
        $units = (int) $this->input('units');
        $price = (int) $this->input('price_per_m');
        $packageId = $this->input('package_id') ? (int) $this->input('package_id') : null;

        $customer = User::find($customerId);
        if (!$customer) {
            $this->flash('danger', 'กรุณาเลือกลูกค้า');
            $this->redirect('admin/contracts');
        }
        try {
            $svc = new ContractService();
            $bonus = $packageId ? (int) (Package::find($packageId)['bonus_gpu'] ?? 0) : 0;
            // Admin-created contracts are treated as already paid.
            $id = $svc->purchase($customerId, $customer['name'], $units, $price, $packageId, null, $bonus, 'paid');
            $this->flash('success', 'สร้างสัญญาและบันทึกการซื้อหน่วยเรียบร้อย');
            $this->redirect('admin/contracts/show?id=' . $id);
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('admin/contracts');
        }
    }

    public function redeem(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        $email = trim((string) $this->input('email'));
        $units = (int) $this->input('units');
        try {
            (new ContractService())->redeem($contractId, $email, $units, (int) $this->input('plan_id'));
            $this->flash('success', 'ส่งคำขอแลกเข้าคิวจัดหาสิทธิ์แล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('admin/contracts/show?id=' . $contractId);
    }
}

<?php
namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Contract;
use App\Models\ExtensionRequest;
use App\Models\Package;
use App\Models\Redemption;
use App\Models\UnitLedger;
use App\Services\ContractService;

class AccountController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $this->render('customer/dashboard', [
            'title'     => 'บัญชีของฉัน',
            'contracts' => Contract::forUser($userId),
            'packages'  => Package::sellable(),
        ], 'layouts/customer');
    }

    public function buyForm(): void
    {
        $this->requireAuth();
        $this->render('customer/buy', [
            'title'    => 'ซื้อหน่วย AI Pro',
            'packages' => Package::sellable(),
            'contracts'=> Contract::forUser(Auth::id()),
        ], 'layouts/customer');
    }

    public function buy(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $user = Auth::user();
        $packageId = (int) $this->input('package_id');
        $pkg = Package::find($packageId);
        if (!$pkg) {
            $this->flash('danger', 'ไม่พบแพ็กเกจที่เลือก');
            $this->redirect('account/buy');
        }
        try {
            $svc = new ContractService();
            $id = $svc->purchase(
                $user['id'],
                $user['name'],
                (int) $pkg['units'],
                (int) $pkg['sale_price'],
                $packageId,
                null
            );
            $this->flash('success', "ทำสัญญาสำเร็จ ได้รับ {$pkg['units']} M เข้าคลังแล้ว");
            $this->redirect('account/contract?id=' . $id);
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('account/buy');
        }
    }

    public function contract(): void
    {
        $this->requireAuth();
        $id = (int) $this->input('id');
        $c = Contract::find($id);
        if (!$c || (int) $c['user_id'] !== Auth::id()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        $this->render('customer/contract', [
            'title'   => 'สัญญา ' . $c['contract_no'],
            'c'       => $c,
            'ledger'  => UnitLedger::forContract($id),
            'seats'   => Redemption::seatsForContract($id),
            'redeems' => Redemption::forContract($id),
            'exts'    => ExtensionRequest::forContract($id),
            'maxExt'  => (int) config('app.max_extension_months', 6),
        ], 'layouts/customer');
    }

    public function redeem(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        $c = Contract::find($contractId);
        if (!$c || (int) $c['user_id'] !== Auth::id()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        try {
            (new ContractService())->redeem($contractId, trim((string) $this->input('email')), (int) $this->input('units'));
            $this->flash('success', 'ส่งคำขอแลกหน่วยเข้าคิวจัดหาสิทธิ์แล้ว');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
    }

    public function requestExtension(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        $c = Contract::find($contractId);
        if (!$c || (int) $c['user_id'] !== Auth::id()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        try {
            (new ContractService())->requestExtension($contractId, (int) $this->input('months'), trim((string) $this->input('reason')));
            $this->flash('success', 'ส่งคำขอขยายอายุสัญญาแล้ว รอผู้ดูแลอนุมัติ');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
    }
}

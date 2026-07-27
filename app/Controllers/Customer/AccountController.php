<?php
namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ApiKey;
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
            'title'     => 'สัญญาของฉัน',
            'contracts' => Contract::forUser($userId),
            'packages'  => Package::sellable(),
        ], 'layouts/customer');
    }

    public function ai(): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $this->render('customer/ai', [
            'title'   => 'AI ของฉัน',
            'seats'   => Redemption::forUser($userId),
            'apikeys' => ApiKey::forUser($userId),
        ], 'layouts/customer');
    }

    public function buyForm(): void
    {
        $this->requireAuth();
        $this->render('customer/buy', [
            'title'       => 'ซื้อหน่วย AI Pro / GPU',
            'packages'    => Package::sellableKind('ai'),
            'gpuPackages' => Package::sellableKind('gpu'),
            'contracts'   => Contract::forUser(Auth::id()),
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
                null,
                (int) $pkg['bonus_gpu']
            );
            $bonusMsg = (int) $pkg['bonus_gpu'] > 0 ? " + แถมการ์ด GPU {$pkg['bonus_gpu']} ตัว" : '';
            $this->flash('success', "ทำสัญญาสำเร็จ ได้รับ {$pkg['units']} M{$bonusMsg} เข้าคลังแล้ว");
            $this->redirect('account/contract?id=' . $id);
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('account/buy');
        }
    }

    public function buyGpu(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $user = Auth::user();
        $packageId = (int) $this->input('package_id');
        $pkg = Package::find($packageId);
        if (!$pkg || $pkg['kind'] !== 'gpu') {
            $this->flash('danger', 'ไม่พบแพ็กเกจ GPU ที่เลือก');
            $this->redirect('account/buy');
        }
        try {
            $id = (new ContractService())->purchaseGpu(
                $user['id'],
                $user['name'],
                (int) $pkg['units'],
                (int) $pkg['sale_price'],
                $packageId,
                null
            );
            $this->flash('success', "ทำสัญญา GPU สำเร็จ ได้รับการ์ด {$pkg['units']} ตัวเข้าคลังแล้ว");
            $this->redirect('account/contract?id=' . $id);
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
            $this->redirect('account/buy');
        }
    }

    public function requestApiKey(): void
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
            (new ContractService())->requestApiKey($contractId, trim((string) $this->input('label')), max(1, (int) $this->input('gpu_units', 1)));
            $this->flash('success', 'ส่งคำขอสร้าง API Key แล้ว รอผู้ดูแลจัดหา');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
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
            'apikeys' => ApiKey::forContract($id),
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

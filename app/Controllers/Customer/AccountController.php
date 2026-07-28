<?php
namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ApiKey;
use App\Models\Contract;
use App\Models\ExtensionRequest;
use App\Models\Package;
use App\Models\Payment;
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
            $this->flash('success', 'สร้างสัญญาแล้ว — กรุณาชำระเงินและแจ้งหลักฐานการชำระเพื่อเปิดใช้งาน');
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
            $this->flash('success', 'สร้างสัญญา GPU แล้ว — กรุณาชำระเงินและแจ้งหลักฐานการชำระเพื่อเปิดใช้งาน');
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
            'payment' => Payment::latestForContract($id),
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

    public function submitPayment(): void
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
            $proofPath = null;
            if (!empty($_FILES['proof']['name']) && (($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
                $proofPath = $this->storeProof($_FILES['proof']);
            }
            (new ContractService())->submitPayment(
                $contractId,
                trim((string) $this->input('method')),
                trim((string) $this->input('reference')),
                $proofPath
            );
            $this->flash('success', 'แจ้งชำระเงินแล้ว รอผู้ดูแลตรวจสอบและอนุมัติ');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
    }

    /** Stream a payment proof file to its owner (or an admin). */
    public function proof(): void
    {
        $this->requireAuth();
        $p = Payment::find((int) $this->input('id'));
        if (!$p || empty($p['proof_path'])) {
            http_response_code(404);
            exit('ไม่พบไฟล์');
        }
        $c = Contract::find((int) $p['contract_id']);
        if (!$c || ((int) $c['user_id'] !== Auth::id() && !Auth::isAdmin())) {
            http_response_code(403);
            exit('Forbidden');
        }
        $path = APP_ROOT . '/storage/uploads/' . $p['proof_path'];
        if (!is_file($path)) {
            http_response_code(404);
            exit('ไม่พบไฟล์');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="proof-' . (int) $p['id'] . '"');
        readfile($path);
        exit;
    }

    /** Validate and store an uploaded proof; returns a path relative to uploads/. */
    private function storeProof(array $file): string
    {
        $allowed = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'application/pdf' => 'pdf',
        ];
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException('ไฟล์หลักฐานต้องไม่เกิน 5MB');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('รองรับเฉพาะรูปภาพ (JPG/PNG/WEBP/GIF) หรือ PDF');
        }
        $dir = APP_ROOT . '/storage/uploads/payments';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new \RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ');
        }
        return 'payments/' . $name;
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

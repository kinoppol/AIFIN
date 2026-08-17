<?php
namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\AiPlan;
use App\Models\ApiKey;
use App\Models\Contract;
use App\Models\CustomerEmail;
use App\Models\CustomerEmailDomain;
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
        $userId = Auth::ownerId();
        $this->render('customer/dashboard', [
            'title'     => 'สัญญาของฉัน',
            'contracts' => Contract::forUser($userId),
            'packages'  => Package::sellable(),
        ], 'layouts/customer');
    }

    public function ai(): void
    {
        $this->requireAuth();
        $userId = Auth::ownerId();
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
            'contracts'   => Contract::forUser(Auth::ownerId()),
        ], 'layouts/customer');
    }

    public function buy(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $owner = ['id' => Auth::ownerId(), 'name' => Auth::ownerName()];
        $packageId = (int) $this->input('package_id');
        $pkg = Package::find($packageId);
        if (!$pkg) {
            $this->flash('danger', 'ไม่พบแพ็กเกจที่เลือก');
            $this->redirect('account/buy');
        }
        try {
            $svc = new ContractService();
            $id = $svc->purchase(
                $owner['id'],
                $owner['name'],
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
        $owner = ['id' => Auth::ownerId(), 'name' => Auth::ownerName()];
        $packageId = (int) $this->input('package_id');
        $pkg = Package::find($packageId);
        if (!$pkg || $pkg['kind'] !== 'gpu') {
            $this->flash('danger', 'ไม่พบแพ็กเกจ GPU ที่เลือก');
            $this->redirect('account/buy');
        }
        try {
            $id = (new ContractService())->purchaseGpu(
                $owner['id'],
                $owner['name'],
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
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
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

    /** Set how many units may be redeemed per calendar month (0 = ไม่จำกัด). */
    public function setRedeemLimit(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        $c = Contract::find($contractId);
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        try {
            $limit = max(0, (int) $this->input('monthly_redeem_limit'));
            (new ContractService())->setMonthlyRedeemLimit($contractId, $limit);
            $this->flash('success', $limit > 0
                ? "ตั้งค่าจำกัดการแลกไว้ที่ {$limit} หน่วยต่อเดือนแล้ว"
                : 'ยกเลิกค่าจำกัดการแลกต่อเดือนแล้ว (ไม่จำกัด)');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
    }

    /** Manage the emails that may be bound to an AI Pro seat. */
    public function emails(): void
    {
        $this->requireAuth();
        $userId = Auth::ownerId();
        $q = trim((string) $this->input('q'));
        $emails = CustomerEmail::forUser($userId, $q);
        $planUsage = CustomerEmail::planUsageByEmail($userId);
        foreach ($emails as &$row) {
            $row['used']  = CustomerEmail::usageCount($userId, $row['email']);
            $row['plans'] = $planUsage[$row['email']] ?? [];
        }
        unset($row);
        $this->render('customer/emails', [
            'title'   => 'อีเมลที่ลงทะเบียน',
            'emails'  => $emails,
            'q'       => $q,
            'total'   => CustomerEmail::count('user_id = ?', [$userId]),
            'domains' => CustomerEmailDomain::forUser($userId),
        ], 'layouts/customer');
    }

    public function addEmail(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $email = strtolower(trim((string) $this->input('email')));
        $label = trim((string) $this->input('label'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('danger', 'อีเมลไม่ถูกต้อง');
        } elseif (!CustomerEmailDomain::allows($userId, $email)) {
            $this->flash('danger', $this->domainError($userId));
        } elseif (CustomerEmail::isRegistered($userId, $email)) {
            $this->flash('danger', 'อีเมลนี้ลงทะเบียนไว้แล้ว');
        } else {
            CustomerEmail::insert([
                'user_id' => $userId,
                'email'   => $email,
                'label'   => $label !== '' ? $label : null,
            ]);
            $this->flash('success', 'ลงทะเบียนอีเมลแล้ว');
        }
        $this->redirect($this->backTo('account/emails'));
    }

    /** Edit an email's address/label. The address is locked once seats use it. */
    public function updateEmail(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $id = (int) $this->input('id');
        $row = CustomerEmail::find($id);
        if (!$row || (int) $row['user_id'] !== $userId) {
            $this->flash('danger', 'ไม่พบอีเมล');
            $this->redirect('account/emails');
        }
        $email = strtolower(trim((string) $this->input('email')));
        $label = trim((string) $this->input('label'));
        $used  = CustomerEmail::usageCount($userId, $row['email']);
        $data  = ['label' => $label !== '' ? $label : null];

        if ($email !== '' && $email !== $row['email']) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->flash('danger', 'อีเมลไม่ถูกต้อง');
                $this->redirect('account/emails');
            }
            if (!CustomerEmailDomain::allows($userId, $email)) {
                $this->flash('danger', $this->domainError($userId));
                $this->redirect('account/emails');
            }
            if ($used > 0) {
                $this->flash('danger', 'อีเมลนี้ถูกใช้แลกสิทธิ์แล้ว แก้ไขที่อยู่อีเมลไม่ได้ (แก้ไขชื่อเรียกได้)');
                $this->redirect('account/emails');
            }
            if (CustomerEmail::findForUser($userId, $email)) {
                $this->flash('danger', 'อีเมลนี้ลงทะเบียนไว้แล้ว');
                $this->redirect('account/emails');
            }
            $data['email'] = $email;
        }
        CustomerEmail::update($id, $data);
        $this->flash('success', 'บันทึกการแก้ไขแล้ว');
        $this->redirect('account/emails');
    }

    /** Suspend / resume an email (suspended addresses can't be redeemed against). */
    public function toggleEmailStatus(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $id = (int) $this->input('id');
        $row = CustomerEmail::find($id);
        if (!$row || (int) $row['user_id'] !== $userId) {
            $this->flash('danger', 'ไม่พบอีเมล');
            $this->redirect('account/emails');
        }
        $suspend = ($row['status'] ?? 'active') === 'active';
        CustomerEmail::update($id, ['status' => $suspend ? 'suspended' : 'active']);
        $this->flash('success', $suspend ? 'ระงับการใช้งานอีเมลแล้ว' : 'เปิดใช้งานอีเมลอีกครั้งแล้ว');
        $this->redirect('account/emails');
    }

    public function deleteEmail(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $id = (int) $this->input('id');
        $row = CustomerEmail::find($id);
        if (!$row || (int) $row['user_id'] !== $userId) {
            $this->flash('danger', 'ไม่พบอีเมล');
        } elseif (CustomerEmail::usageCount($userId, $row['email']) > 0) {
            $this->flash('danger', 'อีเมลนี้ถูกใช้แลกสิทธิ์แล้ว ไม่สามารถลบได้');
        } else {
            CustomerEmail::deleteForUser($id, $userId);
            $this->flash('success', 'ลบอีเมลแล้ว');
        }
        $this->redirect('account/emails');
    }

    /** Restrict which domains may be registered (empty list = ทุกโดเมน). */
    public function addDomain(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $domain = CustomerEmailDomain::normalize((string) $this->input('domain'));
        if (!CustomerEmailDomain::isValid($domain)) {
            $this->flash('danger', 'โดเมนไม่ถูกต้อง (ตัวอย่าง: rvc.ac.th)');
        } elseif (CustomerEmailDomain::count('user_id = ? AND domain = ?', [$userId, $domain])) {
            $this->flash('danger', 'โดเมนนี้อยู่ในรายการแล้ว');
        } else {
            CustomerEmailDomain::insert(['user_id' => $userId, 'domain' => $domain]);
            $this->flash('success', "จำกัดการลงทะเบียนอีเมลไว้ที่โดเมน {$domain} แล้ว");
        }
        $this->redirect('account/emails');
    }

    public function deleteDomain(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $userId = Auth::ownerId();
        $id = (int) $this->input('id');
        $row = CustomerEmailDomain::find($id);
        if (!$row || (int) $row['user_id'] !== $userId) {
            $this->flash('danger', 'ไม่พบโดเมน');
        } else {
            CustomerEmailDomain::deleteForUser($id, $userId);
            $this->flash('success', 'ลบโดเมนออกจากรายการแล้ว');
        }
        $this->redirect('account/emails');
    }

    /** Message listing the domains an address must belong to. */
    private function domainError(int $userId): string
    {
        return 'ลงทะเบียนได้เฉพาะอีเมลของโดเมน ' . implode(', ', CustomerEmailDomain::listForUser($userId)) . ' เท่านั้น';
    }

    /** Allow the add-email form to return to the page it was opened from. */
    private function backTo(string $default): string
    {
        $to = trim((string) $this->input('return'));
        return $to !== '' && strpos($to, 'account') === 0 ? $to : $default;
    }

    public function contract(): void
    {
        $this->requireAuth();
        $id = (int) $this->input('id');
        $c = Contract::find($id);
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
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
            'plans'   => AiPlan::selectable(),
            'emails'  => CustomerEmail::activeForUser(Auth::ownerId()),
            'maxExt'  => (int) config('app.max_extension_months', 6),
        ], 'layouts/customer');
    }

    public function redeem(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $contractId = (int) $this->input('contract_id');
        $c = Contract::find($contractId);
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        try {
            (new ContractService())->redeem($contractId, trim((string) $this->input('email')), (int) $this->input('units'), (int) $this->input('plan_id'));
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
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
            $this->flash('danger', 'ไม่พบสัญญา');
            $this->redirect('account');
        }
        try {
            $proofPath = null;
            if (!empty($_FILES['proof']['name']) && (($_FILES['proof']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK)) {
                $proofPath = $this->storeProof($_FILES['proof']);
            }
            $paidRaw = trim((string) $this->input('paid_at'));
            $paidAt = $paidRaw !== '' ? date('Y-m-d H:i:s', strtotime($paidRaw)) : null;
            (new ContractService())->submitPayment(
                $contractId,
                trim((string) $this->input('method')),
                trim((string) $this->input('reference')),
                $proofPath,
                (int) $this->input('amount'),
                $paidAt
            );
            $this->flash('success', 'แจ้งชำระเงินแล้ว รอผู้ดูแลตรวจสอบและอนุมัติ');
        } catch (\Throwable $e) {
            $this->flash('danger', $e->getMessage());
        }
        $this->redirect('account/contract?id=' . $contractId);
    }

    /** Full-page, printable A4 quotation for a contract (owner or admin). */
    public function quotation(): void
    {
        $this->requireAuth();
        $id = (int) $this->input('id');
        $c = Contract::find($id);
        if (!$c || ((int) $c['user_id'] !== Auth::ownerId() && !Auth::isAdmin())) {
            http_response_code(404);
            exit('ไม่พบสัญญา');
        }
        // Standalone document (its own HTML) — no app layout.
        $this->render('customer/quotation', [
            'c'        => $c,
            'autoPrint'=> (string) $this->input('print') === '1',
        ], null);
    }

    /** Full-page, printable brief receipt for a purchase ledger entry. */
    public function receipt(): void
    {
        $this->requireAuth();
        $l = UnitLedger::find((int) $this->input('id'));
        if (!$l || $l['type'] !== 'purchase') {
            http_response_code(404);
            exit('ไม่พบรายการ');
        }
        $c = Contract::find((int) $l['contract_id']);
        if (!$c || ((int) $c['user_id'] !== Auth::ownerId() && !Auth::isAdmin())) {
            http_response_code(404);
            exit('ไม่พบรายการ');
        }
        $this->render('customer/receipt', [
            'c'         => $c,
            'l'         => $l,
            'total'     => (int) $l['amount'] * (int) $c['price_per_m'],
            'autoPrint' => (string) $this->input('print') === '1',
        ], null);
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
        if (!$c || ((int) $c['user_id'] !== Auth::ownerId() && !Auth::isAdmin())) {
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
        if (!$c || (int) $c['user_id'] !== Auth::ownerId()) {
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

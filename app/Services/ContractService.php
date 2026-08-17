<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Config;
use App\Models\AiPlan;
use App\Models\ApiKey;
use App\Models\Contract;
use App\Models\CustomerEmail;
use App\Models\Payment;
use App\Models\ExtensionRequest;
use App\Models\Package;
use App\Models\Redemption;
use App\Models\UnitLedger;
use RuntimeException;

/**
 * All contract/unit business rules live here so controllers stay thin and the
 * invariants (unit balances, extension quota, ledger consistency) are enforced
 * in one place, inside transactions.
 */
class ContractService
{
    private function db(): \PDO
    {
        return Database::instance();
    }

    private function unitDays(): int
    {
        return (int) Config::get('app.unit_days', 30);
    }

    private function contractMonths(): int
    {
        return (int) Config::get('app.contract_months', 12);
    }

    private function maxExtension(): int
    {
        return (int) Config::get('app.max_extension_months', 6);
    }

    /**
     * Buy units for a customer. If $contractId is null a new contract is
     * created; otherwise units are appended to the existing contract's wallet.
     * Price per M is locked at purchase time.
     *
     * @return int contract id
     */
    public function purchase(int $userId, string $customerName, int $units, int $pricePerM, ?int $packageId = null, ?int $contractId = null, int $bonusGpu = 0, string $paymentStatus = 'unpaid'): int
    {
        if ($units <= 0) {
            throw new RuntimeException('จำนวนหน่วยต้องมากกว่า 0');
        }
        $db = $this->db();
        $db->beginTransaction();
        try {
            $today = date('Y-m-d');
            if ($contractId === null) {
                $start = $today;
                $baseEnd = date('Y-m-d', strtotime("{$start} +{$this->contractMonths()} months"));
                $contractId = Contract::insert([
                    'contract_no'    => Contract::nextNo(),
                    'user_id'        => $userId,
                    'package_id'     => $packageId,
                    'customer_name'  => $customerName,
                    'units_total'    => $units,
                    'units_remaining' => $units,
                    'unit_days'      => $this->unitDays(),
                    'price_per_m'    => $pricePerM,
                    'start_date'     => $start,
                    'base_end_date'  => $baseEnd,
                    'end_date'       => $baseEnd,
                    'status'         => 'active',
                    'payment_status' => $paymentStatus,
                    'total_amount'   => $units * $pricePerM,
                ]);
                $balance = $units;
            } else {
                $c = Contract::find($contractId);
                if (!$c) {
                    throw new RuntimeException('ไม่พบสัญญา');
                }
                $balance = (int) $c['units_remaining'] + $units;
                Contract::update($contractId, [
                    'units_total'     => (int) $c['units_total'] + $units,
                    'units_remaining' => $balance,
                ]);
            }

            $pkgName = $packageId ? (Package::find($packageId)['name'] ?? 'แพ็กเกจ') : 'กำหนดเอง';
            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => $today,
                'description' => "ซื้อหน่วยตามสัญญา ({$pkgName} {$units} M @ " . baht($pricePerM) . ")",
                'amount'      => $units,
                'balance'     => $balance,
                'type'        => 'purchase',
            ]);

            // Bundled/bonus GPU cards from an AI package.
            if ($bonusGpu > 0) {
                $cc = Contract::find($contractId);
                $newGpu = (int) $cc['gpu_remaining'] + $bonusGpu;
                Contract::update($contractId, [
                    'gpu_total'     => (int) $cc['gpu_total'] + $bonusGpu,
                    'gpu_remaining' => $newGpu,
                ]);
                UnitLedger::insert([
                    'contract_id' => $contractId,
                    'entry_date'  => $today,
                    'description' => "แถมการ์ด GPU {$bonusGpu} ตัว (แพ็กเกจ {$pkgName})",
                    'amount'      => 0,
                    'balance'     => $balance,
                    'gpu_amount'  => $bonusGpu,
                    'gpu_balance' => $newGpu,
                    'type'        => 'adjust',
                ]);
            }

            $db->commit();
            return $contractId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Redeem units from a contract against an email. Subtracts units, writes the
     * ledger entry, and enqueues a redemption for provisioning.
     */
    public function redeem(int $contractId, string $email, int $units, int $planId = 0): int
    {
        if ($units <= 0) {
            throw new RuntimeException('จำนวนหน่วยต้องมากกว่า 0');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('อีเมลไม่ถูกต้อง');
        }
        $db = $this->db();
        $db->beginTransaction();
        try {
            $c = Contract::find($contractId);
            if (!$c) {
                throw new RuntimeException('ไม่พบสัญญา');
            }
            if (($c['payment_status'] ?? 'paid') !== 'paid') {
                throw new RuntimeException('สัญญานี้ยังไม่ได้รับการอนุมัติการชำระเงิน');
            }
            // Once the contract has expired, remaining units can no longer be
            // redeemed (but access already redeemed keeps running to completion).
            if (strtotime($c['end_date']) < strtotime(date('Y-m-d'))) {
                throw new RuntimeException('สัญญาหมดอายุแล้ว ไม่สามารถแลกหน่วยเพิ่มได้');
            }
            $cap = (int) Config::get('app.max_redeem_units', 12);
            if ($units > $cap) {
                throw new RuntimeException("แลกได้ครั้งละไม่เกิน {$cap} หน่วย");
            }
            if ((int) $c['units_remaining'] < $units) {
                throw new RuntimeException('หน่วยคงเหลือไม่พอสำหรับการแลก');
            }
            // Customer-set monthly cap (0 = no cap).
            $monthlyLimit = (int) ($c['monthly_redeem_limit'] ?? 0);
            if ($monthlyLimit > 0) {
                $usedThisMonth = Redemption::unitsInMonth($contractId);
                $left = $monthlyLimit - $usedThisMonth;
                if ($units > $left) {
                    throw new RuntimeException(
                        "เกินค่าจำกัดการแลกต่อเดือนที่ตั้งไว้ ({$monthlyLimit} หน่วย/เดือน) — เดือนนี้แลกไปแล้ว {$usedThisMonth} หน่วย เหลือแลกได้อีก " . max(0, $left) . ' หน่วย'
                    );
                }
            }
            // The email must have been registered by the contract owner beforehand.
            $ownerId = (int) ($c['user_id'] ?? 0);
            if ($ownerId <= 0 || !CustomerEmail::isRegistered($ownerId, $email)) {
                throw new RuntimeException('อีเมลนี้ยังไม่ได้ลงทะเบียน กรุณาลงทะเบียนอีเมลก่อนแลกสิทธิ์');
            }
            // Which monthly AI plan the seat should be provisioned on.
            $plan = $planId > 0 ? AiPlan::find($planId) : null;
            if (!$plan) {
                throw new RuntimeException('กรุณาเลือกแพ็กเกจ AI ที่ต้องการ');
            }
            if (($plan['status'] ?? 'active') !== 'active') {
                throw new RuntimeException('แพ็กเกจ AI นี้ถูกระงับการใช้งานแล้ว');
            }
            $days = $units * (int) $c['unit_days'];
            $balance = (int) $c['units_remaining'] - $units;

            Contract::update($contractId, ['units_remaining' => $balance]);

            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => date('Y-m-d'),
                'description' => "แลกสิทธิ์ {$plan['name']} → {$email} ({$days} วัน)",
                'amount'      => -$units,
                'balance'     => $balance,
                'type'        => 'redeem',
            ]);

            // expires_at is set when provisioned (usage clock starts then).
            $id = Redemption::insert([
                'redeem_no'   => Redemption::nextNo(),
                'contract_id' => $contractId,
                'email'       => $email,
                'plan_id'     => (int) $plan['id'],
                'plan_name'   => $plan['name'],
                'units'       => $units,
                'days'        => $days,
                'status'      => 'pending',
            ]);

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Admin edit of a contract's descriptive fields.
     *
     * Unit balances are *not* editable here — they are owned by the ledger.
     * Dates are validated against each other and the granted extension months,
     * and the derived status is refreshed afterwards.
     *
     * @param array{customer_name?:string,price_per_m?:int,start_date?:string,base_end_date?:string,end_date?:string,total_amount?:int,monthly_redeem_limit?:int} $in
     */
    public function updateContract(int $contractId, array $in): void
    {
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        $name = trim((string) ($in['customer_name'] ?? $c['customer_name']));
        if ($name === '') {
            throw new RuntimeException('กรุณากรอกชื่อลูกค้า');
        }
        $start   = ($in['start_date']    ?? '') ?: $c['start_date'];
        $baseEnd = ($in['base_end_date'] ?? '') ?: $c['base_end_date'];
        $end     = ($in['end_date']      ?? '') ?: $c['end_date'];
        foreach ([$start, $baseEnd, $end] as $d) {
            if (!strtotime((string) $d)) {
                throw new RuntimeException('รูปแบบวันที่ไม่ถูกต้อง');
            }
        }
        if (strtotime($baseEnd) <= strtotime($start)) {
            throw new RuntimeException('วันสิ้นสุด (เดิม) ต้องอยู่หลังวันเริ่มสัญญา');
        }
        if (strtotime($end) < strtotime($baseEnd)) {
            throw new RuntimeException('วันสิ้นสุดปัจจุบันต้องไม่ก่อนวันสิ้นสุดเดิม');
        }
        // The gap between base end and current end is the granted extension.
        $maxEnd = date('Y-m-d', strtotime("{$baseEnd} +" . (int) $c['extension_months_used'] . ' months'));
        if (strtotime($end) > strtotime($maxEnd)) {
            throw new RuntimeException(
                'วันสิ้นสุดปัจจุบันเกินโควตาการขยายที่อนุมัติไว้ (' . (int) $c['extension_months_used'] . ' เดือน)'
            );
        }
        $price = isset($in['price_per_m']) ? max(0, (int) $in['price_per_m']) : (int) $c['price_per_m'];
        $total = isset($in['total_amount']) ? max(0, (int) $in['total_amount']) : (int) $c['total_amount'];
        $limit = isset($in['monthly_redeem_limit']) ? max(0, (int) $in['monthly_redeem_limit']) : (int) ($c['monthly_redeem_limit'] ?? 0);

        Contract::update($contractId, [
            'customer_name'        => $name,
            'price_per_m'          => $price,
            'total_amount'         => $total,
            'start_date'           => date('Y-m-d', strtotime($start)),
            'base_end_date'        => date('Y-m-d', strtotime($baseEnd)),
            'end_date'             => date('Y-m-d', strtotime($end)),
            'monthly_redeem_limit' => $limit,
        ]);
        Contract::refreshStatus($contractId);
    }

    /**
     * Customer-set cap on how many units may be redeemed per calendar month
     * (0 = unlimited). Guards a wallet from being drained too quickly.
     */
    public function setMonthlyRedeemLimit(int $contractId, int $limit): void
    {
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        if ($limit < 0) {
            throw new RuntimeException('ค่าจำกัดต้องไม่ติดลบ');
        }
        // A cap tighter than app.max_redeem_units is allowed — it just limits
        // each request further.
        Contract::update($contractId, ['monthly_redeem_limit' => $limit]);
    }

    /** Advance a redemption's provisioning status (admin queue actions). */
    public function setRedemptionStatus(int $redemptionId, string $status): void
    {
        $allowed = ['pending', 'provisioning', 'awaiting_email', 'success', 'failed'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('สถานะไม่ถูกต้อง');
        }
        $data = ['status' => $status];
        if ($status === 'success') {
            // Usage is counted from the provision date (units * unit_days),
            // and may run past the contract's own end date.
            $r = Redemption::find($redemptionId);
            $data['provisioned_at'] = date('Y-m-d H:i:s');
            $data['expires_at'] = date('Y-m-d', strtotime('+' . (int) ($r['days'] ?? 0) . ' days'));
        }
        Redemption::update($redemptionId, $data);
    }

    /**
     * Create an extension request. Enforces the total-quota cap: a request that
     * would push used + requested past the max is flagged over_quota (blocked)
     * rather than accepted.
     */
    public function requestExtension(int $contractId, int $months, string $reason): int
    {
        if ($months <= 0) {
            throw new RuntimeException('จำนวนเดือนต้องมากกว่า 0');
        }
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        // Extensions can only be requested inside the renewal window.
        $window = (int) Config::get('app.extension_window_days', 180);
        $daysLeft = (int) floor((strtotime($c['end_date']) - strtotime(date('Y-m-d'))) / 86400);
        if ($daysLeft >= $window) {
            throw new RuntimeException("ขอขยายอายุได้เมื่อสัญญาเหลือน้อยกว่า {$window} วันเท่านั้น");
        }
        $used = (int) $c['extension_months_used'];
        $overQuota = ($used + $months) > $this->maxExtension();
        $newEnd = $overQuota ? null : date('Y-m-d', strtotime("{$c['end_date']} +{$months} months"));

        $id = ExtensionRequest::insert([
            'ext_no'             => ExtensionRequest::nextNo(),
            'contract_id'        => $contractId,
            'months_requested'   => $months,
            'months_used_before' => $used,
            'reason'             => $reason,
            'new_end_date'       => $newEnd,
            'status'             => $overQuota ? 'over_quota' : 'pending',
        ]);

        if (!$overQuota && $c['status'] !== 'expired') {
            Contract::update($contractId, ['status' => 'pending_ext']);
        }
        return $id;
    }

    /**
     * Approve an extension: extends the contract end date and consumes quota.
     */
    public function approveExtension(int $extId): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $x = ExtensionRequest::find($extId);
            if (!$x) {
                throw new RuntimeException('ไม่พบคำขอ');
            }
            if ($x['status'] === 'over_quota') {
                throw new RuntimeException('คำขอเกินโควตา ไม่สามารถอนุมัติได้');
            }
            $c = Contract::find((int) $x['contract_id']);
            if (!$c) {
                throw new RuntimeException('ไม่พบสัญญา');
            }
            $used = (int) $c['extension_months_used'] + (int) $x['months_requested'];
            if ($used > $this->maxExtension()) {
                throw new RuntimeException('เกินโควตาการขยายอายุรวม');
            }
            $newEnd = date('Y-m-d', strtotime("{$c['end_date']} +{$x['months_requested']} months"));

            Contract::update((int) $x['contract_id'], [
                'end_date'              => $newEnd,
                'extension_months_used' => $used,
                'status'                => 'extended',
            ]);
            ExtensionRequest::update($extId, [
                'status'       => 'approved',
                'new_end_date' => $newEnd,
                'decided_at'   => date('Y-m-d H:i:s'),
            ]);
            UnitLedger::insert([
                'contract_id' => (int) $x['contract_id'],
                'entry_date'  => date('Y-m-d'),
                'description' => "อนุมัติขยายอายุสัญญา +{$x['months_requested']} เดือน",
                'amount'      => 0,
                'balance'     => (int) $c['units_remaining'],
                'type'        => 'extension',
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function rejectExtension(int $extId): void
    {
        $x = ExtensionRequest::find($extId);
        if (!$x) {
            throw new RuntimeException('ไม่พบคำขอ');
        }
        ExtensionRequest::update($extId, [
            'status'     => 'rejected',
            'decided_at' => date('Y-m-d H:i:s'),
        ]);
        // Return the contract to a normal derived status.
        Contract::update((int) $x['contract_id'], ['status' => 'active']);
        Contract::refreshStatus((int) $x['contract_id']);
    }

    // --- Payment / approval -------------------------------------------------

    /** Customer notifies payment (amount, when paid, and an optional proof file). */
    public function submitPayment(int $contractId, string $method, string $reference, ?string $proofPath, int $amount = 0, ?string $paidAt = null): int
    {
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        if (($c['payment_status'] ?? 'paid') === 'paid') {
            throw new RuntimeException('สัญญานี้ได้รับการอนุมัติแล้ว');
        }
        $id = Payment::insert([
            'contract_id' => $contractId,
            'amount'      => $amount > 0 ? $amount : (int) $c['total_amount'],
            'method'      => $method !== '' ? $method : null,
            'reference'   => $reference !== '' ? $reference : null,
            'paid_at'     => $paidAt ?: null,
            'proof_path'  => $proofPath,
            'status'      => 'submitted',
        ]);
        Contract::update($contractId, ['payment_status' => 'submitted']);
        return $id;
    }

    /** Admin approves the payment — the contract becomes usable. */
    public function approvePayment(int $contractId): void
    {
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        $p = Payment::latestForContract($contractId);
        if ($p) {
            Payment::update((int) $p['id'], ['status' => 'approved', 'verified_at' => date('Y-m-d H:i:s')]);
        }
        // The contract term starts on the day the admin approves the payment,
        // not on the day the customer created it. Any extension months already
        // granted are re-applied on top of the new base end date.
        $start   = date('Y-m-d');
        $baseEnd = date('Y-m-d', strtotime("{$start} +{$this->contractMonths()} months"));
        $extUsed = (int) ($c['extension_months_used'] ?? 0);
        $end     = $extUsed > 0 ? date('Y-m-d', strtotime("{$baseEnd} +{$extUsed} months")) : $baseEnd;
        Contract::update($contractId, [
            'payment_status' => 'paid',
            'start_date'     => $start,
            'base_end_date'  => $baseEnd,
            'end_date'       => $end,
        ]);
        Contract::refreshStatus($contractId);
    }

    /** Admin rejects the payment — customer must re-submit. */
    public function rejectPayment(int $contractId, string $note = ''): void
    {
        $c = Contract::find($contractId);
        if (!$c) {
            throw new RuntimeException('ไม่พบสัญญา');
        }
        $p = Payment::latestForContract($contractId);
        if ($p) {
            Payment::update((int) $p['id'], [
                'status'      => 'rejected',
                'verified_at' => date('Y-m-d H:i:s'),
                'note'        => $note !== '' ? $note : null,
            ]);
        }
        Contract::update($contractId, ['payment_status' => 'unpaid']);
    }

    // --- GPU rental ---------------------------------------------------------

    /**
     * Buy GPU cards. Creates a GPU-only contract if $contractId is null (0 M
     * units), otherwise adds cards to an existing contract's wallet.
     */
    public function purchaseGpu(int $userId, string $customerName, int $cards, int $pricePerCard, ?int $packageId = null, ?int $contractId = null, string $paymentStatus = 'unpaid'): int
    {
        if ($cards <= 0) {
            throw new RuntimeException('จำนวนการ์ดต้องมากกว่า 0');
        }
        $db = $this->db();
        $db->beginTransaction();
        try {
            $today = date('Y-m-d');
            $pkgName = $packageId ? (Package::find($packageId)['name'] ?? 'แพ็กเกจ GPU') : 'กำหนดเอง';
            if ($contractId === null) {
                $baseEnd = date('Y-m-d', strtotime("{$today} +{$this->contractMonths()} months"));
                $contractId = Contract::insert([
                    'contract_no'    => Contract::nextNo(),
                    'user_id'        => $userId,
                    'package_id'     => $packageId,
                    'customer_name'  => $customerName,
                    'units_total'    => 0,
                    'units_remaining' => 0,
                    'gpu_total'      => $cards,
                    'gpu_remaining'  => $cards,
                    'unit_days'      => $this->unitDays(),
                    'price_per_m'    => 0,
                    'start_date'     => $today,
                    'base_end_date'  => $baseEnd,
                    'end_date'       => $baseEnd,
                    'status'         => 'active',
                    'payment_status' => $paymentStatus,
                    'total_amount'   => $cards * $pricePerCard,
                ]);
            } else {
                $c = Contract::find($contractId);
                if (!$c) {
                    throw new RuntimeException('ไม่พบสัญญา');
                }
                Contract::update($contractId, [
                    'gpu_total'     => (int) $c['gpu_total'] + $cards,
                    'gpu_remaining' => (int) $c['gpu_remaining'] + $cards,
                ]);
            }
            $c = Contract::find($contractId);
            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => $today,
                'description' => "ซื้อการ์ด GPU {$cards} ตัว ({$pkgName} @ " . baht($pricePerCard) . "/การ์ด)",
                'amount'      => 0,
                'balance'     => (int) $c['units_remaining'],
                'gpu_amount'  => $cards,
                'gpu_balance' => (int) $c['gpu_remaining'],
                'type'        => 'adjust',
            ]);
            $db->commit();
            return $contractId;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Customer requests an API key. Consumes one GPU card (1 card = 1 key) and
     * queues it for the admin to provision with a BASE URL + key.
     */
    /**
     * Customer requests an API key spending $gpuUnits GPU cards (1 G = 30 days).
     * The key will be valid for gpuUnits * unit_days days from provisioning.
     */
    public function requestApiKey(int $contractId, string $label, int $gpuUnits = 1): int
    {
        if ($gpuUnits < 1) {
            throw new RuntimeException('จำนวนการ์ด GPU ต้องอย่างน้อย 1');
        }
        $db = $this->db();
        $db->beginTransaction();
        try {
            $c = Contract::find($contractId);
            if (!$c) {
                throw new RuntimeException('ไม่พบสัญญา');
            }
            if (($c['payment_status'] ?? 'paid') !== 'paid') {
                throw new RuntimeException('สัญญานี้ยังไม่ได้รับการอนุมัติการชำระเงิน');
            }
            if ((int) $c['gpu_remaining'] < $gpuUnits) {
                throw new RuntimeException('การ์ด GPU คงเหลือไม่พอสำหรับสร้าง API Key');
            }
            $days = $gpuUnits * $this->unitDays();
            $newGpu = (int) $c['gpu_remaining'] - $gpuUnits;
            Contract::update($contractId, ['gpu_remaining' => $newGpu]);
            $keyNo = ApiKey::nextNo();
            $id = ApiKey::insert([
                'key_no'      => $keyNo,
                'contract_id' => $contractId,
                'gpu_units'   => $gpuUnits,
                'days'        => $days,
                'label'       => $label !== '' ? $label : null,
                'status'      => 'requested',
            ]);
            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => date('Y-m-d'),
                'description' => "ขอสร้าง API Key {$keyNo} (ใช้การ์ด GPU {$gpuUnits} ตัว = {$days} วัน)",
                'amount'      => 0,
                'balance'     => (int) $c['units_remaining'],
                'gpu_amount'  => -$gpuUnits,
                'gpu_balance' => $newGpu,
                'type'        => 'adjust',
            ]);
            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /** Admin provisions a requested key with the BASE URL + API key. */
    public function provisionApiKey(int $id, string $baseUrl, string $apiKey): void
    {
        $k = ApiKey::find($id);
        if (!$k) {
            throw new RuntimeException('ไม่พบคำขอ API Key');
        }
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('BASE URL ไม่ถูกต้อง');
        }
        if (trim($apiKey) === '') {
            throw new RuntimeException('กรุณากรอก API Key');
        }
        // Usage is counted from the provision date: gpu_units * 30 days.
        $days = (int) ($k['days'] ?? $this->unitDays());
        ApiKey::update($id, [
            'base_url'       => $baseUrl,
            'api_key'        => trim($apiKey),
            'expires_at'     => date('Y-m-d', strtotime("+{$days} days")),
            'status'         => 'active',
            'provisioned_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Admin edits an existing key's BASE URL / API key (status unchanged). */
    public function updateApiKey(int $id, string $baseUrl, string $apiKey): void
    {
        $k = ApiKey::find($id);
        if (!$k) {
            throw new RuntimeException('ไม่พบ API Key');
        }
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('BASE URL ไม่ถูกต้อง');
        }
        if (trim($apiKey) === '') {
            throw new RuntimeException('กรุณากรอก API Key');
        }
        ApiKey::update($id, ['base_url' => $baseUrl, 'api_key' => trim($apiKey)]);
    }

    /**
     * Change an API key's status. Marking a not-yet-active request 'failed'
     * refunds the reserved GPU card back to the contract.
     */
    public function setApiKeyStatus(int $id, string $status): void
    {
        $allowed = ['requested', 'provisioning', 'active', 'failed'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('สถานะไม่ถูกต้อง');
        }
        $k = ApiKey::find($id);
        if (!$k) {
            throw new RuntimeException('ไม่พบคำขอ API Key');
        }
        if ($status === 'failed' && $k['status'] !== 'active') {
            $c = Contract::find((int) $k['contract_id']);
            if ($c) {
                $refund = (int) ($k['gpu_units'] ?? 1);
                Contract::update((int) $k['contract_id'], ['gpu_remaining' => (int) $c['gpu_remaining'] + $refund]);
            }
        }
        ApiKey::update($id, ['status' => $status]);
    }
}

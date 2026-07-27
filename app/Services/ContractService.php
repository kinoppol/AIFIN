<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Config;
use App\Models\ApiKey;
use App\Models\Contract;
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
    public function purchase(int $userId, string $customerName, int $units, int $pricePerM, ?int $packageId = null, ?int $contractId = null, int $bonusGpu = 0): int
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
                Contract::update($contractId, [
                    'gpu_total'     => (int) $cc['gpu_total'] + $bonusGpu,
                    'gpu_remaining' => (int) $cc['gpu_remaining'] + $bonusGpu,
                ]);
                UnitLedger::insert([
                    'contract_id' => $contractId,
                    'entry_date'  => $today,
                    'description' => "แถมการ์ด GPU {$bonusGpu} ตัว (แพ็กเกจ {$pkgName})",
                    'amount'      => 0,
                    'balance'     => $balance,
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
    public function redeem(int $contractId, string $email, int $units): int
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
            if ((int) $c['units_remaining'] < $units) {
                throw new RuntimeException('หน่วยคงเหลือไม่พอสำหรับการแลก');
            }
            $days = $units * (int) $c['unit_days'];
            // Redeemed access must not outlive the contract: its duration cannot
            // exceed the days remaining until the (possibly extended) end date.
            $daysLeft = (int) floor((strtotime($c['end_date']) - strtotime(date('Y-m-d'))) / 86400);
            if ($days > $daysLeft) {
                throw new RuntimeException(
                    "ระยะเวลาสิทธิ์ที่แลก ({$days} วัน) เกินอายุสัญญาที่เหลืออยู่ (" . max(0, $daysLeft) . " วัน)"
                );
            }
            $balance = (int) $c['units_remaining'] - $units;
            $expires = date('Y-m-d', strtotime("+{$days} days"));

            Contract::update($contractId, ['units_remaining' => $balance]);

            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => date('Y-m-d'),
                'description' => "แลกสิทธิ์ → {$email} ({$days} วัน)",
                'amount'      => -$units,
                'balance'     => $balance,
                'type'        => 'redeem',
            ]);

            $id = Redemption::insert([
                'redeem_no'   => Redemption::nextNo(),
                'contract_id' => $contractId,
                'email'       => $email,
                'units'       => $units,
                'days'        => $days,
                'status'      => 'pending',
                'expires_at'  => $expires,
            ]);

            $db->commit();
            return $id;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
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
            $data['provisioned_at'] = date('Y-m-d H:i:s');
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

    // --- GPU rental ---------------------------------------------------------

    /**
     * Buy GPU cards. Creates a GPU-only contract if $contractId is null (0 M
     * units), otherwise adds cards to an existing contract's wallet.
     */
    public function purchaseGpu(int $userId, string $customerName, int $cards, int $pricePerCard, ?int $packageId = null, ?int $contractId = null): int
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
    public function requestApiKey(int $contractId, string $label): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $c = Contract::find($contractId);
            if (!$c) {
                throw new RuntimeException('ไม่พบสัญญา');
            }
            if ((int) $c['gpu_remaining'] < 1) {
                throw new RuntimeException('การ์ด GPU คงเหลือไม่พอสำหรับสร้าง API Key');
            }
            Contract::update($contractId, ['gpu_remaining' => (int) $c['gpu_remaining'] - 1]);
            $keyNo = ApiKey::nextNo();
            $id = ApiKey::insert([
                'key_no'      => $keyNo,
                'contract_id' => $contractId,
                'label'       => $label !== '' ? $label : null,
                'status'      => 'requested',
            ]);
            UnitLedger::insert([
                'contract_id' => $contractId,
                'entry_date'  => date('Y-m-d'),
                'description' => "ขอสร้าง API Key {$keyNo} (ใช้การ์ด GPU 1 ตัว)",
                'amount'      => 0,
                'balance'     => (int) $c['units_remaining'],
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
        ApiKey::update($id, [
            'base_url'       => $baseUrl,
            'api_key'        => trim($apiKey),
            'status'         => 'active',
            'provisioned_at' => date('Y-m-d H:i:s'),
        ]);
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
                Contract::update((int) $k['contract_id'], ['gpu_remaining' => (int) $c['gpu_remaining'] + 1]);
            }
        }
        ApiKey::update($id, ['status' => $status]);
    }
}

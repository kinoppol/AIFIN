<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\AiPlan;

/**
 * Admin CRUD for the monthly AI plans a redeemed seat can be provisioned on.
 */
class AiPlanController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $plans = AiPlan::allSorted();
        foreach ($plans as &$p) {
            $p['used'] = AiPlan::usageCount((int) $p['id']);
        }
        unset($p);
        $this->render('admin/ai_plans', [
            'title'  => 'แพ็กเกจ AI',
            'active' => 'plans',
            'plans'  => $plans,
            'badges' => DashboardController::badges(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $name = trim((string) $this->input('name'));
        if ($name === '') {
            $this->flash('danger', 'กรุณากรอกชื่อแพ็กเกจ');
        } elseif (AiPlan::findByName($name)) {
            $this->flash('danger', 'มีแพ็กเกจชื่อนี้อยู่แล้ว');
        } else {
            AiPlan::insert([
                'name'       => $name,
                'vendor'     => trim((string) $this->input('vendor')) ?: null,
                'note'       => trim((string) $this->input('note')) ?: null,
                'status'     => $this->status(),
                'sort_order' => (int) $this->input('sort_order'),
            ]);
            $this->flash('success', 'เพิ่มแพ็กเกจ AI แล้ว');
        }
        $this->redirect('admin/plans');
    }

    public function update(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $plan = $this->plan((int) $this->input('id'));
        $name = trim((string) $this->input('name'));
        if ($name === '') {
            $this->flash('danger', 'กรุณากรอกชื่อแพ็กเกจ');
            $this->redirect('admin/plans');
        }
        $dupe = AiPlan::findByName($name);
        if ($dupe && (int) $dupe['id'] !== (int) $plan['id']) {
            $this->flash('danger', 'มีแพ็กเกจชื่อนี้อยู่แล้ว');
            $this->redirect('admin/plans');
        }
        AiPlan::update((int) $plan['id'], [
            'name'       => $name,
            'vendor'     => trim((string) $this->input('vendor')) ?: null,
            'note'       => trim((string) $this->input('note')) ?: null,
            'status'     => $this->status(),
            'sort_order' => (int) $this->input('sort_order'),
        ]);
        $this->flash('success', 'บันทึกแพ็กเกจ AI แล้ว');
        $this->redirect('admin/plans');
    }

    /** Suspend / resume — suspended plans disappear from the redeem forms. */
    public function toggleStatus(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $plan = $this->plan((int) $this->input('id'));
        $suspend = ($plan['status'] ?? 'active') === 'active';
        AiPlan::update((int) $plan['id'], ['status' => $suspend ? 'suspended' : 'active']);
        $this->flash('success', $suspend ? 'ระงับแพ็กเกจแล้ว (ลูกค้าเลือกไม่ได้)' : 'เปิดใช้งานแพ็กเกจอีกครั้งแล้ว');
        $this->redirect('admin/plans');
    }

    public function destroy(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $plan = $this->plan((int) $this->input('id'));
        if (AiPlan::usageCount((int) $plan['id']) > 0) {
            $this->flash('danger', 'แพ็กเกจนี้ถูกใช้แลกสิทธิ์แล้ว ลบไม่ได้ (ใช้ "ระงับ" แทน)');
        } else {
            AiPlan::delete((int) $plan['id']);
            $this->flash('success', 'ลบแพ็กเกจแล้ว');
        }
        $this->redirect('admin/plans');
    }

    private function status(): string
    {
        return $this->input('status') === 'suspended' ? 'suspended' : 'active';
    }

    /** @return array the plan row, or redirect when missing */
    private function plan(int $id): array
    {
        $plan = AiPlan::find($id);
        if (!$plan) {
            $this->flash('danger', 'ไม่พบแพ็กเกจ');
            $this->redirect('admin/plans');
        }
        return $plan;
    }
}

<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Package;

class PackageController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/packages', [
            'title'    => 'แพ็กเกจ & โปรโมชั่น',
            'active'   => 'pack',
            'packages' => Package::allSorted(),
            'unitDays' => (int) config('app.unit_days', 30),
            'badges'   => DashboardController::badges(),
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $code = trim((string) $this->input('code'));
        if ($code === '' || Package::findByCode($code)) {
            $this->flash('danger', 'รหัสแพ็กเกจว่างหรือซ้ำกับที่มีอยู่');
            $this->redirect('admin/packages');
        }
        $kind = in_array($this->input('kind'), ['ai', 'gpu'], true) ? $this->input('kind') : 'ai';
        Package::insert([
            'code'         => $code,
            'kind'         => $kind,
            'name'         => trim((string) $this->input('name')),
            'note'         => trim((string) $this->input('note')),
            'units'        => (int) $this->input('units'),
            'bonus_gpu'    => $kind === 'ai' ? max(0, (int) $this->input('bonus_gpu')) : 0,
            'unit_days'    => (int) config('app.unit_days', 30),
            'list_price'   => (int) $this->input('list_price'),
            'sale_price'   => (int) $this->input('sale_price'),
            'promo_label'  => trim((string) $this->input('promo_label')) ?: null,
            'window_start' => $this->input('window_start') ?: null,
            'window_end'   => $this->input('window_end') ?: null,
            'status'       => in_array($this->input('status'), ['active', 'promo', 'closed'], true)
                                ? $this->input('status') : 'active',
            'sort_order'   => (int) $this->input('sort_order'),
        ]);
        $this->flash('success', 'สร้างแพ็กเกจใหม่แล้ว');
        $this->redirect('admin/packages');
    }

    /** Only the per-M sale price and status/window are editable (unit value is locked). */
    public function update(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        $id = (int) $this->input('id');
        $pkg = Package::find($id);
        if (!$pkg) {
            $this->flash('danger', 'ไม่พบแพ็กเกจ');
            $this->redirect('admin/packages');
        }
        $data = [
            'sale_price'  => (int) $this->input('sale_price'),
            'promo_label' => trim((string) $this->input('promo_label')) ?: null,
            'status'      => in_array($this->input('status'), ['active', 'promo', 'closed'], true)
                                ? $this->input('status') : 'active',
        ];
        // Bundled free GPU cards apply only to AI packages.
        if ($pkg['kind'] === 'ai') {
            $data['bonus_gpu'] = max(0, (int) $this->input('bonus_gpu'));
        }
        Package::update($id, $data);
        $this->flash('success', 'อัปเดตแพ็กเกจแล้ว');
        $this->redirect('admin/packages');
    }
}

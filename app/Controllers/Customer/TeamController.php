<?php
namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\User;

/**
 * Assistant logins (ผู้ช่วย) under a customer account.
 *
 * Only the account owner may manage the team — an assistant can use the
 * account's contracts but cannot create or remove other assistants.
 */
class TeamController extends Controller
{
    public function index(): void
    {
        $this->requireOwner();
        $ownerId = Auth::ownerId();
        $q = trim((string) $this->input('q'));
        $this->render('customer/team', [
            'title'      => 'ผู้ช่วยของฉัน',
            'assistants' => User::assistantsOf($ownerId, $q),
            'q'          => $q,
            'total'      => User::count('parent_user_id = ?', [$ownerId]),
        ], 'layouts/customer');
    }

    public function store(): void
    {
        $this->requireOwner();
        Csrf::verify();
        $ownerId  = Auth::ownerId();
        $name     = trim((string) $this->input('name'));
        $email    = strtolower(trim((string) $this->input('email')));
        $password = (string) $this->input('password');

        if ($name === '') {
            $this->flash('danger', 'กรุณากรอกชื่อผู้ช่วย');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('danger', 'อีเมลไม่ถูกต้อง');
        } elseif (User::findByEmail($email)) {
            $this->flash('danger', 'อีเมลนี้ถูกใช้งานแล้ว');
        } elseif (strlen($password) < 6) {
            $this->flash('danger', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
        } else {
            User::create($email, $password, $name, 'customer', $ownerId);
            $this->flash('success', 'เพิ่มผู้ช่วยแล้ว — แจ้งอีเมลและรหัสผ่านให้ผู้ช่วยเข้าสู่ระบบได้ทันที');
        }
        $this->redirect('account/team');
    }

    /** Rename an assistant and optionally set a new password. */
    public function update(): void
    {
        $this->requireOwner();
        Csrf::verify();
        $a = $this->assistant((int) $this->input('id'));
        $name     = trim((string) $this->input('name'));
        $password = (string) $this->input('password');

        if ($name === '') {
            $this->flash('danger', 'กรุณากรอกชื่อผู้ช่วย');
            $this->redirect('account/team');
        }
        User::update((int) $a['id'], ['name' => $name]);
        if ($password !== '') {
            if (strlen($password) < 6) {
                $this->flash('danger', 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร');
                $this->redirect('account/team');
            }
            User::setPassword((int) $a['id'], $password);
            $this->flash('success', 'บันทึกข้อมูลและตั้งรหัสผ่านใหม่แล้ว');
        } else {
            $this->flash('success', 'บันทึกข้อมูลผู้ช่วยแล้ว');
        }
        $this->redirect('account/team');
    }

    /** Suspend / resume an assistant login. */
    public function toggleStatus(): void
    {
        $this->requireOwner();
        Csrf::verify();
        $a = $this->assistant((int) $this->input('id'));
        $suspend = ($a['status'] ?? 'active') === 'active';
        User::update((int) $a['id'], ['status' => $suspend ? 'suspended' : 'active']);
        $this->flash('success', $suspend ? 'ระงับผู้ช่วยแล้ว (เข้าสู่ระบบไม่ได้)' : 'เปิดใช้งานผู้ช่วยอีกครั้งแล้ว');
        $this->redirect('account/team');
    }

    public function destroy(): void
    {
        $this->requireOwner();
        Csrf::verify();
        $a = $this->assistant((int) $this->input('id'));
        User::delete((int) $a['id']);
        $this->flash('success', 'ลบผู้ช่วยแล้ว');
        $this->redirect('account/team');
    }

    /** Assistants may use the account but not manage the team. */
    private function requireOwner(): void
    {
        $this->requireAuth();
        if (Auth::isAssistant()) {
            $this->flash('danger', 'เฉพาะเจ้าของบัญชีเท่านั้นที่จัดการผู้ช่วยได้');
            $this->redirect('account');
        }
    }

    /** @return array the assistant row, or redirect when it isn't ours */
    private function assistant(int $id): array
    {
        $a = User::find($id);
        if (!$a || (int) ($a['parent_user_id'] ?? 0) !== Auth::ownerId()) {
            $this->flash('danger', 'ไม่พบผู้ช่วย');
            $this->redirect('account/team');
        }
        return $a;
    }
}

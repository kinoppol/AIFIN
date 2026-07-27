<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? 'admin' : 'account');
        }
        $this->render('auth/login', ['title' => 'เข้าสู่ระบบ'], 'layouts/auth');
    }

    public function login(): void
    {
        Csrf::verify();
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        if (Auth::attempt($email, $password)) {
            $this->redirect(Auth::isAdmin() ? 'admin' : 'account');
        }
        $this->flash('danger', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
        $this->redirect('login');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('account');
        }
        $this->render('auth/register', ['title' => 'ลงทะเบียน'], 'layouts/auth');
    }

    public function register(): void
    {
        Csrf::verify();
        $name = trim((string) $this->input('name'));
        $email = trim((string) $this->input('email'));
        $password = (string) $this->input('password');

        $errors = [];
        if ($name === '') {
            $errors[] = 'กรุณากรอกชื่อ';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'อีเมลไม่ถูกต้อง';
        } elseif (User::findByEmail($email)) {
            $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
        }
        if (strlen($password) < 6) {
            $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        }
        if ($errors) {
            foreach ($errors as $err) {
                $this->flash('danger', $err);
            }
            $this->redirect('register');
        }

        $id = User::create($email, $password, $name, 'customer');
        Auth::login(User::find($id));
        $this->flash('success', 'สร้างบัญชีสำเร็จ ยินดีต้อนรับ');
        $this->redirect('account');
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        $this->redirect('');
    }
}

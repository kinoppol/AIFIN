<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Migrator;

/**
 * Admin-facing migration manager: view applied/pending schema migrations and
 * apply pending ones. This is how future DB structure updates are rolled out —
 * drop a new NNN_*.php file into /migrations and click "Run pending".
 */
class MigrationController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $migrator = new Migrator(Database::instance());
        $this->render('admin/migrations', [
            'title'    => 'Migrations ฐานข้อมูล',
            'active'   => 'migrations',
            'status'   => $migrator->status(),
            'pending'  => array_keys($migrator->pending()),
            'badges'   => DashboardController::badges(),
        ]);
    }

    public function run(): void
    {
        $this->requireAdmin();
        Csrf::verify();
        try {
            $ran = (new Migrator(Database::instance()))->migrate();
            if ($ran) {
                $this->flash('success', 'รัน migration แล้ว: ' . implode(', ', $ran));
            } else {
                $this->flash('info', 'ไม่มี migration ที่รอดำเนินการ');
            }
        } catch (\Throwable $e) {
            $this->flash('danger', 'Migration ล้มเหลว: ' . $e->getMessage());
        }
        $this->redirect('admin/migrations');
    }
}

<?php
/**
 * Front controller. All application requests route through here.
 */

$installed = require __DIR__ . '/app/bootstrap.php';

use App\Core\Router;

// Not installed yet → send everyone to the installer.
if (!$installed) {
    header('Location: ' . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/install.php');
    exit;
}

$router = new Router();
$GLOBALS['__router'] = $router;

use App\Controllers\LandingController;
use App\Controllers\AuthController;
use App\Controllers\Customer\AccountController;
use App\Controllers\Customer\TeamController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ContractController;
use App\Controllers\Admin\WalletController;
use App\Controllers\Admin\RedeemController;
use App\Controllers\Admin\ExtensionController;
use App\Controllers\Admin\PackageController;
use App\Controllers\Admin\AiPlanController;
use App\Controllers\Admin\ApiKeyController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\MigrationController;

// Public
$router->get('',          [LandingController::class, 'index']);
$router->get('login',     [AuthController::class, 'showLogin']);
$router->post('login',    [AuthController::class, 'login']);
$router->get('register',  [AuthController::class, 'showRegister']);
$router->post('register', [AuthController::class, 'register']);
$router->post('logout',   [AuthController::class, 'logout']);

// Customer area
$router->get('account',          [AccountController::class, 'index']);
$router->get('account/ai',       [AccountController::class, 'ai']);
$router->get('account/buy',      [AccountController::class, 'buyForm']);
$router->post('account/buy',     [AccountController::class, 'buy']);
$router->get('account/contract', [AccountController::class, 'contract']);
$router->post('account/redeem',  [AccountController::class, 'redeem']);
$router->post('account/extend',  [AccountController::class, 'requestExtension']);
$router->post('account/buy-gpu', [AccountController::class, 'buyGpu']);
$router->post('account/apikey',  [AccountController::class, 'requestApiKey']);
$router->post('account/payment', [AccountController::class, 'submitPayment']);
$router->get('account/quotation', [AccountController::class, 'quotation']);
$router->get('account/receipt',  [AccountController::class, 'receipt']);
$router->get('account/proof',    [AccountController::class, 'proof']);
$router->get('account/emails',   [AccountController::class, 'emails']);
$router->post('account/emails',  [AccountController::class, 'addEmail']);
$router->post('account/emails/update', [AccountController::class, 'updateEmail']);
$router->post('account/emails/status', [AccountController::class, 'toggleEmailStatus']);
$router->post('account/emails/delete', [AccountController::class, 'deleteEmail']);
$router->get('account/team',     [TeamController::class, 'index']);
$router->post('account/team',    [TeamController::class, 'store']);
$router->post('account/team/update', [TeamController::class, 'update']);
$router->post('account/team/status', [TeamController::class, 'toggleStatus']);
$router->post('account/team/delete', [TeamController::class, 'destroy']);

// Admin area
$router->get('admin',                    [DashboardController::class, 'index']);
$router->get('admin/contracts',          [ContractController::class, 'index']);
$router->post('admin/contracts',         [ContractController::class, 'store']);
$router->get('admin/contracts/show',     [ContractController::class, 'show']);
$router->post('admin/contracts/redeem',  [ContractController::class, 'redeem']);
$router->get('admin/wallets',            [WalletController::class, 'index']);
$router->get('admin/redeem',             [RedeemController::class, 'index']);
$router->post('admin/redeem/status',     [RedeemController::class, 'updateStatus']);
$router->get('admin/extensions',         [ExtensionController::class, 'index']);
$router->post('admin/extensions/approve',[ExtensionController::class, 'approve']);
$router->post('admin/extensions/reject', [ExtensionController::class, 'reject']);
$router->get('admin/packages',           [PackageController::class, 'index']);
$router->post('admin/packages',          [PackageController::class, 'store']);
$router->post('admin/packages/update',   [PackageController::class, 'update']);
$router->get('admin/plans',              [AiPlanController::class, 'index']);
$router->post('admin/plans',             [AiPlanController::class, 'store']);
$router->post('admin/plans/update',      [AiPlanController::class, 'update']);
$router->post('admin/plans/status',      [AiPlanController::class, 'toggleStatus']);
$router->post('admin/plans/delete',      [AiPlanController::class, 'destroy']);
$router->get('admin/gpu',                [ApiKeyController::class, 'index']);
$router->post('admin/gpu/provision',     [ApiKeyController::class, 'provision']);
$router->post('admin/gpu/update',        [ApiKeyController::class, 'update']);
$router->post('admin/gpu/status',        [ApiKeyController::class, 'updateStatus']);
$router->get('admin/payments',           [PaymentController::class, 'index']);
$router->post('admin/payments/approve',  [PaymentController::class, 'approve']);
$router->post('admin/payments/reject',   [PaymentController::class, 'reject']);
$router->get('admin/migrations',         [MigrationController::class, 'index']);
$router->post('admin/migrations/run',    [MigrationController::class, 'run']);

$router->dispatch();

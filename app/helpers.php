<?php
/**
 * Global helper functions and the class autoloader.
 */

use App\Core\Config;
use App\Core\Csrf;

// --- PSR-4-ish autoloader for the App\ namespace ------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $rel = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// --- The router is stored globally so url() works everywhere -------------------
$GLOBALS['__router'] = null;

function router(): ?\App\Core\Router
{
    return $GLOBALS['__router'];
}

/** Build an app URL from a route path. */
function url(string $path = ''): string
{
    $r = router();
    return $r ? $r->url($path) : '/' . ltrim($path, '/');
}

/** Path to a static asset under /assets. */
function asset(string $path): string
{
    $r = router();
    $base = $r ? $r->base() : '';
    return $base . '/assets/' . ltrim($path, '/');
}

/** HTML-escape. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_field(): string
{
    return Csrf::field();
}

function config(string $key, $default = null)
{
    return Config::get($key, $default);
}

/** Old input value (not persisted across redirects here, just a convenience). */
function old(string $key, $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

/** Consume queued flash messages. */
function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Format an integer as a "NN M" unit label. */
function units(int|string|null $n): string
{
    return number_format((int) $n) . ' M';
}

/** Format baht. */
function baht(int|float|null $n): string
{
    return '฿' . number_format((float) $n);
}

/** Days remaining until a contract's end date (>= 0). */
function contract_days_left(array $c): int
{
    return max(0, (int) floor((strtotime($c['end_date']) - strtotime(date('Y-m-d'))) / 86400));
}

/**
 * Max units redeemable now: capped by both remaining units AND the remaining
 * contract duration (redeemed access must not outlive the contract).
 */
function contract_max_redeem(array $c): int
{
    $unitDays = max(1, (int) $c['unit_days']);
    $byDuration = intdiv(contract_days_left($c), $unitDays);
    return max(0, min((int) $c['units_remaining'], $byDuration));
}

/** Map a status code to [pill css class, Thai label] for the given domain. */
function status_pill(string $domain, string $status): array
{
    $map = [
        'contract' => [
            'active'      => ['pill-ok', 'ใช้งาน'],
            'expiring'    => ['pill-wait', 'ใกล้หมดอายุ'],
            'pending_ext' => ['pill-info', 'รออนุมัติขยาย'],
            'extended'    => ['pill-info', 'ขยายอายุแล้ว'],
            'expired'     => ['pill-off', 'หมดอายุ'],
        ],
        'redeem' => [
            'pending'        => ['pill-wait', 'รอจัดหา'],
            'provisioning'   => ['pill-wait', 'กำลังจัดหา'],
            'awaiting_email' => ['pill-info', 'รอยืนยันอีเมล'],
            'success'        => ['pill-ok', 'จัดหาสำเร็จ'],
            'failed'         => ['pill-bad', 'ล้มเหลว'],
        ],
        'ext' => [
            'pending'    => ['pill-wait', 'รออนุมัติ'],
            'reviewing'  => ['pill-info', 'ตรวจสอบโควตา'],
            'over_quota' => ['pill-bad', 'เกินโควตา'],
            'approved'   => ['pill-ok', 'อนุมัติแล้ว'],
            'rejected'   => ['pill-off', 'ปฏิเสธ'],
        ],
    ];
    return $map[$domain][$status] ?? ['pill-off', $status];
}

/** Echo a status pill. */
function pill(string $domain, string $status): string
{
    [$cls, $label] = status_pill($domain, $status);
    return '<span class="pill ' . $cls . '">' . e($label) . '</span>';
}

/** Thai short date from a Y-m-d string. */
function thai_date(?string $ymd): string
{
    if (!$ymd) {
        return '—';
    }
    $ts = strtotime($ymd);
    if ($ts === false) {
        return e($ymd);
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $d = (int) date('j', $ts);
    $m = (int) date('n', $ts);
    $y = (int) date('Y', $ts) + 543; // Buddhist era
    return "{$d} {$months[$m]} {$y}";
}

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

/** Inline SVG icon (stroke = currentColor) for use inside buttons/labels. */
function icon(string $name, int $size = 16): string
{
    static $paths = [
        'login'         => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'user-plus'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'cart'          => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2 2h2l2.7 12.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H5.1"/>',
        'key'           => '<circle cx="7.5" cy="15.5" r="5.5"/><path d="M21 2l-9.6 9.6"/><path d="M15.5 7.5l3 3L22 7l-3-3"/>',
        'calendar-plus' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/>',
        'redeem'        => '<path d="M17 2l4 4-4 4"/><path d="M3 11v-1a4 4 0 0 1 4-4h14"/><path d="M7 22l-4-4 4-4"/><path d="M21 13v1a4 4 0 0 1-4 4H3"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'check'         => '<path d="M20 6L9 17l-5-5"/>',
        'x'             => '<path d="M18 6 6 18M6 6l12 12"/>',
        'send'          => '<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>',
        'database'      => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'download'      => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>',
        'edit'          => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'logout'        => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'grid'          => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
    $p = $paths[$name] ?? '';
    if ($p === '') {
        return '';
    }
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none" aria-hidden="true">' . $p . '</svg>';
}

/** True once the contract's end date has passed (no more redeeming allowed). */
function contract_is_expired(array $c): bool
{
    return strtotime($c['end_date']) < strtotime(date('Y-m-d'));
}

/**
 * Max units redeemable in one request: min(remaining, per-request cap). Once the
 * contract has expired no more units can be redeemed (0) — but access already
 * redeemed keeps running until its own days are used up.
 */
function contract_max_redeem(array $c): int
{
    if (contract_is_expired($c)) {
        return 0;
    }
    $cap = (int) config('app.max_redeem_units', 12);
    return max(0, min((int) $c['units_remaining'], $cap));
}

/**
 * Render a contract's remaining balance, showing AI units (M) and/or GPU cards
 * (G) depending on which the contract holds. GPU is shown in the accent-2 colour.
 */
function balance_summary(array $c): string
{
    $lines = [];
    if ((int) $c['units_total'] > 0) {
        $lines[] = '<div>' . (int) $c['units_remaining'] . ' / ' . (int) $c['units_total'] . ' M</div>';
    }
    if ((int) ($c['gpu_total'] ?? 0) > 0) {
        $lines[] = '<div style="color:var(--accent2)">' . (int) $c['gpu_remaining'] . ' / ' . (int) $c['gpu_total'] . ' G</div>';
    }
    if (!$lines) {
        $lines[] = '<span class="faint">—</span>';
    }
    return implode('', $lines);
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

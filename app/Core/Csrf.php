<?php
namespace App\Core;

/**
 * Session-based CSRF token helper.
 */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /** Abort the request with 419 if the posted token is missing/invalid. */
    public static function verify(): void
    {
        if (!self::check($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Invalid or expired form token. Please go back and try again.');
        }
    }
}

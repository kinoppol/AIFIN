<?php
namespace App\Core;

/**
 * Loads and holds the app configuration (config/config.php).
 */
class Config
{
    private static array $data = [];
    private static bool $loaded = false;

    public static function load(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }
        $data = require $path;
        if (!is_array($data)) {
            return false;
        }
        self::$data = $data;
        self::$loaded = true;
        return true;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    public static function all(): array
    {
        return self::$data;
    }

    /** Dot-path getter: Config::get('app.unit_days', 30). */
    public static function get(string $key, $default = null)
    {
        $node = self::$data;
        foreach (explode('.', $key) as $seg) {
            if (is_array($node) && array_key_exists($seg, $node)) {
                $node = $node[$seg];
            } else {
                return $default;
            }
        }
        return $node;
    }
}

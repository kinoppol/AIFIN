<?php
namespace App\Core;

use PDO;
use PDOException;

/**
 * Thin PDO wrapper (singleton). MariaDB / MySQL only.
 */
class Database
{
    private static ?PDO $pdo = null;

    /** Build a PDO from a config['db'] array. Optionally without selecting a db. */
    public static function connect(array $db, bool $selectDatabase = true): PDO
    {
        $dsn = "mysql:host={$db['host']};port={$db['port']}";
        if ($selectDatabase && !empty($db['name'])) {
            $dsn .= ";dbname={$db['name']}";
        }
        $dsn .= ";charset=" . ($db['charset'] ?? 'utf8mb4');

        return new PDO($dsn, $db['user'], $db['pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /** App-wide shared connection, built from the loaded config. */
    public static function instance(): PDO
    {
        if (self::$pdo === null) {
            $cfg = Config::all();
            self::$pdo = self::connect($cfg['db']);
        }
        return self::$pdo;
    }

    public static function set(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /** Quick connectivity test — returns null on success or an error string. */
    public static function testConnection(array $db, bool $selectDatabase = true): ?string
    {
        try {
            self::connect($db, $selectDatabase);
            return null;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}

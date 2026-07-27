<?php
namespace App\Core;

use PDO;

/**
 * Very small active-record-ish base. Subclasses set $table.
 */
abstract class Model
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::instance();
    }

    public static function all(string $orderBy = 'id ASC'): array
    {
        return static::db()
            ->query("SELECT * FROM " . static::$table . " ORDER BY {$orderBy}")
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function where(string $column, $value, string $orderBy = 'id ASC'): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM " . static::$table . " WHERE {$column} = ? ORDER BY {$orderBy}"
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $place = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO " . static::$table . " (`" . implode('`,`', $cols) . "`) VALUES ({$place})";
        static::db()->prepare($sql)->execute(array_values($data));
        return (int) static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        if (!$data) {
            return;
        }
        $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $sql = "UPDATE " . static::$table . " SET {$set} WHERE id = ?";
        $values = array_values($data);
        $values[] = $id;
        static::db()->prepare($sql)->execute($values);
    }

    public static function count(string $where = '1', array $params = []): int
    {
        $stmt = static::db()->prepare("SELECT COUNT(*) FROM " . static::$table . " WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}

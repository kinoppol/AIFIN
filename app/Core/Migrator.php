<?php
namespace App\Core;

use PDO;

/**
 * File-based migration runner.
 *
 * Each file in /migrations is named NNN_description.php and returns:
 *   ['name' => '001_initial_schema', 'up' => <string|string[]|callable(PDO)>]
 *
 * Applied migrations are tracked in the `migrations` table so the runner is
 * idempotent: running it repeatedly only applies files not yet recorded. This
 * powers both install.php (run all) and the Admin > Migrations menu (run
 * pending on demand for future schema updates).
 */
class Migrator
{
    private PDO $db;
    private string $dir;

    public function __construct(PDO $db, ?string $dir = null)
    {
        $this->db = $db;
        $this->dir = $dir ?: dirname(__DIR__, 2) . '/migrations';
    }

    /** Ensure the tracking table exists. Safe to call repeatedly. */
    public function ensureTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(190) NOT NULL UNIQUE,
                batch INT UNSIGNED NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** All migration files on disk, sorted by filename. Returns [name => path]. */
    public function discover(): array
    {
        $out = [];
        foreach (glob($this->dir . '/*.php') ?: [] as $path) {
            $out[basename($path, '.php')] = $path;
        }
        ksort($out);
        return $out;
    }

    /** Names already applied. */
    public function applied(): array
    {
        $this->ensureTable();
        return $this->db->query("SELECT migration FROM migrations ORDER BY migration")
            ->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /** [name => path] of files not yet applied. */
    public function pending(): array
    {
        $applied = array_flip($this->applied());
        return array_diff_key($this->discover(), $applied);
    }

    /**
     * A status list for the admin UI: each entry has name, applied (bool),
     * batch, applied_at.
     */
    public function status(): array
    {
        $this->ensureTable();
        $rows = $this->db->query("SELECT migration, batch, applied_at FROM migrations")
            ->fetchAll(PDO::FETCH_ASSOC);
        $meta = [];
        foreach ($rows as $r) {
            $meta[$r['migration']] = $r;
        }
        $out = [];
        foreach ($this->discover() as $name => $path) {
            $out[] = [
                'name'       => $name,
                'applied'    => isset($meta[$name]),
                'batch'      => $meta[$name]['batch'] ?? null,
                'applied_at' => $meta[$name]['applied_at'] ?? null,
            ];
        }
        return $out;
    }

    /**
     * Run every pending migration inside the next batch number.
     * Returns the list of migration names that were applied.
     */
    public function migrate(): array
    {
        $this->ensureTable();
        $pending = $this->pending();
        if (!$pending) {
            return [];
        }
        $batch = (int) $this->db->query("SELECT COALESCE(MAX(batch),0) FROM migrations")
            ->fetchColumn() + 1;

        $ran = [];
        $record = $this->db->prepare(
            "INSERT INTO migrations (migration, batch) VALUES (?, ?)"
        );
        foreach ($pending as $name => $path) {
            $def = require $path;
            $this->runUp($def['up'] ?? null);
            $record->execute([$name, $batch]);
            $ran[] = $name;
        }
        return $ran;
    }

    /** Execute a migration's up definition (string | string[] | callable). */
    private function runUp($up): void
    {
        if (is_callable($up)) {
            $up($this->db);
            return;
        }
        foreach ((array) $up as $sql) {
            $sql = trim((string) $sql);
            if ($sql !== '') {
                $this->db->exec($sql);
            }
        }
    }
}

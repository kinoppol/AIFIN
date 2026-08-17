<?php
namespace App\Models;

use App\Core\Model;

/**
 * Domains a customer allows for registering AI-seat emails.
 * An empty list means "any domain".
 */
class CustomerEmailDomain extends Model
{
    protected static string $table = 'customer_email_domains';

    /** @return array<int,array> */
    public static function forUser(int $userId): array
    {
        $stmt = static::db()->prepare(
            "SELECT * FROM customer_email_domains WHERE user_id = ? ORDER BY domain ASC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,string> just the domain names */
    public static function listForUser(int $userId): array
    {
        return array_column(self::forUser($userId), 'domain');
    }

    /** Normalise user input ("@RVC.ac.th", "https://rvc.ac.th/") to "rvc.ac.th". */
    public static function normalize(string $domain): string
    {
        $d = strtolower(trim($domain));
        $d = preg_replace('#^[a-z]+://#', '', $d);   // strip scheme
        $d = ltrim($d, '@');
        $d = explode('/', $d)[0];                    // strip any path
        return trim($d, '. ');
    }

    public static function isValid(string $domain): bool
    {
        return (bool) preg_match('/^(?=.{1,190}$)[a-z0-9-]+(\.[a-z0-9-]+)+$/', $domain);
    }

    /**
     * Whether an address may be registered: true when no domain list is set,
     * otherwise the address must sit on a listed domain (subdomains count).
     */
    public static function allows(int $userId, string $email): bool
    {
        $domains = self::listForUser($userId);
        if (!$domains) {
            return true;
        }
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }
        $host = strtolower(substr($email, $at + 1));
        foreach ($domains as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return true;
            }
        }
        return false;
    }

    public static function deleteForUser(int $id, int $userId): void
    {
        $stmt = static::db()->prepare("DELETE FROM customer_email_domains WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
}

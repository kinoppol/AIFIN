<?php
namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $email, string $password, string $name, string $role = 'customer'): int
    {
        return static::insert([
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name'          => $name,
            'role'          => $role,
        ]);
    }

    /** Create if missing, otherwise update name/password/role. Used by installer. */
    public static function upsertAdmin(string $email, string $password, string $name): int
    {
        $existing = self::findByEmail($email);
        if ($existing) {
            static::update((int) $existing['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'name'          => $name,
                'role'          => 'admin',
            ]);
            return (int) $existing['id'];
        }
        return self::create($email, $password, $name, 'admin');
    }
}

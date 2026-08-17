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

    public static function create(string $email, string $password, string $name, string $role = 'customer', ?int $parentUserId = null): int
    {
        return static::insert([
            'email'          => $email,
            'password_hash'  => password_hash($password, PASSWORD_DEFAULT),
            'name'           => $name,
            'role'           => $role,
            'parent_user_id' => $parentUserId,
        ]);
    }

    /** Assistant logins working under a customer account. @return array<int,array> */
    public static function assistantsOf(int $ownerId, string $search = ''): array
    {
        $sql = "SELECT * FROM users WHERE parent_user_id = ?";
        $params = [$ownerId];
        if ($search !== '') {
            $sql .= " AND (email LIKE ? OR name LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $stmt = static::db()->prepare($sql . " ORDER BY status ASC, name ASC");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function setPassword(int $id, string $password): void
    {
        static::update($id, ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
    }

    public static function delete(int $id): void
    {
        $stmt = static::db()->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
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

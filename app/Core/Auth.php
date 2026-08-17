<?php
namespace App\Core;

use App\Models\User;

/**
 * Session-backed authentication.
 */
class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (($user['status'] ?? 'active') !== 'active') {
            return false;
        }
        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $parentId = isset($user['parent_user_id']) && $user['parent_user_id'] !== null
            ? (int) $user['parent_user_id']
            : null;
        $owner = $parentId ? User::find($parentId) : null;
        $_SESSION['user'] = [
            'id'         => (int) $user['id'],
            'email'      => $user['email'],
            'name'       => $user['name'],
            'role'       => $user['role'],
            // Assistants act on their owner's data; owners are their own owner.
            'owner_id'   => $owner ? (int) $owner['id'] : (int) $user['id'],
            'owner_name' => $owner ? $owner['name'] : $user['name'],
        ];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    /** The account whose data is being worked on (owner id, not the assistant's). */
    public static function ownerId(): ?int
    {
        return $_SESSION['user']['owner_id'] ?? $_SESSION['user']['id'] ?? null;
    }

    public static function ownerName(): string
    {
        return (string) ($_SESSION['user']['owner_name'] ?? $_SESSION['user']['name'] ?? '');
    }

    /** True when the logged-in user is an assistant under another customer. */
    public static function isAssistant(): bool
    {
        return self::check() && self::ownerId() !== self::id();
    }

    public static function isAdmin(): bool
    {
        return (($_SESSION['user']['role'] ?? null) === 'admin');
    }
}

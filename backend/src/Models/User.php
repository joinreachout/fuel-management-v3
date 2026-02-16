<?php

namespace App\Models;

use App\Core\Database;

/**
 * User Model
 * Represents system users with authentication
 */
class User
{
    /**
     * Get all users
     */
    public static function all(): array
    {
        return Database::fetchAll("
            SELECT
                id,
                username,
                email,
                full_name,
                role,
                station_id,
                is_active,
                last_login,
                created_at
            FROM users
            ORDER BY full_name ASC
        ");
    }

    /**
     * Find user by ID
     */
    public static function find(int $id): ?array
    {
        $result = Database::fetchAll("
            SELECT
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.role,
                u.station_id,
                s.name as station_name,
                s.code as station_code,
                u.is_active,
                u.last_login,
                u.created_at
            FROM users u
            LEFT JOIN stations s ON u.station_id = s.id
            WHERE u.id = ?
        ", [$id]);

        return $result[0] ?? null;
    }

    /**
     * Find user by username
     */
    public static function findByUsername(string $username): ?array
    {
        $result = Database::fetchAll("
            SELECT
                u.id,
                u.username,
                u.password_hash,
                u.email,
                u.full_name,
                u.role,
                u.station_id,
                s.name as station_name,
                u.is_active,
                u.last_login,
                u.created_at
            FROM users u
            LEFT JOIN stations s ON u.station_id = s.id
            WHERE u.username = ?
        ", [$username]);

        return $result[0] ?? null;
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?array
    {
        $result = Database::fetchAll("
            SELECT
                u.id,
                u.username,
                u.password_hash,
                u.email,
                u.full_name,
                u.role,
                u.station_id,
                u.is_active,
                u.created_at
            FROM users u
            WHERE u.email = ?
        ", [$email]);

        return $result[0] ?? null;
    }

    /**
     * Get users by role
     */
    public static function getByRole(string $role): array
    {
        return Database::fetchAll("
            SELECT
                id,
                username,
                email,
                full_name,
                role,
                station_id,
                is_active,
                last_login,
                created_at
            FROM users
            WHERE role = ?
            ORDER BY full_name ASC
        ", [$role]);
    }

    /**
     * Get active users only
     */
    public static function getActive(): array
    {
        return Database::fetchAll("
            SELECT
                id,
                username,
                email,
                full_name,
                role,
                station_id,
                last_login,
                created_at
            FROM users
            WHERE is_active = 1
            ORDER BY full_name ASC
        ");
    }

    /**
     * Get users by station
     */
    public static function getByStation(int $stationId): array
    {
        return Database::fetchAll("
            SELECT
                id,
                username,
                email,
                full_name,
                role,
                is_active,
                last_login,
                created_at
            FROM users
            WHERE station_id = ?
            ORDER BY full_name ASC
        ", [$stationId]);
    }

    /**
     * Authenticate user
     */
    public static function authenticate(string $username, string $password): ?array
    {
        $user = self::findByUsername($username);

        if (!$user || !$user['is_active']) {
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            // Update last login
            Database::update('users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = ?',
                [$user['id']]
            );

            // Remove password hash from return value
            unset($user['password_hash']);
            return $user;
        }

        return null;
    }

    /**
     * Create new user
     */
    public static function create(array $data): int
    {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        return Database::insert('users', [
            'username' => $data['username'],
            'password_hash' => $hashedPassword,
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'station_id' => $data['station_id'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Update user password
     */
    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        Database::update('users',
            ['password_hash' => $hashedPassword],
            'id = ?',
            [$userId]
        );

        return true;
    }

    /**
     * Check if user has permission for action
     */
    public static function hasPermission(array $user, string $permission): bool
    {
        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }

        // Define role permissions
        $permissions = [
            'manager' => ['view_all', 'manage_orders', 'manage_transfers', 'view_reports'],
            'operator' => ['view_station', 'record_sales', 'view_stock'],
            'viewer' => ['view_station', 'view_reports']
        ];

        $userRole = $user['role'];
        return in_array($permission, $permissions[$userRole] ?? []);
    }
}

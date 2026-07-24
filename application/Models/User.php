<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function createResetToken(string $email, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET reset_token = :token, reset_token_expires_at = :expires_at
            WHERE email = :email
        ");
        return $stmt->execute([
            'token'      => $token,
            'expires_at' => $expiresAt,
            'email'      => $email,
        ]);
    }

    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE reset_token = :token
              AND reset_token_expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET password_hash = :hash, reset_token = NULL, reset_token_expires_at = NULL
            WHERE id = :id
        ");
        return $stmt->execute([
            'hash' => $passwordHash,
            'id'   => $id,
        ]);
    }

    public function getAllStaff(): array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, last_login_at FROM users WHERE role = 'staff' ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

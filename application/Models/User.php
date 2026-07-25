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

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, created_at, last_login_at FROM users ORDER BY role ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, role, status)
            VALUES (:name, :email, :password_hash, :role, :status)
        ");
        $stmt->execute([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password_hash' => $data['password_hash'],
            'role'          => $data['role'] ?? 'client',
            'status'        => $data['status'] ?? 'active',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function storeVerificationCode(string $email, string $code, string $expiresAt): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET verification_code = :code, verification_code_expires_at = :expires_at
            WHERE email = :email
        ");
        return $stmt->execute([
            'code'       => $code,
            'expires_at' => $expiresAt,
            'email'      => $email,
        ]);
    }

    public function findByVerificationCode(string $email, string $code): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE email = :email
              AND verification_code = :code
              AND verification_code_expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['email' => $email, 'code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function clearVerificationCode(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET verification_code = NULL, verification_code_expires_at = NULL
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    public function storeActivationToken(int $id, string $token): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET activation_token = :token, status = 'pending_activation'
            WHERE id = :id
        ");
        return $stmt->execute(['token' => $token, 'id' => $id]);
    }

    public function findByActivationToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users
            WHERE activation_token = :token
              AND status = 'pending_activation'
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function activateAccount(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET password_hash = :hash, status = 'active', activation_token = NULL
            WHERE id = :id
        ");
        return $stmt->execute(['hash' => $passwordHash, 'id' => $id]);
    }

    public function incrementFailedLogin(int $id): int
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET failed_login_attempts = failed_login_attempts + 1
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);

        $user = $this->findById($id);
        return (int)($user['failed_login_attempts'] ?? 0);
    }

    public function lockAccount(int $id, string $lockedUntil): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET locked_until = :locked_until, failed_login_attempts = 0
            WHERE id = :id
        ");
        $stmt->execute(['locked_until' => $lockedUntil, 'id' => $id]);
    }

    public function resetLoginAttempts(int $id): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET failed_login_attempts = 0, locked_until = NULL
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmailIncludingDeleted(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1");
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
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, last_login_at FROM users WHERE role = 'staff' AND deleted_at IS NULL ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAll(): array
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, status, created_at, last_login_at FROM users WHERE deleted_at IS NULL ORDER BY role ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(10, min($perPage, 50));
        $where = ['u.deleted_at IS NULL'];
        $params = [];
        if (($filters['search'] ?? '') !== '') {
            $like = '%' . $filters['search'] . '%';
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email)';
            $params += ['search_name' => $like, 'search_email' => $like];
        }
        if (($filters['role'] ?? '') !== '') {
            $where[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['login'] ?? '') === 'never') {
            $where[] = 'u.last_login_at IS NULL';
        } elseif (($filters['login'] ?? '') === 'logged_in') {
            $where[] = 'u.last_login_at IS NOT NULL';
        } elseif (($filters['login'] ?? '') === 'recent_30') {
            $where[] = 'u.last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $sorts = ['name_asc' => 'u.name ASC', 'newest' => 'u.created_at DESC', 'last_login' => 'u.last_login_at IS NULL, u.last_login_at DESC', 'role' => 'u.role ASC, u.name ASC'];
        $orderBy = $sorts[$filters['sort'] ?? 'name_asc'] ?? $sorts['name_asc'];
        $stmt = $this->db->prepare("SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login_at, c.id AS client_id FROM users u LEFT JOIN clients c ON c.user_id = u.id {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages];
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
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET failed_login_attempts = failed_login_attempts + 1
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);

            $user = $this->findById($id);
            return (int)($user['failed_login_attempts'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function lockAccount(int $id, string $lockedUntil): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET locked_until = :locked_until, failed_login_attempts = 0
                WHERE id = :id
            ");
            $stmt->execute(['locked_until' => $lockedUntil, 'id' => $id]);
        } catch (\Throwable $e) {
            // Silently ignore if column does not exist yet
        }
    }

    public function resetLoginAttempts(int $id): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET failed_login_attempts = 0, locked_until = NULL
                WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
        } catch (\Throwable $e) {
            // Silently ignore if column does not exist yet
        }
    }

    public function delete(int $id): bool
    {
        return $this->softDelete($id);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function bulkSoftDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->softDelete((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET deleted_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function bulkRestore(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->restore((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    public function getSoftDeleted(): array
    {
        $stmt = $this->db->query("SELECT id, name, email, role, status, deleted_at FROM users WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        return $stmt->fetchAll();
    }
}

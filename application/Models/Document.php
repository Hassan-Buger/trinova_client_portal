<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Document extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByClientAndDirection(int $clientId, string $direction): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, u.role AS uploaded_by_role
            FROM documents d
            JOIN users u ON u.id = d.uploaded_by_user_id
            WHERE d.client_id = :client_id AND d.direction = :direction
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([
            'client_id' => $clientId,
            'direction' => $direction
        ]);
        return $stmt->fetchAll();
    }

    public function getByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, u.role AS uploaded_by_role
            FROM documents d
            LEFT JOIN users u ON u.id = d.uploaded_by_user_id
            WHERE d.client_id = :client_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getRecentCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM documents");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO documents (client_id, uploaded_by_user_id, direction, filename, stored_path, description, status)
            VALUES (:client_id, :uploaded_by_user_id, :direction, :filename, :stored_path, :description, :status)
        ");
        $stmt->execute([
            'client_id'           => $data['client_id'],
            'uploaded_by_user_id' => $data['uploaded_by_user_id'],
            'direction'           => $data['direction'],
            'filename'            => $data['filename'],
            'stored_path'         => $data['stored_path'],
            'description'        => $data['description'] ?? null,
            'status'              => $data['status'] ?? 'Ready',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, c_user.name AS client_name
            FROM documents d
            JOIN users u ON u.id = d.uploaded_by_user_id
            JOIN clients c ON c.id = d.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function paginateWithDetails(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(10, min($perPage, 50));
        $where = [];
        $params = [];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(d.filename LIKE :search_filename OR d.description LIKE :search_description OR c_user.name LIKE :search_client OR u.name LIKE :search_uploader)';
            $like = '%' . $search . '%';
            $params['search_filename'] = $like;
            $params['search_description'] = $like;
            $params['search_client'] = $like;
            $params['search_uploader'] = $like;
        }

        if (!empty($filters['client_id'])) {
            $where[] = 'd.client_id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }
        if (!empty($filters['direction'])) {
            $where[] = 'd.direction = :direction';
            $params['direction'] = $filters['direction'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'd.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'd.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $params['date_to'] = $filters['date_to'];
        }

        $extension = "LOWER(SUBSTRING_INDEX(d.filename, '.', -1))";
        $knownExtensions = "'pdf','doc','docx','xls','xlsx','csv','png','jpg','jpeg','zip','txt'";
        $fileTypeConditions = [
            'pdf' => "{$extension} = 'pdf'",
            'word' => "{$extension} IN ('doc','docx')",
            'spreadsheet' => "{$extension} IN ('xls','xlsx','csv')",
            'image' => "{$extension} IN ('png','jpg','jpeg')",
            'archive' => "{$extension} = 'zip'",
            'text' => "{$extension} = 'txt'",
            'other' => "{$extension} NOT IN ({$knownExtensions})",
        ];
        $fileType = (string) ($filters['file_type'] ?? '');
        if (isset($fileTypeConditions[$fileType])) {
            $where[] = $fileTypeConditions[$fileType];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $joins = "
            JOIN users u ON u.id = d.uploaded_by_user_id
            JOIN clients c ON c.id = d.client_id
            JOIN users c_user ON c_user.id = c.user_id
        ";

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM documents d {$joins} {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sortOptions = [
            'newest' => 'd.created_at DESC',
            'oldest' => 'd.created_at ASC',
            'client_asc' => 'c_user.name ASC, d.created_at DESC',
            'filename_asc' => 'd.filename ASC',
        ];
        $orderBy = $sortOptions[$filters['sort'] ?? 'newest'] ?? $sortOptions['newest'];

        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, c_user.name AS client_name
            FROM documents d
            {$joins}
            {$whereSql}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public function getStatuses(): array
    {
        $stmt = $this->db->query("SELECT DISTINCT status FROM documents WHERE status <> '' ORDER BY status ASC");
        return array_column($stmt->fetchAll(), 'status');
    }
}

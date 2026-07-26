<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class DocumentRequest extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM document_requests WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getOutstandingByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM document_requests
            WHERE client_id = :client_id AND status != 'Completed'
            ORDER BY due_date ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getAllByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM document_requests
            WHERE client_id = :client_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT dr.*, u.name AS created_by_name, c_user.name AS client_name
            FROM document_requests dr
            JOIN users u ON u.id = dr.created_by_user_id
            JOIN clients c ON c.id = dr.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY dr.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO document_requests (client_id, created_by_user_id, title, description, due_date, status)
            VALUES (:client_id, :created_by_user_id, :title, :description, :due_date, :status)
        ");
        $stmt->execute([
            'client_id'          => $data['client_id'],
            'created_by_user_id' => $data['created_by_user_id'],
            'title'              => $data['title'],
            'description'        => $data['description'] ?? null,
            'due_date'           => $data['due_date'],
            'status'             => $data['status'] ?? 'Awaiting Client',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE document_requests SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function getOverdueCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM document_requests 
            WHERE status != 'Completed' AND due_date < CURRENT_DATE()
        ");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function paginateWithDetails(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(10, min($perPage, 50));
        $where = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $like = '%' . $filters['search'] . '%';
            $where[] = '(dr.title LIKE :search_title OR dr.description LIKE :search_description OR c_user.name LIKE :search_client)';
            $params += ['search_title' => $like, 'search_description' => $like, 'search_client' => $like];
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'dr.client_id = :client_id';
            $params['client_id'] = (int)$filters['client_id'];
        }
        if (!empty($filters['created_by'])) {
            $where[] = 'dr.created_by_user_id = :created_by';
            $params['created_by'] = (int)$filters['created_by'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'dr.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['due_from'] ?? '') !== '') {
            $where[] = 'dr.due_date >= :due_from';
            $params['due_from'] = $filters['due_from'];
        }
        if (($filters['due_to'] ?? '') !== '') {
            $where[] = 'dr.due_date <= :due_to';
            $params['due_to'] = $filters['due_to'];
        }
        if (($filters['timing'] ?? '') === 'overdue') {
            $where[] = "dr.due_date < CURRENT_DATE() AND dr.status <> 'Completed'";
        } elseif (($filters['timing'] ?? '') === 'upcoming') {
            $where[] = "dr.due_date >= CURRENT_DATE() AND dr.status <> 'Completed'";
        }

        $joins = 'JOIN users u ON u.id = dr.created_by_user_id JOIN clients c ON c.id = dr.client_id JOIN users c_user ON c_user.id = c.user_id';
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM document_requests dr {$joins} {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $sorts = [
            'due_asc' => 'dr.due_date ASC',
            'due_desc' => 'dr.due_date DESC',
            'newest' => 'dr.created_at DESC',
            'client_asc' => 'c_user.name ASC, dr.due_date ASC',
        ];
        $orderBy = $sorts[$filters['sort'] ?? 'due_asc'] ?? $sorts['due_asc'];
        $stmt = $this->db->prepare("SELECT dr.*, u.name AS created_by_name, c_user.name AS client_name FROM document_requests dr {$joins} {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages];
    }
}

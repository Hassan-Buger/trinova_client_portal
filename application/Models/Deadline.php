<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Deadline extends Model
{
    public function getAllByClient(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM deadlines
            WHERE client_id = :client_id
            ORDER BY due_date ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getUpcomingByClient(int $clientId): array
    {
        return $this->getAllByClient($clientId);
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, c_user.name AS client_name
            FROM deadlines d
            JOIN clients c ON c.id = d.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY d.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO deadlines (client_id, type, due_date, status)
            VALUES (:client_id, :type, :due_date, :status)
        ");
        $stmt->execute([
            'client_id' => $data['client_id'],
            'type'      => $data['type'],
            'due_date'  => $data['due_date'],
            'status'    => $data['status'] ?? 'Pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE deadlines SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function paginateWithDetails(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(10, min($perPage, 50));
        $where = [];
        $params = [];
        if (($filters['search'] ?? '') !== '') {
            $where[] = 'c_user.name LIKE :search_client';
            $params['search_client'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['client_id'])) {
            $where[] = 'd.client_id = :client_id';
            $params['client_id'] = (int)$filters['client_id'];
        }
        if (($filters['type'] ?? '') !== '') {
            $where[] = 'd.type = :type';
            $params['type'] = $filters['type'];
        }
        if (($filters['status'] ?? '') !== '') {
            $where[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['due_from'] ?? '') !== '') {
            $where[] = 'd.due_date >= :due_from';
            $params['due_from'] = $filters['due_from'];
        }
        if (($filters['due_to'] ?? '') !== '') {
            $where[] = 'd.due_date <= :due_to';
            $params['due_to'] = $filters['due_to'];
        }
        if (($filters['timing'] ?? '') === 'overdue') {
            $where[] = "d.due_date < CURRENT_DATE() AND d.status <> 'Completed'";
        } elseif (($filters['timing'] ?? '') === 'upcoming') {
            $where[] = "d.due_date >= CURRENT_DATE() AND d.status <> 'Completed'";
        }

        $joins = 'JOIN clients c ON c.id = d.client_id JOIN users c_user ON c_user.id = c.user_id';
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM deadlines d {$joins} {$whereSql}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $sorts = ['due_asc' => 'd.due_date ASC', 'due_desc' => 'd.due_date DESC', 'newest' => 'd.created_at DESC', 'client_asc' => 'c_user.name ASC, d.due_date ASC'];
        $orderBy = $sorts[$filters['sort'] ?? 'due_asc'] ?? $sorts['due_asc'];
        $stmt = $this->db->prepare("SELECT d.*, c_user.name AS client_name FROM deadlines d {$joins} {$whereSql} ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'per_page' => $perPage, 'total_pages' => $totalPages];
    }
}

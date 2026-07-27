<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Client extends Model
{
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email
            FROM clients c
            JOIN users u ON u.id = c.user_id
            WHERE c.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email
            FROM clients c
            JOIN users u ON u.id = c.user_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getAllWithUsers(): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email, u.status AS user_status
            FROM clients c
            JOIN users u ON u.id = c.user_id
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function paginate(string $search = '', int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(5, min($perPage, 50));
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = "WHERE u.name LIKE :search_name OR u.email LIKE :search_email OR c.phone LIKE :search_phone";
            $like = '%' . $search . '%';
            $params = [
                'search_name' => $like,
                'search_email' => $like,
                'search_phone' => $like,
            ];
        }

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM clients c
            JOIN users u ON u.id = c.user_id
            {$where}
        ");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email, u.status AS user_status
            FROM clients c
            JOIN users u ON u.id = c.user_id
            {$where}
            ORDER BY u.name ASC
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

    public function countForExport(string $search = ''): int
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE u.name LIKE :name OR u.email LIKE :email OR c.phone LIKE :phone';
            $like = '%' . $search . '%';
            $params = ['name'=>$like, 'email'=>$like, 'phone'=>$like];
        }
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM clients c JOIN users u ON u.id=c.user_id {$where}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getExportBatch(string $search, int $offset, int $limit = 250): array
    {
        $limit=max(1,min($limit,500));
        $offset=max(0,$offset);
        $where='';
        $params=[];
        if($search!==''){
            $where='WHERE u.name LIKE :name OR u.email LIKE :email OR c.phone LIKE :phone';
            $like='%'.$search.'%';
            $params=['name'=>$like,'email'=>$like,'phone'=>$like];
        }
        $stmt=$this->db->prepare("
            SELECT c.id AS client_id,c.phone,c.address,c.aml_status,c.notes,
                   u.name AS contact_name,u.email,u.status AS user_status,
                   e.id AS entity_id,e.company_name,e.company_number,e.tax_reference,e.attributes,
                   GROUP_CONCAT(DISTINCT du.name ORDER BY du.name SEPARATOR '; ') AS directors,
                   (SELECT d.due_date FROM deadlines d
                    WHERE d.entity_id=e.id AND (LOWER(d.type) LIKE '%account%' OR LOWER(d.type) LIKE '%filing%')
                    ORDER BY d.due_date ASC LIMIT 1) AS filing_deadline
            FROM clients c
            JOIN users u ON u.id=c.user_id
            LEFT JOIN client_entities e ON e.id=(
                SELECT e2.id FROM client_entities e2 WHERE e2.client_id=c.id
                ORDER BY CASE WHEN e2.entity_scope='company' THEN 0 ELSE 1 END,e2.id ASC LIMIT 1
            )
            LEFT JOIN entity_directors ed ON ed.entity_id=e.id
            LEFT JOIN users du ON du.id=ed.user_id
            {$where}
            GROUP BY c.id,c.phone,c.address,c.aml_status,c.notes,u.name,u.email,u.status,
                     e.id,e.company_name,e.company_number,e.tax_reference,e.attributes
            ORDER BY COALESCE(e.company_name,u.name) ASC,c.id ASC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAmlActionRequiredCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM clients 
            WHERE aml_status = 'Action Required'
        ");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO clients (user_id, phone, address, aml_status, notes)
            VALUES (:user_id, :phone, :address, :aml_status, :notes)
        ");
        $stmt->execute([
            'user_id'    => $data['user_id'],
            'phone'      => $data['phone'] ?? null,
            'address'    => $data['address'] ?? null,
            'aml_status' => $data['aml_status'] ?? 'Action Required',
            'notes'      => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $client = $this->findById($id);
        if ($client && !empty($client['user_id'])) {
            $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmtUser->execute(['user_id' => $client['user_id']]);
        }
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

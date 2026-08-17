<?php

require_once ROOT_PATH . 'core/Model.php';

class Document extends Model {
    protected $table = 'documents';

    public function getDocumentsWithDetails($employeeId = null) {
        $sql = "SELECT d.*, cat.name as category_name, e.first_name, e.last_name, e.employee_code
                FROM documents d
                JOIN document_categories cat ON d.category_id = cat.id
                JOIN employees e ON d.employee_id = e.id";
        if ($employeeId) {
            $sql .= " WHERE d.employee_id = ? ORDER BY d.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        $sql .= " ORDER BY d.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getContractsWithDetails($employeeId = null) {
        $sql = "SELECT c.*, e.first_name, e.last_name, e.employee_code, d.name as department_name
                FROM contracts c
                JOIN employees e ON c.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";
        if ($employeeId) {
            $sql .= " WHERE c.employee_id = ? ORDER BY c.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        $sql .= " ORDER BY c.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getExpiringContracts($days = 30) {
        $sql = "SELECT c.*, e.first_name, e.last_name, e.employee_code
                FROM contracts c
                JOIN employees e ON c.employee_id = e.id
                WHERE c.status = 'Active' 
                AND c.end_date IS NOT NULL 
                AND c.end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
                ORDER BY c.end_date ASC";
        return $this->db->fetchAll($sql, [$days]);
    }
}

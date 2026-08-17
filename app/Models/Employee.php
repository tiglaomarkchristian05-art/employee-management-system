<?php

require_once ROOT_PATH . 'core/Model.php';

class Employee extends Model {
    protected $table = 'employees';

    public function getAllWithDetails() {
        $sql = "SELECT e.*, d.name as department_name, p.title as position_title, b.name as branch_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN branches b ON e.branch_id = b.id
                ORDER BY e.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getDetailsById($id) {
        $sql = "SELECT e.*, d.name as department_name, p.title as position_title, b.name as branch_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                LEFT JOIN branches b ON e.branch_id = b.id
                WHERE e.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function getActiveCount() {
        $row = $this->db->fetchOne("SELECT COUNT(*) as cnt FROM employees WHERE status IN ('Active', 'Probationary')");
        return $row['cnt'] ?? 0;
    }
}

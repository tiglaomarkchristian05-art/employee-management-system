<?php

require_once ROOT_PATH . 'core/Model.php';

class Admin extends Model {
    public function getAuditLogs() {
        $sql = "SELECT a.*, u.username, r.name as role_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY a.id DESC LIMIT 500";
        return $this->db->fetchAll($sql);
    }

    public function getDepartments() {
        $sql = "SELECT d.*, e.first_name, e.last_name, (SELECT COUNT(*) FROM employees emp WHERE emp.department_id = d.id) as total_employees
                FROM departments d
                LEFT JOIN employees e ON d.manager_id = e.id";
        return $this->db->fetchAll($sql);
    }

    public function getPositions() {
        $sql = "SELECT p.*, d.name as department_name FROM positions p JOIN departments d ON p.department_id = d.id";
        return $this->db->fetchAll($sql);
    }

    public function getRoles() {
        return $this->db->fetchAll("SELECT * FROM roles");
    }

    public function getSystemSettings() {
        return $this->db->fetchAll("SELECT * FROM system_settings");
    }
}

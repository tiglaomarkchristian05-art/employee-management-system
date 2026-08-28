<?php

require_once ROOT_PATH . 'core/Model.php';

class User extends Model {
    protected $table = 'users';

    public function findByUsername($usernameOrEmail) {
        // Map Moses Group demo email credentials seamlessly
        if (in_array($usernameOrEmail, ['admin@mosesgroup.ph', 'admin@apexhr.com'])) $usernameOrEmail = 'admin';
        if (in_array($usernameOrEmail, ['hr@mosesgroup.ph', 'hr@apexhr.com'])) $usernameOrEmail = 'hr_manager';
        if (in_array($usernameOrEmail, ['employee@mosesgroup.ph', 'employee@apexhr.com'])) $usernameOrEmail = 'employee';

        $sql = "SELECT u.*, r.name as role_name, e.first_name, e.last_name, e.email, e.department_id, d.name as department_name, e.photo
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN employees e ON u.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE u.username = ? OR e.email = ?";
        return $this->db->fetchOne($sql, [$usernameOrEmail, $usernameOrEmail]);
    }

    public function getAllUsersWithDetails() {
        $sql = "SELECT u.*, r.name as role_name, e.employee_code, e.first_name, e.last_name, e.email as emp_email
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN employees e ON u.employee_id = e.id
                ORDER BY u.id DESC";
        return $this->db->fetchAll($sql);
    }
}

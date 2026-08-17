<?php

require_once ROOT_PATH . 'core/Model.php';

class Benefit extends Model {
    protected $table = 'benefit_plans';

    public function getClaimsWithDetails($employeeId = null) {
        $sql = "SELECT c.*, b.name as benefit_name, b.type as benefit_type, e.first_name, e.last_name, e.employee_code
                FROM benefit_claims c
                JOIN benefit_plans b ON c.benefit_id = b.id
                JOIN employees e ON c.employee_id = e.id";
        if ($employeeId) {
            $sql .= " WHERE c.employee_id = ? ORDER BY c.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        $sql .= " ORDER BY c.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getEmployeeEnrollments($employeeId) {
        $sql = "SELECT eb.*, b.name as benefit_name, b.type, b.coverage_amount, b.monthly_cost
                FROM employee_benefits eb
                JOIN benefit_plans b ON eb.benefit_id = b.id
                WHERE eb.employee_id = ?";
        return $this->db->fetchAll($sql, [$employeeId]);
    }
}

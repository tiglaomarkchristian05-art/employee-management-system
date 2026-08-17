<?php

require_once ROOT_PATH . 'core/Model.php';

class Loan extends Model {
    protected $table = 'loans';

    public function getLoansWithDetails($employeeId = null) {
        $sql = "SELECT l.*, e.first_name, e.last_name, e.employee_code, d.name as department_name
                FROM loans l
                JOIN employees e ON l.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";
        if ($employeeId) {
            $sql .= " WHERE l.employee_id = ? ORDER BY l.id DESC";
            return $this->db->fetchAll($sql, [$employeeId]);
        }
        $sql .= " ORDER BY l.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function getLoanPayments($loanId) {
        return $this->db->fetchAll("SELECT * FROM loan_payments WHERE loan_id = ? ORDER BY payment_date DESC", [$loanId]);
    }
}

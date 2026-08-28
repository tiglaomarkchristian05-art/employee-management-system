<?php

require_once ROOT_PATH . 'core/Model.php';

class Compliance extends Model {
    protected $table = 'gov_contributions';

    public function getContributionsWithDetails($month = null, $year = null, $employeeId = null) {
        $sql = "SELECT c.*, e.first_name, e.last_name, e.employee_code, e.sss_no, e.philhealth_no, e.pagibig_no, e.tin_no, d.name as department_name
                FROM gov_contributions c
                JOIN employees e ON c.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id";
        
        $params = [];
        $conditions = [];
        if ($month) {
            $conditions[] = "c.period_month = ?";
            $params[] = $month;
        }
        if ($year) {
            $conditions[] = "c.period_year = ?";
            $params[] = $year;
        }
        if ($employeeId) {
            $conditions[] = "c.employee_id = ?";
            $params[] = $employeeId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY c.id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function getUpcomingDeadlines() {
        return $this->db->fetchAll("SELECT * FROM gov_deadlines ORDER BY due_date ASC");
    }

    public function getBIR2316Data($employeeId, $year) {
        $sql = "SELECT e.*, d.name as department_name, p.title as position_title,
                       SUM(c.gross_salary) as total_gross,
                       SUM(c.sss_employee + c.philhealth_employee + c.pagibig_employee) as total_non_taxable_statutory,
                       SUM(c.bir_tax_withheld) as total_tax_withheld
                FROM employees e
                LEFT JOIN gov_contributions c ON e.id = c.employee_id AND c.period_year = ?
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN positions p ON e.position_id = p.id
                WHERE e.id = ?
                GROUP BY e.id";
        return $this->db->fetchOne($sql, [$year, $employeeId]);
    }
}

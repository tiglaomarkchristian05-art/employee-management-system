<?php

require_once ROOT_PATH . 'core/Model.php';

class Dashboard extends Model {
    public function getAdminSummary() {
        $sql = "SELECT
            (SELECT COUNT(*) FROM employees WHERE status IN ('Active','Probationary')) AS total_active_employees,
            (SELECT COUNT(*) FROM training_courses WHERE is_active = 1 AND end_date >= CURRENT_DATE) AS upcoming_trainings,
            (SELECT COUNT(*) FROM training_registrations WHERE status = 'Applied') AS pending_training_requests,
            (SELECT COALESCE(ROUND(100 * SUM(status = 'Completed') / NULLIF(COUNT(*), 0), 1), 0) FROM training_registrations) AS training_completion_rate,
            (SELECT COUNT(*) FROM documents WHERE status IN ('Submitted','Under Review')) AS documents_pending_review,
            (SELECT COUNT(*) FROM documents WHERE expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY) AND status NOT IN ('Expired','Rejected')) AS documents_expiring_soon,
            (SELECT COUNT(*) FROM contracts WHERE status = 'Active' AND end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY)) AS contracts_expiring_soon,
            (SELECT COUNT(*) FROM employees WHERE status IN ('Active','Probationary') AND (NULLIF(TRIM(sss_no),'') IS NULL OR NULLIF(TRIM(philhealth_no),'') IS NULL OR NULLIF(TRIM(pagibig_no),'') IS NULL OR NULLIF(TRIM(tin_no),'') IS NULL)) AS missing_government_records,
            ((SELECT COUNT(*) FROM gov_deadlines WHERE status = 'Overdue' OR (status = 'Upcoming' AND due_date < CURRENT_DATE)) +
             (SELECT COUNT(*) FROM employees WHERE status IN ('Active','Probationary') AND (NULLIF(TRIM(sss_no),'') IS NULL OR NULLIF(TRIM(philhealth_no),'') IS NULL OR NULLIF(TRIM(pagibig_no),'') IS NULL OR NULLIF(TRIM(tin_no),'') IS NULL))) AS compliance_issues,
            (SELECT COUNT(*) FROM benefit_claims WHERE status IN ('Submitted','Under Review','Returned')) AS pending_benefit_applications,
            (SELECT COUNT(*) FROM loans WHERE status = 'Active') AS active_loans,
            (SELECT COUNT(*) FROM loans WHERE status IN ('Submitted','Under Review','Returned')) AS pending_loan_applications,
            (SELECT COUNT(*) FROM separations WHERE status IN ('Submitted','Under Review')) AS pending_separation_requests,
            (SELECT COUNT(DISTINCT separation_id) FROM clearances WHERE status = 'Pending') AS pending_exit_clearances";
        return $this->db->fetchOne($sql) ?: [];
    }

    public function getAdminRecentActivities($limit = 10) {
        $limit = max(1, min(25, (int)$limit));
        return $this->db->fetchAll(
            "SELECT a.action, a.module, a.description, a.created_at, u.username,
                    COALESCE(NULLIF(TRIM(CONCAT(e.first_name, ' ', e.last_name)), ''), u.username, 'System') AS actor
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE a.module <> 'Authentication'
             ORDER BY a.id DESC LIMIT {$limit}"
        );
    }

    public function getAdminPendingActions($limit = 12) {
        $limit = max(1, min(30, (int)$limit));
        $sql = "SELECT item_type, title, detail, event_date, route, priority FROM (
            SELECT 'Training' item_type, CONCAT(e.first_name, ' ', e.last_name) title, CONCAT('Training request: ', c.title) detail, r.registration_date event_date, 'training' route, 2 priority
              FROM training_registrations r JOIN employees e ON e.id=r.employee_id JOIN training_courses c ON c.id=r.course_id WHERE r.status='Applied'
            UNION ALL
            SELECT 'Document', CONCAT(e.first_name, ' ', e.last_name), CONCAT('Review document: ', d.title), d.upload_date, 'documents', 2
              FROM documents d JOIN employees e ON e.id=d.employee_id WHERE d.status IN ('Submitted','Under Review')
            UNION ALL
            SELECT 'Contract', CONCAT(e.first_name, ' ', e.last_name), CONCAT('Contract approval: ', c.contract_type), c.created_at, 'documents_contracts', 2
              FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.approval_status='Pending'
            UNION ALL
            SELECT 'Benefit', CONCAT(e.first_name, ' ', e.last_name), CONCAT('Benefit claim: ', c.claim_type), c.submitted_at, 'benefits', 2
              FROM benefit_claims c JOIN employees e ON e.id=c.employee_id WHERE c.status IN ('Submitted','Under Review','Returned')
            UNION ALL
            SELECT 'Loan', CONCAT(e.first_name, ' ', e.last_name), CONCAT('Loan application: ', l.loan_type), l.requested_at, 'benefits_loans', 2
              FROM loans l JOIN employees e ON e.id=l.employee_id WHERE l.status IN ('Submitted','Under Review','Returned')
            UNION ALL
            SELECT 'Separation', CONCAT(e.first_name, ' ', e.last_name), CONCAT('Separation request: ', s.separation_type), s.created_at, 'separation', 1
              FROM separations s JOIN employees e ON e.id=s.employee_id WHERE s.status IN ('Submitted','Under Review')
            UNION ALL
            SELECT 'Clearance', CONCAT(e.first_name, ' ', e.last_name), CONCAT(c.department_name, ' clearance pending'), s.created_at, CONCAT('separation_clearance&id=',s.id), 1
              FROM clearances c JOIN separations s ON s.id=c.separation_id JOIN employees e ON e.id=s.employee_id WHERE c.status='Pending'
        ) pending ORDER BY priority ASC, event_date ASC LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    public function getAdminDeadlines($limit = 12) {
        $limit = max(1, min(30, (int)$limit));
        $sql = "SELECT item_type, title, detail, due_date, route FROM (
            SELECT 'Training' item_type, c.title, CONCAT(c.course_type, ' • ', COALESCE(c.venue,'Venue TBA')) detail, c.start_date due_date, 'training' route
              FROM training_courses c WHERE c.is_active=1 AND c.start_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY)
            UNION ALL
            SELECT 'Contract', CONCAT(e.first_name, ' ', e.last_name), CONCAT(c.contract_type, ' contract expires'), c.end_date, 'documents_contracts'
              FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.status='Active' AND c.end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY)
            UNION ALL
            SELECT 'Document', CONCAT(e.first_name, ' ', e.last_name), CONCAT(d.title, ' expires'), d.expiry_date, 'documents'
              FROM documents d JOIN employees e ON e.id=d.employee_id WHERE d.expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY) AND d.status NOT IN ('Expired','Rejected')
            UNION ALL
            SELECT 'Compliance', g.agency_name, CONCAT(g.form_type, ': ', COALESCE(g.description,'')), g.due_date, 'compliance'
              FROM gov_deadlines g WHERE g.status!='Submitted' AND g.due_date <= DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY)
        ) deadlines ORDER BY due_date ASC LIMIT {$limit}";
        return $this->db->fetchAll($sql);
    }

    public function getEmployeeSummary($employeeId, $userId) {
        $sql = "SELECT
            (SELECT COUNT(*) FROM training_registrations r JOIN training_courses c ON c.id=r.course_id WHERE r.employee_id=e.id AND r.status IN ('Assigned','Applied','Confirmed','Attended') AND c.end_date>=CURRENT_DATE) AS upcoming_trainings,
            (SELECT COUNT(*) FROM training_registrations WHERE employee_id=e.id AND status='Completed') AS completed_trainings,
            (SELECT COUNT(*) FROM training_registrations WHERE employee_id=e.id AND status='Completed') AS certificates,
            (SELECT COUNT(*) FROM documents WHERE employee_id=e.id AND status IN ('Pending','Submitted','Under Review','Returned','Renewal Required')) AS pending_documents,
            (SELECT COUNT(*) FROM documents WHERE employee_id=e.id AND expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL 60 DAY) AND status NOT IN ('Expired','Rejected')) AS expiring_documents,
            (SELECT COUNT(*) FROM benefit_plans bp WHERE NOT EXISTS (SELECT 1 FROM employee_benefits eb WHERE eb.employee_id=e.id AND eb.benefit_id=bp.id AND eb.status IN ('Active','Pending'))) AS available_benefits,
            (SELECT COUNT(*) FROM benefit_claims WHERE employee_id=e.id) AS benefit_applications,
            (SELECT COUNT(*) FROM benefit_claims WHERE employee_id=e.id AND status IN ('Submitted','Under Review','Returned')) AS pending_benefit_applications,
            (SELECT COUNT(*) FROM loans WHERE employee_id=e.id AND status='Active') AS active_loans,
            (SELECT COALESCE(SUM(balance_remaining),0) FROM loans WHERE employee_id=e.id AND status IN ('Approved','Active')) AS remaining_loan_balance,
            (SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0) AS notifications,
            ((NULLIF(TRIM(e.sss_no),'') IS NULL) + (NULLIF(TRIM(e.philhealth_no),'') IS NULL) + (NULLIF(TRIM(e.pagibig_no),'') IS NULL) + (NULLIF(TRIM(e.tin_no),'') IS NULL)) AS missing_government_records
         FROM employees e WHERE e.id=?";
        return $this->db->fetchOne($sql, [(int)$userId, (int)$employeeId]) ?: [];
    }

    public function getEmployeeDashboardDetails($employeeId, $userId) {
        $employeeId = (int)$employeeId;
        $userId = (int)$userId;
        $details = [];
        $details['upcoming_trainings'] = $this->db->fetchAll(
            "SELECT r.status, c.title, c.start_date, c.end_date, c.venue FROM training_registrations r JOIN training_courses c ON c.id=r.course_id WHERE r.employee_id=? AND r.status IN ('Assigned','Applied','Confirmed','Attended') AND c.end_date>=CURRENT_DATE ORDER BY c.start_date ASC LIMIT 5",
            [$employeeId]
        );
        $details['contract'] = $this->db->fetchOne("SELECT contract_type, start_date, end_date, status, approval_status FROM contracts WHERE employee_id=? ORDER BY id DESC LIMIT 1", [$employeeId]);
        $details['government'] = $this->db->fetchOne("SELECT sss_no, philhealth_no, pagibig_no, tin_no FROM employees WHERE id=?", [$employeeId]) ?: [];
        $details['contributions'] = $this->db->fetchOne(
            "SELECT COUNT(*) periods, COALESCE(SUM(sss_employee),0) sss, COALESCE(SUM(philhealth_employee),0) philhealth, COALESCE(SUM(pagibig_employee),0) pagibig, COALESCE(SUM(bir_tax_withheld),0) bir, COALESCE(SUM(sss_employee+philhealth_employee+pagibig_employee+bir_tax_withheld),0) total FROM gov_contributions WHERE employee_id=? AND period_year=YEAR(CURRENT_DATE)",
            [$employeeId]
        ) ?: [];
        $details['benefits'] = $this->db->fetchAll("SELECT b.name, b.type, eb.status FROM employee_benefits eb JOIN benefit_plans b ON b.id=eb.benefit_id WHERE eb.employee_id=? ORDER BY eb.id DESC LIMIT 5", [$employeeId]);
        $details['loans'] = $this->db->fetchAll("SELECT loan_type, balance_remaining, monthly_deduction, status FROM loans WHERE employee_id=? AND status IN ('Submitted','Under Review','Returned','Approved','Released','Active') ORDER BY id DESC LIMIT 5", [$employeeId]);
        $details['separation'] = $this->db->fetchOne("SELECT id, separation_type, effective_date, status FROM separations WHERE employee_id=? ORDER BY id DESC LIMIT 1", [$employeeId]);
        $details['clearances'] = [];
        if (!empty($details['separation']['id'])) {
            $details['clearances'] = $this->db->fetchAll("SELECT department_name, status FROM clearances WHERE separation_id=? ORDER BY id ASC", [(int)$details['separation']['id']]);
        }
        $details['notifications'] = $this->db->fetchAll("SELECT id, title, message, type, link, module, related_id, is_read, created_at FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 6", [$userId]);
        return $details;
    }
}

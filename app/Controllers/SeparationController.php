<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Separation.php';
require_once APP_PATH . 'Models/Employee.php';

class SeparationController extends Controller {
    public function index() {
        Auth::requireAuth();

        $separationModel = new Separation();
        $employeeModel = new Employee();

        $data = [
            'separations' => $separationModel->getSeparationsWithDetails(),
            'employees'   => $employeeModel->getAllWithDetails()
        ];

        $this->view('separation/index', $data);
    }

    public function clearance() {
        Auth::requireAuth();
        $separationId = intval($_GET['id'] ?? 1);

        $separationModel = new Separation();
        $sep = $separationModel->db->fetchOne("SELECT s.*, e.first_name, e.last_name, e.employee_code, e.hire_date, d.name as department_name, p.title as position_title FROM separations s JOIN employees e ON s.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN positions p ON e.position_id = p.id WHERE s.id = ?", [$separationId]);

        if (!$sep) {
            die("Separation record not found.");
        }

        $data = [
            'separation' => $sep,
            'clearances' => $separationModel->getClearanceStatus($separationId),
            'assets'     => $separationModel->getAssetReturns($separationId),
            'final_pay'  => $separationModel->getFinalPay($separationId)
        ];

        $this->view('separation/clearance', $data);
    }

    public function coe() {
        Auth::requireAuth();
        $separationId = intval($_GET['id'] ?? 1);

        $separationModel = new Separation();
        $sep = $separationModel->db->fetchOne("SELECT s.*, e.first_name, e.last_name, e.employee_code, e.hire_date, e.basic_salary, d.name as department_name, p.title as position_title FROM separations s JOIN employees e ON s.employee_id = e.id LEFT JOIN departments d ON e.department_id = d.id LEFT JOIN positions p ON e.position_id = p.id WHERE s.id = ?", [$separationId]);

        $data = [
            'separation' => $sep
        ];

        $this->view('separation/coe', $data);
    }

    public function initiate() {
        Auth::requireRole(['Super Admin', 'HR Manager', 'Employee']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $user = Auth::user();
        $empId = intval($_POST['employee_id'] ?? $user['employee_id']);
        $type = sanitize_input($_POST['separation_type'] ?? 'Resignation');
        $notice = $_POST['notice_date'] ?? date('Y-m-d');
        $effective = $_POST['effective_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $reason = sanitize_input($_POST['reason'] ?? '');

        $separationModel = new Separation();
        $sepId = $separationModel->create([
            'employee_id'     => $empId,
            'separation_type' => $type,
            'notice_date'     => $notice,
            'effective_date'  => $effective,
            'reason'          => $reason,
            'status'          => 'Pending Clearance'
        ]);

        // Auto-generate 5 Department Clearances
        $departments = ['HR', 'Finance', 'IT', 'Security', 'Manager'];
        foreach ($departments as $dept) {
            $separationModel->db->insert('clearances', [
                'separation_id'   => $sepId,
                'department_name' => $dept,
                'status'          => 'Pending'
            ]);
        }

        // Auto-generate Asset Returns
        $separationModel->db->insert('asset_returns', [
            'separation_id' => $sepId,
            'item_name'     => 'Company ID & RFID Pass',
            'returned'      => 0
        ]);

        // Auto-generate Draft Final Pay
        $separationModel->db->insert('final_pays', [
            'separation_id'             => $sepId,
            'basic_pay_due'             => 25000.00,
            'unused_leave_encashment'   => 5000.00,
            'thirteenth_month_prorated' => 15000.00,
            'loan_deductions'           => 0.00,
            'net_final_pay'             => 45000.00,
            'status'                    => 'Draft'
        ]);

        AuditLogger::log('INITIATE_EXIT', 'Separation Management', "Initiated exit clearance for Employee ID {$empId}");
        $this->json('success', 'Separation workflow initiated. Department clearance routing active.');
    }

    public function updateClearance() {
        Auth::requireRole(['Super Admin', 'HR Manager', 'Department Head']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $clearanceId = intval($_POST['clearance_id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? 'Cleared');
        $comments = sanitize_input($_POST['comments'] ?? 'Approved');
        $user = Auth::user();

        $separationModel = new Separation();
        $separationModel->db->update('clearances', [
            'status'         => $status,
            'cleared_by'     => $user['full_name'],
            'clearance_date' => date('Y-m-d H:i:s'),
            'comments'       => $comments
        ], "id = ?", [$clearanceId]);

        AuditLogger::log('CLEARANCE_UPDATE', 'Separation Management', "Updated clearance ID {$clearanceId} status to {$status}");
        $this->json('success', 'Department clearance status updated!');
    }
}

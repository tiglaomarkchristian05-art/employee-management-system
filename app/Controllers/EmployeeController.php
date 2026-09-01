<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Admin.php';

class EmployeeController extends Controller {
    public function index() {
        Auth::requireAdmin();

        $employeeModel = new Employee();
        $adminModel = new Admin();

        $data = [
            'employees'   => $employeeModel->getAllWithDetails(),
            'departments' => $adminModel->getDepartments(),
            'positions'   => $adminModel->getPositions()
        ];

        $this->view('admin/employees', $data);
    }

    public function store() {
        Auth::requireAdmin();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $password = !empty($_POST['account_password'])
            ? (string)$_POST['account_password']
            : bin2hex(random_bytes(8));
        if (strlen($password) < 8) {
            $this->json('error', 'The employee account password must be at least 8 characters.');
        }

        $employeeModel = new Employee();
        $code = 'EMP-' . date('Y') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        
        $data = [
            'employee_code' => $code,
            'first_name'    => sanitize_input($_POST['first_name'] ?? ''),
            'last_name'     => sanitize_input($_POST['last_name'] ?? ''),
            'email'         => sanitize_input($_POST['email'] ?? ''),
            'phone'         => sanitize_input($_POST['phone'] ?? ''),
            'gender'        => sanitize_input($_POST['gender'] ?? 'Male'),
            'dob'           => $_POST['dob'] ?? date('Y-m-d'),
            'hire_date'     => $_POST['hire_date'] ?? date('Y-m-d'),
            'department_id' => intval($_POST['department_id'] ?? 1),
            'position_id'   => intval($_POST['position_id'] ?? 1),
            'branch_id'     => intval($_POST['branch_id'] ?? 1),
            'status'        => sanitize_input($_POST['status'] ?? 'Active'),
            'basic_salary'  => floatval($_POST['basic_salary'] ?? 25000.00),
            'sss_no'        => sanitize_input($_POST['sss_no'] ?? ''),
            'philhealth_no' => sanitize_input($_POST['philhealth_no'] ?? ''),
            'pagibig_no'    => sanitize_input($_POST['pagibig_no'] ?? ''),
            'tin_no'        => sanitize_input($_POST['tin_no'] ?? '')
        ];

        $id = $employeeModel->create($data);

        $salary = $data['basic_salary'];
        $sssEE = min($salary * 0.045, 1350);
        $sssER = min($salary * 0.095, 2850);
        $phEE  = min($salary * 0.025, 2500);
        $phER  = min($salary * 0.025, 2500);
        $pagEE = 200.00;
        $pagER = 200.00;
        $tax   = max(($salary - ($sssEE + $phEE + $pagEE) - 20833) * 0.15, 0);
        $totalStat = $sssEE + $sssER + $phEE + $phER + $pagEE + $pagER + $tax;

        $employeeModel->db->insert('gov_contributions', [
            'employee_id'         => $id,
            'period_month'        => intval(date('n')),
            'period_year'         => intval(date('Y')),
            'gross_salary'        => $salary,
            'sss_employee'        => $sssEE,
            'sss_employer'        => $sssER,
            'philhealth_employee' => $phEE,
            'philhealth_employer' => $phER,
            'pagibig_employee'    => $pagEE,
            'pagibig_employer'    => $pagER,
            'bir_tax_withheld'    => $tax,
            'total_statutory'     => $totalStat
        ]);

        $employeeModel->db->insert('contracts', [
            'employee_id'    => $id,
            'contract_type'  => 'Employment',
            'start_date'     => $data['hire_date'],
            'end_date'       => date('Y-m-d', strtotime('+1 year', strtotime($data['hire_date']))),
            'status'         => 'Active',
            'approval_status' => 'Approved'
        ]);

        $userModel = new User();
        $username = !empty($_POST['account_username']) ? sanitize_input($_POST['account_username']) : (!empty($data['email']) ? strtolower($data['email']) : strtolower(str_replace(' ', '.', $data['first_name'] . '.' . $data['last_name'])));
        $existing = $userModel->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            $username = strtolower($data['first_name'] . '.' . $data['last_name'] . rand(10, 99));
        }

        $userModel->create([
            'username'    => $username,
            'password'    => password_hash($password, PASSWORD_BCRYPT),
            'role_id'     => 4,
            'employee_id' => $id,
            'is_active'   => 1
        ]);

        AuditLogger::log('CREATE_EMPLOYEE', 'Employee Directory', "Added new employee {$data['first_name']} {$data['last_name']} and created Employee account '{$username}'");

        $this->json('success', "New hire '{$data['first_name']} {$data['last_name']}' registered successfully! System Account created (Username: {$username}, Default Password: {$password}).", ['id' => $id, 'username' => $username]);
    }

    public function delete() {
        Auth::requireAdmin();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json('error', 'Invalid employee ID.');
        }

        $employeeModel = new Employee();
        $emp = $employeeModel->find($id);
        if (!$emp) {
            $this->json('error', 'Employee record not found.');
        }

        $employeeModel->delete($id);
        AuditLogger::log('DELETE_EMPLOYEE', 'Employee Directory', "Deleted employee record for {$emp['first_name']} {$emp['last_name']} (ID: {$id})");
        $this->json('success', 'Employee record deleted successfully.');
    }

    public function get() {
        Auth::requireAdmin();
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->json('error', 'Invalid employee ID.');
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->getDetailsById($id);

        if (!$employee) {
            $this->json('error', 'Employee record not found.');
        }

        $this->json('success', 'Employee record loaded.', ['employee' => $employee]);
    }

    public function update() {
        Auth::requireAdmin();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json('error', 'Invalid employee ID.');
        }

        $employeeModel = new Employee();
        $existing = $employeeModel->find($id);
        if (!$existing) {
            $this->json('error', 'Employee record not found.');
        }

        $data = [
            'first_name'    => sanitize_input($_POST['first_name'] ?? $existing['first_name']),
            'last_name'     => sanitize_input($_POST['last_name'] ?? $existing['last_name']),
            'email'         => sanitize_input($_POST['email'] ?? $existing['email']),
            'phone'         => sanitize_input($_POST['phone'] ?? $existing['phone']),
            'gender'        => sanitize_input($_POST['gender'] ?? $existing['gender']),
            'dob'           => $_POST['dob'] ?? $existing['dob'],
            'hire_date'     => $_POST['hire_date'] ?? $existing['hire_date'],
            'department_id' => intval($_POST['department_id'] ?? $existing['department_id']),
            'position_id'   => intval($_POST['position_id'] ?? $existing['position_id']),
            'branch_id'     => intval($_POST['branch_id'] ?? $existing['branch_id']),
            'status'        => sanitize_input($_POST['status'] ?? $existing['status']),
            'basic_salary'  => floatval($_POST['basic_salary'] ?? $existing['basic_salary']),
            'sss_no'        => sanitize_input($_POST['sss_no'] ?? $existing['sss_no']),
            'philhealth_no' => sanitize_input($_POST['philhealth_no'] ?? $existing['philhealth_no']),
            'pagibig_no'    => sanitize_input($_POST['pagibig_no'] ?? $existing['pagibig_no']),
            'tin_no'        => sanitize_input($_POST['tin_no'] ?? $existing['tin_no'])
        ];

        $employeeModel->update($id, $data);
        AuditLogger::log('UPDATE_EMPLOYEE', 'Employee Directory', "Updated employee record for {$data['first_name']} {$data['last_name']} (ID: {$id})");

        $this->json('success', 'Employee profile updated successfully!');
    }
}

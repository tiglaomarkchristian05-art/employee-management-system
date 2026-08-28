<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Benefit.php';
require_once APP_PATH . 'Models/Loan.php';
require_once APP_PATH . 'Models/Employee.php';

class BenefitsController extends Controller {
    public function index() {
        Auth::requireAuth();

        $benefitModel = new Benefit();
        $employeeModel = new Employee();
        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'plans'       => $benefitModel->all(),
            'claims'      => $benefitModel->getClaimsWithDetails($empId),
            'enrollments' => $empId ? $benefitModel->getEmployeeEnrollments($empId) : [],
            'employees'   => $isHRAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('benefits/index', $data);
    }

    public function loans() {
        Auth::requireAuth();

        $loanModel = new Loan();
        $employeeModel = new Employee();
        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'loans'     => $loanModel->getLoansWithDetails($empId),
            'employees' => $isHRAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('benefits/loans', $data);
    }

    public function submitClaim() {
        Auth::requireSelfService();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $user = Auth::user();
        $empId = Auth::isAdmin() ? intval($_POST['employee_id'] ?? 0) : Auth::employeeId();
        if ($empId <= 0) $this->json('error', 'A valid employee is required.');

        $benefitId = intval($_POST['benefit_id'] ?? 1);
        $claimType = sanitize_input($_POST['claim_type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $orNum = sanitize_input($_POST['receipt_number'] ?? 'OR-' . rand(10000, 99999));

        if ($amount <= 0 || empty($claimType)) {
            $this->json('error', 'Please provide a valid claim amount and description.');
        }

        $status = Auth::hasRole(['Super Admin', 'HR Manager']) ? 'Approved' : 'Pending';

        $benefitModel = new Benefit();
        $benefitModel->db->insert('benefit_claims', [
            'employee_id'    => $empId,
            'benefit_id'     => $benefitId,
            'claim_type'     => $claimType,
            'amount'         => $amount,
            'receipt_number' => $orNum,
            'status'         => $status
        ]);

        AuditLogger::log('SUBMIT_CLAIM', 'Benefits Management', "Submitted claim for ₱{$amount} under Benefit ID {$benefitId}");
        $this->json('success', 'Benefit reimbursement claim submitted successfully!');
    }

    public function requestLoan() {
        Auth::requireSelfService();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $user = Auth::user();
        $empId = Auth::isAdmin() ? intval($_POST['employee_id'] ?? 0) : Auth::employeeId();
        if ($empId <= 0) $this->json('error', 'A valid employee is required.');

        $loanType = sanitize_input($_POST['loan_type'] ?? 'Emergency');
        $principal = floatval($_POST['principal_amount'] ?? 0);
        $term = intval($_POST['term_months'] ?? 12);
        $rate = floatval($_POST['interest_rate'] ?? 2.0);

        if ($principal <= 0 || $term <= 0) {
            $this->json('error', 'Invalid loan terms.');
        }

        $totalInterest = $principal * ($rate / 100);
        $totalPayable = $principal + $totalInterest;
        $monthly = $totalPayable / $term;

        $status = Auth::hasRole(['Super Admin', 'HR Manager']) ? 'Active' : 'Pending';

        $loanModel = new Loan();
        $loanModel->create([
            'employee_id'       => $empId,
            'loan_type'         => $loanType,
            'principal_amount'  => $principal,
            'interest_rate'     => $rate,
            'term_months'       => $term,
            'monthly_deduction' => $monthly,
            'total_payable'     => $totalPayable,
            'balance_remaining' => $totalPayable,
            'status'            => $status
        ]);

        AuditLogger::log('REQUEST_LOAN', 'Loans Management', "Submitted {$loanType} loan request of ₱{$principal}");
        $this->json('success', 'Loan request submitted successfully!');
    }

    public function adminRequestLoan() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        if ($empId <= 0) {
            $this->json('error', 'Please select a valid employee.');
        }

        $loanType = sanitize_input($_POST['loan_type'] ?? 'Emergency');
        $principal = floatval($_POST['principal_amount'] ?? 0);
        $term = intval($_POST['term_months'] ?? 12);
        $rate = floatval($_POST['interest_rate'] ?? 2.0);
        $status = sanitize_input($_POST['status'] ?? 'Active');

        if ($principal <= 0 || $term <= 0) {
            $this->json('error', 'Invalid loan terms.');
        }

        $totalInterest = $principal * ($rate / 100);
        $totalPayable = $principal + $totalInterest;
        $monthly = $totalPayable / $term;

        $loanModel = new Loan();
        $loanId = $loanModel->create([
            'employee_id'       => $empId,
            'loan_type'         => $loanType,
            'principal_amount'  => $principal,
            'interest_rate'     => $rate,
            'term_months'       => $term,
            'monthly_deduction' => $monthly,
            'total_payable'     => $totalPayable,
            'balance_remaining' => $totalPayable,
            'status'            => $status
        ]);

        AuditLogger::log('ADMIN_REQUEST_LOAN', 'Loans Management', "HR/Admin filed {$loanType} loan of ₱{$principal} for Employee ID {$empId}");
        $this->json('success', "Loan request for Employee ID {$empId} recorded successfully!", ['loan_id' => $loanId]);
    }

    public function adminSubmitClaim() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        if ($empId <= 0) {
            $this->json('error', 'Please select a valid employee.');
        }

        $benefitId = intval($_POST['benefit_id'] ?? 1);
        $claimType = sanitize_input($_POST['claim_type'] ?? 'Reimbursement Claim');
        $amount = floatval($_POST['amount'] ?? 0);
        $orNum = sanitize_input($_POST['receipt_number'] ?? 'OR-' . rand(10000, 99999));
        $status = sanitize_input($_POST['status'] ?? 'Approved');

        if ($amount <= 0 || empty($claimType)) {
            $this->json('error', 'Please provide a valid claim amount and description.');
        }

        $benefitModel = new Benefit();
        $claimId = $benefitModel->db->insert('benefit_claims', [
            'employee_id'    => $empId,
            'benefit_id'     => $benefitId,
            'claim_type'     => $claimType,
            'amount'         => $amount,
            'receipt_number' => $orNum,
            'status'         => $status
        ]);

        AuditLogger::log('ADMIN_SUBMIT_CLAIM', 'Benefits Management', "HR/Admin submitted claim of ₱{$amount} for Employee ID {$empId}");
        $this->json('success', 'Reimbursement record created successfully!', ['claim_id' => $claimId]);
    }

    public function adminGrantAllowance() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        if ($empId <= 0) {
            $this->json('error', 'Please select a valid employee.');
        }

        $benefitId = intval($_POST['benefit_id'] ?? 3);
        $allowanceTitle = sanitize_input($_POST['allowance_title'] ?? 'Pre-Deployment Allowance');
        $amount = floatval($_POST['amount'] ?? 0);
        $refNo = sanitize_input($_POST['reference_no'] ?? 'ALLOW-' . rand(10000, 99999));

        if ($amount <= 0) {
            $this->json('error', 'Please enter a valid allowance amount.');
        }

        $benefitModel = new Benefit();
        
        $existingEnrollment = $benefitModel->db->fetchOne("SELECT id FROM employee_benefits WHERE employee_id = ? AND benefit_id = ?", [$empId, $benefitId]);
        if (!$existingEnrollment) {
            $benefitModel->db->insert('employee_benefits', [
                'employee_id'     => $empId,
                'benefit_id'      => $benefitId,
                'enrollment_date' => date('Y-m-d'),
                'status'          => 'Active'
            ]);
        }

        $claimId = $benefitModel->db->insert('benefit_claims', [
            'employee_id'    => $empId,
            'benefit_id'     => $benefitId,
            'claim_type'     => "Allowance: {$allowanceTitle}",
            'amount'         => $amount,
            'receipt_number' => $refNo,
            'status'         => 'Approved'
        ]);

        AuditLogger::log('ADMIN_GRANT_ALLOWANCE', 'Benefits Management', "HR/Admin granted ₱{$amount} allowance ({$allowanceTitle}) to Employee ID {$empId}");
        $this->json('success', 'Allowance granted and recorded successfully!', ['claim_id' => $claimId]);
    }

    public function updateClaimStatus() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->json('error', 'Invalid CSRF token.', [], 403);

        $claimId = intval($_POST['claim_id'] ?? $_GET['claim_id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? $_GET['status'] ?? 'Approved');

        if ($claimId <= 0 || !in_array($status, ['Approved', 'Rejected', 'Pending', 'Paid'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $this->json('error', 'Invalid parameters.');
            }
            redirect('index.php?page=benefits');
        }

        $benefitModel = new Benefit();
        $benefitModel->db->update('benefit_claims', ['status' => $status], "id = ?", [$claimId]);

        AuditLogger::log('UPDATE_CLAIM_STATUS', 'Benefits Management', "Claim ID {$claimId} status updated to {$status}");

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json('success', "Reimbursement claim status updated to {$status}!");
        } else {
            redirect('index.php?page=benefits');
        }
    }

    public function updateLoanStatus() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->json('error', 'Invalid CSRF token.', [], 403);

        $loanId = intval($_POST['loan_id'] ?? $_GET['loan_id'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? $_GET['status'] ?? 'Active');

        if ($loanId <= 0 || !in_array($status, ['Active', 'Approved', 'Rejected', 'Pending', 'Fully Paid'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                $this->json('error', 'Invalid parameters.');
            }
            redirect('index.php?page=benefits_loans');
        }

        $loanModel = new Loan();
        $loanModel->db->update('loans', ['status' => $status], "id = ?", [$loanId]);

        AuditLogger::log('UPDATE_LOAN_STATUS', 'Loans Management', "Loan ID {$loanId} status updated to {$status}");

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json('success', "Loan status updated to {$status}!");
        } else {
            redirect('index.php?page=benefits_loans');
        }
    }

    public function deleteClaim() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->json('error', 'Invalid CSRF token.', [], 403);

        $claimId = intval($_POST['claim_id'] ?? $_GET['claim_id'] ?? 0);
        if ($claimId > 0) {
            $benefitModel = new Benefit();
            $benefitModel->db->delete('benefit_claims', "id = ?", [$claimId]);
            AuditLogger::log('DELETE_CLAIM', 'Benefits Management', "Deleted benefit claim ID {$claimId}");
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json('success', 'Claim record deleted successfully.');
        } else {
            redirect('index.php?page=benefits');
        }
    }

    public function deleteLoan() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) $this->json('error', 'Invalid CSRF token.', [], 403);

        $loanId = intval($_POST['loan_id'] ?? $_GET['loan_id'] ?? 0);
        if ($loanId > 0) {
            $loanModel = new Loan();
            $loanModel->db->delete('loans', "id = ?", [$loanId]);
            AuditLogger::log('DELETE_LOAN', 'Loans Management', "Deleted loan ID {$loanId}");
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $this->json('success', 'Loan record deleted successfully.');
        } else {
            redirect('index.php?page=benefits_loans');
        }
    }

    public function addLoanPayment() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $loanId = intval($_POST['loan_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        $method = sanitize_input($_POST['payment_method'] ?? 'Payroll Deduction');
        $refNo  = sanitize_input($_POST['reference_no'] ?? 'PMT-' . rand(10000, 99999));
        $payDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');

        if ($loanId <= 0 || $amount <= 0) {
            $this->json('error', 'Please provide a valid loan and payment amount.');
        }

        $loanModel = new Loan();
        $loan = $loanModel->find($loanId);
        if (!$loan) {
            $this->json('error', 'Loan record not found.');
        }

        $loanModel->db->insert('loan_payments', [
            'loan_id'        => $loanId,
            'payment_date'   => $payDate,
            'amount'         => $amount,
            'payment_method' => $method,
            'reference_no'   => $refNo
        ]);

        $newBalance = max(0, $loan['balance_remaining'] - $amount);
        $newStatus  = ($newBalance <= 0) ? 'Fully Paid' : 'Active';

        $loanModel->update($loanId, [
            'balance_remaining' => $newBalance,
            'status'            => $newStatus
        ]);

        AuditLogger::log('ADD_LOAN_PAYMENT', 'Loans Management', "Recorded payment of ₱{$amount} for Loan ID {$loanId}. Remaining balance: ₱{$newBalance}");
        $this->json('success', 'Loan payment recorded successfully!', ['new_balance' => $newBalance, 'status' => $newStatus]);
    }

    public function createAllowance() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $name = sanitize_input($_POST['name'] ?? '');
        $type = sanitize_input($_POST['type'] ?? 'Monthly');
        $amount = floatval($_POST['amount'] ?? 0);
        $isTaxable = isset($_POST['is_taxable']) ? 1 : 0;
        $desc = sanitize_input($_POST['description'] ?? '');

        if (empty($name) || $amount <= 0) {
            $this->json('error', 'Please enter a valid allowance name and amount.');
        }

        $benefitModel = new Benefit();
        $allowanceId = $benefitModel->db->insert('allowances', [
            'name'        => $name,
            'type'        => $type,
            'amount'      => $amount,
            'is_taxable'  => $isTaxable,
            'description' => $desc
        ]);

        AuditLogger::log('CREATE_ALLOWANCE', 'Benefits Management', "Created allowance plan '{$name}' of ₱{$amount}");
        $this->json('success', "Allowance plan '{$name}' created successfully!", ['id' => $allowanceId]);
    }

    public function assignAllowance() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        $allowanceId = intval($_POST['allowance_id'] ?? 0);
        $effDate = !empty($_POST['effective_date']) ? $_POST['effective_date'] : date('Y-m-d');

        if ($empId <= 0 || $allowanceId <= 0) {
            $this->json('error', 'Please select a valid employee and allowance.');
        }

        $benefitModel = new Benefit();
        $assignId = $benefitModel->db->insert('employee_allowances', [
            'employee_id'    => $empId,
            'allowance_id'   => $allowanceId,
            'effective_date' => $effDate,
            'status'         => 'Active'
        ]);

        AuditLogger::log('ASSIGN_ALLOWANCE', 'Benefits Management', "Assigned Allowance ID {$allowanceId} to Employee ID {$empId}");
        $this->json('success', 'Allowance successfully assigned to employee!', ['id' => $assignId]);
    }

    public function deleteAllowance() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $allowanceId = intval($_POST['allowance_id'] ?? $_GET['allowance_id'] ?? 0);
        if ($allowanceId <= 0) {
            $this->json('error', 'Invalid allowance ID.');
        }

        $benefitModel = new Benefit();
        $benefitModel->db->delete('allowances', "id = ?", [$allowanceId]);

        AuditLogger::log('DELETE_ALLOWANCE', 'Benefits Management', "Deleted allowance ID {$allowanceId}");
        $this->json('success', 'Allowance plan deleted successfully.');
    }
}

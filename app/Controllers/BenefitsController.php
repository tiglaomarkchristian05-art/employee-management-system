<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Benefit.php';
require_once APP_PATH . 'Models/Loan.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Notification.php';

class BenefitsController extends Controller {
    private $benefits;private $notifications;
    public function __construct(){$this->benefits=new Benefit();$this->notifications=new Notification();}
    public function index() {
        Auth::requireAuth();

        $employeeModel = new Employee();
        $user = Auth::user();
        $isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
        $empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'is_admin'=>$isHRAdmin,'plans'=>$this->benefits->getPlans(!$isHRAdmin),
            'claims'=>$this->benefits->getClaimsWithDetails($empId),
            'enrollments'=>$empId?$this->benefits->getEmployeeEnrollments($empId):[],
            'employees'=>$isHRAdmin?$employeeModel->getAllWithDetails():[],
            'departments'=>$isHRAdmin?$this->benefits->db->fetchAll("SELECT id,name FROM departments ORDER BY name"):[]
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
            'employees' => $isHRAdmin ? $employeeModel->getAllWithDetails() : [],
            'programs' => $loanModel->programs(!$isHRAdmin),
            'departments' => $isHRAdmin ? $loanModel->db->fetchAll('SELECT id,name FROM departments ORDER BY name') : [],
            'is_admin' => $isHRAdmin
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

    public function storeBenefit(){$this->adminBenefitPost();try{$data=$this->benefitInput();$data['created_by']=Auth::user()['id'];$id=$this->benefits->create($data);AuditLogger::log('CREATE_BENEFIT','Benefits Management',"Created benefit {$data['name']} (ID: {$id})",null,['record_type'=>'benefit','record_id'=>$id,'new_value'=>['name'=>$data['name'],'type'=>$data['type'],'is_active'=>$data['is_active']]]);$this->json('success','Benefit type created.',['id'=>(int)$id]);}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function updateBenefit(){$this->adminBenefitPost();$id=(int)($_POST['id']??0);if(!$this->benefits->getPlan($id))$this->json('error','Benefit not found.',[],404);try{$this->benefits->update($id,$this->benefitInput());AuditLogger::log('UPDATE_BENEFIT','Benefits Management',"Updated benefit ID {$id}");$this->json('success','Benefit updated.');}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function toggleBenefit(){$this->adminBenefitPost();$id=(int)($_POST['id']??0);$b=$this->benefits->getPlan($id);if(!$b)$this->json('error','Benefit not found.',[],404);$active=$b['is_active']?0:1;$this->benefits->update($id,['is_active'=>$active]);AuditLogger::log('TOGGLE_BENEFIT','Benefits Management',"Benefit ID {$id} ".($active?'activated':'deactivated'));$this->json('success','Benefit '.($active?'activated.':'deactivated.'));}
    private function benefitInput(){$name=sanitize_input($_POST['name']??'');$type=sanitize_input($_POST['type']??'Health');$max=(float)($_POST['max_amount']??0);$start=$_POST['application_start']??'';$end=$_POST['application_end']??'';if($name===''||!in_array($type,['Health','Allowance','Incentive','Bonus'],true))throw new RuntimeException('Valid benefit name and type are required.');if($max<0)throw new RuntimeException('Maximum amount cannot be negative.');if($start&&$end&&strtotime($end)<strtotime($start))throw new RuntimeException('Application end date must follow its start date.');$deps=array_filter(array_map('intval',(array)($_POST['eligible_department_ids']??[])));$statuses=array_intersect((array)($_POST['eligible_employment_statuses']??['Active','Probationary']),['Active','Probationary','Resigned','Terminated','Retired']);return ['name'=>$name,'type'=>$type,'description'=>trim($_POST['description']??''),'eligibility_rules'=>trim($_POST['eligibility_rules']??''),'required_documents'=>trim($_POST['required_documents']??''),'max_amount'=>$max?:null,'coverage_amount'=>$max,'application_start'=>$start?:null,'application_end'=>$end?:null,'minimum_tenure_months'=>max(0,(int)($_POST['minimum_tenure_months']??0)),'eligible_employment_statuses'=>implode(',',$statuses)?:'Active,Probationary','eligible_department_ids'=>$deps?implode(',',$deps):null,'is_active'=>!empty($_POST['is_active'])?1:0];}

    public function applyBenefit(){$this->employeeBenefitPost();$emp=Auth::employeeId();$benefit=(int)($_POST['benefit_id']??0);$type=sanitize_input($_POST['claim_type']??'Benefit Application');$amount=(float)($_POST['amount']??0);if($amount<=0||$type==='')$this->json('error','Provide a valid amount and description.',[],422);[$ok,$reason]=$this->benefits->eligibility($benefit,$emp);if(!$ok)$this->json('error',$reason,[],422);if($this->benefits->db->fetchOne("SELECT id FROM benefit_claims WHERE employee_id=? AND benefit_id=? AND status IN ('Submitted','Under Review','Returned','Approved','Processing','Released')",[$emp,$benefit]))$this->json('error','You already have an active application for this benefit.',[],422);try{$file=$this->benefitFile();$id=$this->benefits->apply($benefit,$emp,['claim_type'=>$type,'amount'=>$amount,'receipt_number'=>sanitize_input($_POST['receipt_number']??''),'application_notes'=>trim($_POST['application_notes']??''),'requirement_file'=>$file['path'],'original_name'=>$file['original']]);$this->notifications->createForAdmins('New benefit application','Employee ID '.$emp.' submitted a benefit application.','info','index.php?page=benefits','benefits',$id);AuditLogger::log('SUBMIT_BENEFIT_APPLICATION','Benefits Management',"Submitted application ID {$id}");$this->json('success','Benefit application submitted.',['id'=>(int)$id]);}catch(Throwable $e){if(isset($file))$this->removeBenefitFile($file['path']);$this->json('error',$e->getMessage(),[],422);}}
    public function resubmitBenefit(){$this->employeeBenefitPost();$id=(int)($_POST['id']??0);$current=$this->benefits->getClaim($id);if(!$current||$current['employee_id']!=Auth::employeeId()||$current['status']!=='Returned')$this->json('error','Returned application not found.',[],422);try{$file=$this->benefitFile();$old=$this->benefits->resubmit($id,Auth::employeeId(),['claim_type'=>sanitize_input($_POST['claim_type']??'Benefit Application'),'amount'=>(float)($_POST['amount']??0),'receipt_number'=>sanitize_input($_POST['receipt_number']??''),'application_notes'=>trim($_POST['application_notes']??''),'requirement_file'=>$file['path'],'original_name'=>$file['original']]);$this->removeBenefitFile($old['requirement_file']);$this->notifications->createForAdmins('Benefit application resubmitted','Employee ID '.Auth::employeeId().' resubmitted application ID '.$id.'.','info','index.php?page=benefits','benefits',$id);AuditLogger::log('RESUBMIT_BENEFIT_APPLICATION','Benefits Management',"Resubmitted application ID {$id}");$this->json('success','Application resubmitted for review.');}catch(Throwable $e){if(isset($file))$this->removeBenefitFile($file['path']);$this->json('error',$e->getMessage(),[],422);}}
    public function reviewBenefit(){$this->adminBenefitPost();$id=(int)($_POST['id']??0);$status=sanitize_input($_POST['status']??'');$remarks=trim($_POST['admin_remarks']??'');$c=$this->benefits->getClaim($id);if(!$c)$this->json('error','Application not found.',[],404);$allowed=['Submitted'=>['Under Review','Returned','Approved','Rejected','Cancelled'],'Under Review'=>['Returned','Approved','Rejected'],'Approved'=>['Processing','Cancelled'],'Processing'=>['Released'],'Released'=>['Completed']];if(!in_array($status,$allowed[$c['status']]??[],true))$this->json('error',"Invalid transition from {$c['status']} to {$status}.",[],422);if(in_array($status,['Returned','Rejected'],true)&&$remarks==='')$this->json('error','Remarks are required for Return or Reject.',[],422);$data=['status'=>$status,'admin_remarks'=>$remarks,'reviewed_by'=>Auth::user()['id'],'reviewed_at'=>date('Y-m-d H:i:s')];if($status==='Released')$data['released_at']=date('Y-m-d H:i:s');if($status==='Completed')$data['completed_at']=date('Y-m-d H:i:s');$this->benefits->db->update('benefit_claims',$data,"id=?",[$id]);$this->notifications->createForEmployee($c['employee_id'],'Benefit application '.strtolower($status),"{$c['benefit_name']} is now {$status}.".($remarks?" Remarks: {$remarks}":''),in_array($status,['Approved','Released','Completed'],true)?'success':(in_array($status,['Returned','Rejected'],true)?'warning':'info'),'index.php?page=benefits','benefits',$id);AuditLogger::log('BENEFIT_APPLICATION_'.strtoupper(str_replace(' ','_',$status)),'Benefits Management',"Application ID {$id}: {$status}",null,['record_type'=>'benefit_application','record_id'=>$id,'old_value'=>['status'=>$c['status']],'new_value'=>['status'=>$status,'remarks'=>$remarks]]);$this->json('success',"Application marked {$status}.");}

    public function downloadBenefitFile(){Auth::requireAuth();$c=$this->benefits->getClaim((int)($_GET['id']??0));if(!$c||!$c['requirement_file']){http_response_code(404);exit('Requirement file not found.');}Auth::requireOwnership($c['employee_id']);$root=realpath(ROOT_PATH.'public/uploads/benefits');$path=realpath(ROOT_PATH.'public/'.str_replace(['/',chr(92)],DIRECTORY_SEPARATOR,ltrim($c['requirement_file'],'/'.chr(92))));if(!$root||!$path||!is_file($path)||strpos($path,$root.DIRECTORY_SEPARATOR)!==0){http_response_code(404);exit('File not found.');}header('Content-Type:'.(mime_content_type($path)?:'application/octet-stream'));header('Content-Disposition:attachment; filename="benefit-requirement.'.pathinfo($path,PATHINFO_EXTENSION).'"');header('X-Content-Type-Options:nosniff');readfile($path);exit;}
    private function benefitFile(){if(!isset($_FILES['requirement_file'])||$_FILES['requirement_file']['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Upload the required supporting file.');$f=$_FILES['requirement_file'];if($f['size']<1||$f['size']>5242880)throw new RuntimeException('Supporting file must be 5 MB or less.');$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!in_array($ext,['pdf','jpg','jpeg','png'],true)||!in_array($mime,['application/pdf','image/jpeg','image/png'],true))throw new RuntimeException('Only valid PDF, JPG, or PNG files are allowed.');$dir=ROOT_PATH.'public/uploads/benefits/';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to prepare upload storage.');$name=bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],$dir.$name))throw new RuntimeException('Unable to store requirement file.');return ['path'=>'uploads/benefits/'.$name,'original'=>preg_replace('/[^A-Za-z0-9._-]+/','-',basename($f['name']))];}
    private function adminBenefitPost(){Auth::requirePermission('manage_benefits');Auth::requireMethod('POST');$this->csrf();}private function employeeBenefitPost(){Auth::requirePermission('submit_claim');Auth::requireMethod('POST');$this->csrf();}private function csrf(){if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid CSRF token.',[],403);}

    private function removeBenefitFile($relative){$root=realpath(ROOT_PATH.'public/uploads/benefits');$path=realpath(ROOT_PATH.'public/'.str_replace(['/',chr(92)],DIRECTORY_SEPARATOR,ltrim((string)$relative,'/'.chr(92))));if($root&&$path&&is_file($path)&&strpos($path,$root.DIRECTORY_SEPARATOR)===0)unlink($path);}

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

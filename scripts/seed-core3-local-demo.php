<?php
/** Additive Core 3 fixture: localhost CLI or authorized Super Admin presentation action. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Model.php';
require_once dirname(__DIR__) . '/app/Models/Benefit.php';
require_once dirname(__DIR__) . '/app/Models/Document.php';
require_once dirname(__DIR__) . '/app/Models/Loan.php';
require_once dirname(__DIR__) . '/app/Models/Separation.php';
require_once dirname(__DIR__) . '/app/Models/TrainingRecommendation.php';

$presentationMode = defined('CORE3_PRESENTATION_SEED_AUTHORIZED') && CORE3_PRESENTATION_SEED_AUTHORIZED === true;
$localMode = PHP_SAPI === 'cli' && in_array(DB_HOST, ['127.0.0.1', 'localhost', '::1'], true) && DB_NAME === 'apex_hrms' && in_array('--apply', $argv, true);
if (!$presentationMode && !$localMode) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }
    fwrite(STDERR, "Local apex_hrms only. Run: php scripts/seed-core3-local-demo.php --apply\n");
    exit(1);
}
$db = Database::getInstance();
$pdo = $db->getConnection();
$today = new DateTimeImmutable('today');
$date = static fn(string $change): string => $today->modify($change)->format('Y-m-d');
$lookup = static function (string $table, array $where) use ($db): ?array {
    $parts = array_map(static fn(string $column): string => $column . '=?', array_keys($where));
    return $db->fetchOne('SELECT * FROM ' . $table . ' WHERE ' . implode(' AND ', $parts) . ' LIMIT 1', array_values($where)) ?: null;
};
$ensure = static function (string $table, array $where, array $values) use ($db, $lookup): array {
    $existing = $lookup($table, $where);
    if ($existing) return $existing;
    $id = (int)$db->insert($table, $where + $values);
    return $db->fetchOne('SELECT * FROM ' . $table . ' WHERE id=?', [$id]);
};
$required = static function (string $table, array $where) use ($lookup): array {
    return $lookup($table, $where) ?? throw new RuntimeException('Missing required ' . $table . ': ' . json_encode($where));
};
$password = 'Core3!' . bin2hex(random_bytes(7));
$createdAccounts = [];
$createdCredentials = [];

$pdo->beginTransaction();
try {
    $branch = (int)$required('branches', ['id'=>1])['id'];
    $recruitment = (int)$required('departments', ['code'=>'REC'])['id'];
    $documentation = (int)$required('departments', ['code'=>'DOC'])['id'];
    $deployment = (int)$required('departments', ['code'=>'DEP'])['id'];
    $finance = (int)$required('departments', ['code'=>'FIN'])['id'];
    $it = (int)$ensure('departments', ['code'=>'DEMO-IT'], ['name'=>'IT & Systems (Local Demo)','description'=>'LOCAL DEMO: exit-clearance and technical support scenarios.'])['id'];
    $positions = [
        'IT Support Specialist' => $it,
        'Trade Certification Candidate' => $recruitment,
        'Compliance Coordinator' => $documentation,
        'Overseas Documentation Assistant' => $documentation,
        'Finance Assistant' => $finance,
        'Deployment Coordinator' => $deployment,
    ];
    $positionIds = [];
    foreach ($positions as $title => $departmentId) {
        $positionIds[$title] = (int)$ensure('positions', ['title'=>$title, 'department_id'=>$departmentId], ['salary_grade'=>'Demo'])['id'];
    }
    $profiles = [
        ['DEMO-C3-001','Mark','Villanueva','IT Support Specialist','2024-02-12','Active','Male'],
        ['DEMO-C3-002','John','Reyes','Trade Certification Candidate','2023-04-18','Active','Male'],
        ['DEMO-C3-003','Liza','Ramos','Trade Certification Candidate','2026-08-18','Probationary','Female'],
        ['DEMO-C3-004','Angela','Cruz','Overseas Documentation Assistant','2022-05-09','Active','Female'],
        ['DEMO-C3-005','Paolo','Mendoza','Finance Assistant','2021-11-08','Active','Male'],
        ['DEMO-C3-006','Carlo','Garcia','Deployment Coordinator','2020-07-20','Active','Male'],
    ];
    if ($presentationMode) $profiles[] = ['DEMO-C3-007','Ava','Flores','Overseas Documentation Assistant','2021-03-15','Resigned','Female'];
    $employeeIds = [];
    foreach ($profiles as [$code,$first,$last,$position,$hired,$status,$gender]) {
        $email = strtolower($first . '.' . $last) . '@test.local';
        $collision = $lookup('employees', ['email'=>$email]);
        if ($collision && $collision['employee_code'] !== $code) throw new RuntimeException('Email collision for ' . $code);
        $row = $ensure('employees', ['employee_code'=>$code], [
            'first_name'=>$first,'last_name'=>$last,'email'=>$email,'hire_date'=>$hired,
            'department_id'=>$positions[$position],'position_id'=>$positionIds[$position],
            'branch_id'=>$branch,'status'=>$status,'gender'=>$gender,'basic_salary'=>32000
        ]);
        $employeeIds[$first] = (int)$row['id'];
    }
    $employeeIds['Juan'] = (int)$required('employees', ['employee_code'=>'OFW-2026-005'])['id'];
    $employeeIds['Clarissa'] = (int)$required('employees', ['employee_code'=>'OFW-2026-006'])['id'];
    $employeeRole = (int)$required('roles', ['name'=>'Employee'])['id'];
    foreach (['Mark','John','Liza','Angela','Paolo','Carlo','Juan'] as $name) {
        $employeeId = $employeeIds[$name];
        $username = 'demo.' . strtolower($name);
        $existingLink = $lookup('users', ['employee_id'=>$employeeId]);
        if ($existingLink) {
            if ($presentationMode && ($existingLink['username'] !== $username || (int)$existingLink['role_id'] !== $employeeRole)) throw new RuntimeException('Employee account collision for ' . $username);
            continue;
        }
        if ($lookup('users', ['username'=>$username])) throw new RuntimeException('Username collision: ' . $username);
        $accountPassword = $presentationMode ? 'Core3!' . bin2hex(random_bytes(12)) : $password;
        $db->insert('users', [
            'username'=>$username,'password'=>password_hash($accountPassword, PASSWORD_BCRYPT),
            'role_id'=>$employeeRole,'employee_id'=>$employeeId,'is_active'=>$presentationMode ? 0 : 1,'mfa_mode'=>'off'
        ]);
        $createdAccounts[] = $username;
        if ($presentationMode) $createdCredentials[$username] = $accountPassword;
    }
    $hr = (int)$required('users', ['username'=>'hr_manager'])['id'];
    $head = (int)$required('users', ['username'=>'mchen'])['id'];
    $admin = (int)$required('users', ['username'=>'admin'])['id'];
    $headRole = (int)$required('roles', ['name'=>'Department Head'])['id'];
    foreach (['demo.it_approver','demo.finance_approver'] as $username) {
        $existingApprover = $lookup('users', ['username'=>$username]);
        if ($existingApprover && $presentationMode && ((int)$existingApprover['role_id'] !== $headRole || $existingApprover['employee_id'] !== null)) {
            throw new RuntimeException('Approver account collision for ' . $username);
        }
        if (!$existingApprover) {
            $accountPassword = $presentationMode ? 'Core3!' . bin2hex(random_bytes(12)) : $password;
            $db->insert('users', [
                'username'=>$username,'password'=>password_hash($accountPassword, PASSWORD_BCRYPT),
                'role_id'=>$headRole,'employee_id'=>null,'is_active'=>$presentationMode ? 0 : 1,
                'mfa_email'=>str_replace('_','.', $username).'@test.local','mfa_mode'=>'off'
            ]);
            $createdAccounts[] = $username;
            if ($presentationMode) $createdCredentials[$username] = $accountPassword;
        }
    }
    $itApprover = (int)$required('users', ['username'=>'demo.it_approver'])['id'];
    $financeApprover = (int)$required('users', ['username'=>'demo.finance_approver'])['id'];

    // Fresh demo courses are necessary: the original welding course ended before this demo date.
    $tradeCategory = (int)$required('training_categories', ['id'=>4])['id'];
    $privacyCategory = (int)$required('training_categories', ['id'=>5])['id'];
    $welding = (int)$ensure('training_courses', ['title'=>'LOCAL DEMO - SMAW 6G Certification Renewal'], [
        'category_id'=>$tradeCategory,'description'=>'Fictional local test course for renewal comparisons.',
        'training_purpose'=>'Renew expired trade certification','training_need_reason'=>'Certification expires or expired',
        'basis_source'=>'Certification registry','expected_outcome'=>'Renewed SMAW 6G credential',
        'course_type'=>'Internal','duration_hours'=>8,'venue'=>'Local demo workshop',
        'start_date'=>$date('+14 days'),'end_date'=>$date('+15 days'),'status'=>'Scheduled','is_active'=>1,
        'capacity'=>20,'required_skills'=>'SMAW 6G pipe welding','certification_provided'=>'SMAW 6G Pipe Welding Trade Certification'
    ])['id'];
    $privacy = (int)$ensure('training_courses', ['title'=>'LOCAL DEMO - Data Privacy & Information Security'], [
        'category_id'=>$privacyCategory,'description'=>'Fictional local test course for IT skill-gap analysis.',
        'training_purpose'=>'Improve data privacy and information security',
        'training_need_reason'=>'Observed competency gap','basis_source'=>'Skills matrix',
        'expected_outcome'=>'Secure records handling','course_type'=>'Internal','duration_hours'=>4,
        'venue'=>'Local demo classroom','start_date'=>$date('+20 days'),'end_date'=>$date('+21 days'),
        'status'=>'Scheduled','is_active'=>1,'capacity'=>20,
        'required_skills'=>'Data Privacy Information Security','certification_provided'=>'Data Privacy Training Certificate'
    ])['id'];
    $ensure('skills_matrix', ['employee_id'=>$employeeIds['Mark'],'skill_name'=>'Data Privacy Information Security'], [
        'proficiency_level'=>'Beginner','target_level'=>'Advanced','verified_by'=>'LOCAL DEMO HR skill audit'
    ]);
    $ensure('training_registrations', ['employee_id'=>$employeeIds['Mark'],'course_id'=>$privacy], [
        'status'=>'Failed','attendance_percentage'=>100,'quiz_score'=>42,'assessment_result'=>42,
        'result_notes'=>'LOCAL DEMO: low assessment supports skill gap.'
    ]);
    $ensure('training_registrations', ['employee_id'=>$employeeIds['Liza'],'course_id'=>3], [
        'status'=>'Completed','attendance_percentage'=>100,'quiz_score'=>91,'assessment_result'=>91,
        'completed_at'=>$date('-30 days'),'result_notes'=>'LOCAL DEMO: prior completed welding course.'
    ]);
    foreach ([
        ['John','Expired',$date('-20 days')],
        ['Liza','Valid',$date('+500 days')],
        ['Angela','Expiring Soon',$date('+20 days')],
    ] as [$name,$status,$expires]) {
        $certName = $name === 'Angela' ? 'Data Privacy Training Certificate' : 'SMAW 6G Pipe Welding Trade Certification';
        $ensure('employee_certifications', ['employee_id'=>$employeeIds[$name],'certification_name'=>$certName], [
            'certification_type'=>'LOCAL DEMO - Professional','issuing_organization'=>'LOCAL DEMO Training Center',
            'issue_date'=>$date('-1 year'),'expiration_date'=>$expires,'status'=>$status,
            'related_training_id'=>$name === 'Angela' ? $privacy : $welding,
            'verified_by'=>$hr,'verification_date'=>date('Y-m-d H:i:s'),'created_by'=>$hr
        ]);
    }
    $ensure('employee_certifications', ['employee_id'=>$employeeIds['Carlo'],'certification_name'=>'LOCAL DEMO - Security Awareness'], [
        'certification_type'=>'Internal','issuing_organization'=>'LOCAL DEMO Training Center',
        'issue_date'=>$date('-10 days'),'status'=>'Pending Verification','related_training_id'=>$privacy
    ]);

    $contract = static function (int $employeeId, string $kind, string $end, string $status, string $renewal, int $version=1, ?int $previous=null) use ($ensure,$date,$admin): array {
        return $ensure('contracts', ['employee_id'=>$employeeId,'contract_type'=>$kind,'version_no'=>$version], [
            'previous_contract_id'=>$previous,'start_date'=>$date('-1 year'),'end_date'=>$end,
            'status'=>$status,'renewal_status'=>$renewal,'approval_status'=>'Approved',
            'remarks'=>'LOCAL DEMO: date-based contract comparison.','created_by'=>$admin
        ]);
    };
    $sarah = (int)$required('employees', ['employee_code'=>'OFW-2026-002'])['id'];
    $contract($sarah,'LOCAL DEMO - Standard Contract',$date('+180 days'),'Active','Not Required');
    $contract($employeeIds['Angela'],'LOCAL DEMO - Expiring Contract',$date('+25 days'),'Active','For Review');
    $contract($employeeIds['Mark'],'LOCAL DEMO - Expired Contract',$date('-15 days'),'Expired','Renewal Required');
    $old = $contract($employeeIds['Liza'],'LOCAL DEMO - Renewed Contract',$date('-40 days'),'Renewed','Renewed');
    $contract($employeeIds['Liza'],'LOCAL DEMO - Renewed Contract',$date('+365 days'),'Active','Not Required',2,(int)$old['id']);

    $category = (int)$ensure('document_categories', ['name'=>'Supporting Document'], [
        'is_required'=>0,'category_group'=>'Other'
    ])['id'];
    $sourcePdf = dirname(__DIR__) . '/sample-training-material-data-privacy.pdf';
    $pdf = $presentationMode ? 'uploads/documents/core3-presentation-evidence.pdf' : 'uploads/documents/core3-local-demo-evidence.pdf';
    $storedPdf = dirname(__DIR__) . '/public/' . $pdf;
    if ($presentationMode) {
        if (!is_file($storedPdf)) {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
            $pdfGenerator = new \Dompdf\Dompdf();
            $pdfGenerator->loadHtml('<h1>CORE 3 PRESENTATION SAMPLE</h1><p>Fictional document used only to demonstrate document review and expiration workflows.</p>');
            $pdfGenerator->render();
            if (file_put_contents($storedPdf, $pdfGenerator->output()) === false) throw new RuntimeException('Could not create presentation PDF.');
        }
    } else {
        if (!is_file($sourcePdf)) throw new RuntimeException('Required local demo PDF is missing.');
        if (!is_file($storedPdf) && !copy($sourcePdf, $storedPdf)) throw new RuntimeException('Could not prepare local demo document PDF.');
    }
    foreach ([
        ['Angela','LOCAL DEMO - Approved Record','Approved',$date('+365 days')],
        ['Angela','LOCAL DEMO - Expiring Record','Approved',$date('+20 days')],
        ['John','LOCAL DEMO - Expired Record','Expired',$date('-20 days')],
        ['Liza','LOCAL DEMO - Pending Review','Submitted',null],
        ['Mark','LOCAL DEMO - Returned for Correction','Returned',null],
    ] as [$name,$title,$status,$expires]) {
        $document = $ensure('documents', ['employee_id'=>$employeeIds[$name],'title'=>$title], [
            'category_id'=>$category,'file_path'=>$pdf,'original_name'=>$pdf,'mime_type'=>'application/pdf',
            'file_size'=>(string)filesize($storedPdf),'expiry_date'=>$expires,
            'status'=>$status,'remarks'=>'LOCAL DEMO: fictional test document state.',
            'submitted_by'=>$name === 'Mark' ? (int)$required('users', ['username'=>'demo.mark'])['id'] : $hr
        ]);
        if ($document['file_path'] === 'sample-training-material-data-privacy.pdf') {
            $db->update('documents', ['file_path'=>$pdf,'original_name'=>'core3-local-demo-evidence.pdf'], 'id=?', [(int)$document['id']]);
        }
    }

    // Existing plan rules: active Juan is eligible; probationary Liza is not.
    $planName = $presentationMode ? 'CORE3 DEMO - Medical Allowance' : 'Manual Test Medical Allowance';
    $plan = (int)$ensure('benefit_plans', ['name'=>$planName], [
        'type'=>'Allowance','description'=>'CORE 3 fictional presentation plan for eligibility comparison.',
        'coverage_amount'=>1000,'max_amount'=>1000,'eligible_employment_statuses'=>'Active',
        'eligible_department_ids'=>(string)$recruitment,'is_active'=>1,'created_by'=>$admin
    ])['id'];
    $benefits = new Benefit();
    if (!$benefits->eligibility($plan,$employeeIds['Juan'])[0]) throw new RuntimeException('Juan is not eligible under actual plan rules.');
    if ($benefits->eligibility($plan,$employeeIds['Liza'])[0]) throw new RuntimeException('Liza unexpectedly qualifies for active-only plan.');
    $ensure('benefit_claims', ['employee_id'=>$employeeIds['Juan'],'benefit_id'=>$plan,'claim_type'=>'LOCAL DEMO - Medical Support'], [
        'amount'=>1000,'status'=>'Submitted','application_notes'=>'LOCAL DEMO: HR review remains to be performed.'
    ]);

    // Use the existing loan program and its configured rate/term; leave remaining installments unpaid.
    $program = $required('loan_programs', ['name'=>'Company Emergency Loan']);
    $loanModel = new Loan();
    if (!$loanModel->eligibility((int)$program['id'],$employeeIds['Paolo'])[0]) throw new RuntimeException('Paolo does not qualify for the selected loan program.');
    $loan = $lookup('loans', ['employee_id'=>$employeeIds['Paolo'],'loan_program_id'=>(int)$program['id']]);
    if (!$loan) {
        $principal = 12000.0;
        $term = max(6,(int)$program['minimum_term_months']);
        $total = round($principal * (1 + (float)$program['interest_rate'] / 100),2);
        $monthly = round($total/$term,2);
        $loan = $ensure('loans', ['employee_id'=>$employeeIds['Paolo'],'loan_program_id'=>(int)$program['id']], [
            'loan_type'=>$program['name'],'requested_amount'=>$principal,'approved_amount'=>$principal,
            'principal_amount'=>$principal,'interest_rate'=>$program['interest_rate'],'term_months'=>$term,
            'monthly_deduction'=>$monthly,'total_payable'=>$total,'balance_remaining'=>$total-$monthly,
            'status'=>'Active','application_notes'=>'LOCAL DEMO: active repayment scenario.',
            'reviewed_by'=>$hr,'release_date'=>$date('-1 month'),'released_by'=>$hr
        ]);
        $base = round($total/$term,2);
        $used = 0;
        for ($i=1; $i<=$term; $i++) {
            $amount = $i===$term ? round($total-$used,2) : $base;
            $used += $amount;
            $due = $today->modify('-1 month')->modify('+' . ($i-1) . ' month')->format('Y-m-d');
            $ensure('loan_payment_schedules', ['loan_id'=>(int)$loan['id'],'installment_no'=>$i], [
                'due_date'=>$due,'amount_due'=>$amount,'amount_paid'=>$i===1?$amount:0,
                'status'=>$i===1?'Paid':'Pending','paid_at'=>$i===1?date('Y-m-d H:i:s'):null
            ]);
        }
        $first = $required('loan_payment_schedules', ['loan_id'=>(int)$loan['id'],'installment_no'=>1]);
        $ensure('loan_payments', ['loan_id'=>(int)$loan['id'],'reference_no'=>'LOCAL-DEMO-C3-PAOLO-01'], [
            'schedule_id'=>(int)$first['id'],'payment_date'=>$first['due_date'],
            'amount'=>$monthly,'payment_method'=>'Payroll Deduction','recorded_by'=>$hr,
            'remarks'=>'LOCAL DEMO: first installment only.'
        ]);
    }

    $separation = $ensure('separations', ['employee_id'=>$employeeIds['Carlo'],'separation_type'=>'Resignation'], [
        'notice_date'=>$date('-7 days'),'proposed_last_working_date'=>$date('+30 days'),
        'effective_date'=>$date('+30 days'),'reason'=>'LOCAL DEMO: fictional resignation and asset accountability.',
        'status'=>'Clearance Ongoing','reviewed_by'=>$hr,'clearance_created_at'=>date('Y-m-d H:i:s'),
        'final_pay_processing_status'=>'Not Ready','eligible_for_final_pay'=>0
    ]);
    $separationId = (int)$separation['id'];
    foreach ([
        [$documentation,'Documentation, Visa & OEC',$hr,'Personnel documents handover','Cleared'],
        [$it,'IT & Systems (Local Demo)',$itApprover,'Return issued device and revoke access','With Accountability'],
        [$finance,'Finance & Overseas Accounts',$financeApprover,'Resolve cash advances and payroll','Cleared'],
        [$deployment,'Deployment & Flight Operations',$head,'Manager exit sign-off','Cleared'],
    ] as [$departmentId,$departmentName,$approver,$requirement,$status]) {
        $ensure('clearances', ['separation_id'=>$separationId,'department_id'=>$departmentId], [
            'department_name'=>$departmentName,'responsible_user_id'=>$approver,
            'clearance_requirement'=>$requirement,'is_mandatory'=>1,'status'=>$status,
            'accountability_details'=>$status==='With Accountability'?'LOCAL DEMO: laptop not returned.':null,
            'verified_by_user_id'=>$status==='Cleared'?$approver:null,
            'clearance_date'=>$status==='Cleared'?date('Y-m-d H:i:s'):null
        ]);
    }
    $ensure('asset_returns', ['separation_id'=>$separationId,'serial_no'=>'LOCAL-DEMO-C3-LAPTOP'], [
        'item_name'=>'LOCAL DEMO - Company Laptop','returned'=>0,'condition_status'=>'Unreturned',
        'remarks'=>'LOCAL DEMO: leave unresolved to demonstrate final-clearance enforcement.'
    ]);
    $ensure('asset_returns', ['separation_id'=>$separationId,'serial_no'=>'LOCAL-DEMO-C3-ID'], [
        'item_name'=>'LOCAL DEMO - Company ID','returned'=>1,'condition_status'=>'Good',
        'verified_by'=>'HR Manager','verified_by_user_id'=>$hr,'returned_at'=>date('Y-m-d H:i:s')
    ]);
    if ((new Separation())->blockers($separationId) < 1) throw new RuntimeException('Unresolved exit scenario is not blocked.');
    if ($presentationMode) {
        $positive = $ensure('separations', ['employee_id'=>$employeeIds['Ava'],'separation_type'=>'Resignation'], [
            'notice_date'=>$date('-40 days'),'proposed_last_working_date'=>$date('-10 days'),
            'effective_date'=>$date('-10 days'),'reason'=>'CORE3 DEMO: completed fictional separation.',
            'status'=>'Clearance Ongoing','reviewed_by'=>$hr,'clearance_created_at'=>date('Y-m-d H:i:s'),
            'final_pay_processing_status'=>'Not Ready','eligible_for_final_pay'=>0
        ]);
        $positiveId = (int)$positive['id'];
        foreach ([
            [$documentation,'Documentation, Visa & OEC',$hr],
            [$it,'IT & Systems (Local Demo)',$itApprover],
            [$finance,'Finance & Overseas Accounts',$financeApprover],
            [$deployment,'Deployment & Flight Operations',$head]
        ] as [$departmentId,$departmentName,$approver]) {
            $ensure('clearances', ['separation_id'=>$positiveId,'department_id'=>$departmentId], [
                'department_name'=>$departmentName,'responsible_user_id'=>$approver,
                'clearance_requirement'=>'CORE3 DEMO: completed departmental handover',
                'is_mandatory'=>1,'status'=>'Cleared','verified_by_user_id'=>$approver,
                'clearance_date'=>date('Y-m-d H:i:s')
            ]);
        }
        $ensure('asset_returns', ['separation_id'=>$positiveId,'serial_no'=>'CORE3-DEMO-AVA-ID'], [
            'item_name'=>'CORE3 DEMO - Company ID','returned'=>1,'condition_status'=>'Good',
            'verified_by'=>'HR Manager','verified_by_user_id'=>$hr,'returned_at'=>date('Y-m-d H:i:s')
        ]);
        if ((new Separation())->blockers($positiveId) !== 0) throw new RuntimeException('Positive presentation clearance has unexpected blockers.');
        if ($positive['status'] === 'Clearance Ongoing') {
            $db->update('separations', [
                'status'=>'Completed','completed_at'=>date('Y-m-d H:i:s'),
                'eligible_for_final_pay'=>1,'final_pay_processing_status'=>'Ready for Payroll'
            ], 'id=?', [$positiveId]);
        }
    } elseif ((new Separation())->blockers((int)$required('separations', ['employee_id'=>$employeeIds['Clarissa']])['id']) !== 0) {
        throw new RuntimeException('Existing completed clearance has unexpected blockers.');
    }

    if ($presentationMode) {
        foreach (['Mark','John','Liza'] as $name) (new TrainingRecommendation())->analyze($employeeIds[$name]);
    }
    $pdo->commit();
    if ($presentationMode) {
        $GLOBALS['core3_presentation_seed_result'] = ['accounts'=>$createdCredentials,'created'=>count($createdAccounts)];
    } else {
        echo 'LOCAL DEMO seed complete. Employees: ' . count($db->fetchAll('SELECT id FROM employees')) . PHP_EOL;
        echo 'New accounts: ' . implode(', ', $createdAccounts) . PHP_EOL;
        if ($createdAccounts) echo 'New-account password (shown once): ' . $password . PHP_EOL;
        echo 'Existing accounts and employee records were not updated.' . PHP_EOL;
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($presentationMode) throw $error;
    fwrite(STDERR, 'Seed rolled back: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

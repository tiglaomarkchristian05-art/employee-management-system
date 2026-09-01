<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Compliance.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Notification.php';

class ComplianceController extends Controller {
    private $compliance;private $notifications;
    public function __construct(){$this->compliance=new Compliance();$this->notifications=new Notification();}
    public function index() {
        Auth::requireAuth();

        $employeeModel = new Employee();
        $isAdmin = Auth::isAdmin();
        $employeeId = $isAdmin ? null : Auth::employeeId();

        $data = [
            'is_admin'=>$isAdmin,
            'records'=>$this->compliance->getRecords($employeeId),
            'corrections'=>$this->compliance->getCorrections($employeeId),
            'summary'=>$isAdmin?$this->compliance->getSummary():[],
            'contributions' => $this->compliance->getContributionsWithDetails((int)($_GET['month']??0),(int)($_GET['year']??0),$employeeId),
            'deadlines'     => $this->compliance->getUpcomingDeadlines(),
            'employees'     => $isAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('compliance/index', $data);
    }

    public function calculator() {
        Auth::requireRole(['Super Admin']);
        $this->view('compliance/calculator');
    }

    public function bir2316() {
        Auth::requireAuth();
        $complianceModel = new Compliance();
        $employeeModel = new Employee();

        $isAdmin = Auth::isAdmin();
        $empId = $isAdmin ? intval($_GET['employee_id'] ?? 0) : Auth::employeeId();
        $year = intval($_GET['year'] ?? 2026);

        $data = [
            'bir_data'  => $complianceModel->getBIR2316Data($empId, $year),
            'employees' => $isAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('compliance/bir2316', $data);
    }

    public function generateContribution() {
        Auth::requireRole(['Super Admin', 'HR Manager', 'Finance']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        $gross = floatval($_POST['gross_salary'] ?? 0);

        if (!$empId || $gross <= 0) {
            $this->json('error', 'Please select a valid employee and gross salary amount.');
        }

        // Live Calculate Statutory
        $msc = min(max($gross, 4000), 30000);
        $sssEmp = $msc * 0.045;
        $sssComp = $msc * 0.095;

        $phSalary = min(max($gross, 10000), 100000);
        $phEmp = ($phSalary * 0.05) / 2;
        $phComp = ($phSalary * 0.05) / 2;

        $hdmfEmp = $gross >= 5000 ? 200 : $gross * 0.02;
        $hdmfComp = $gross >= 5000 ? 200 : $gross * 0.02;

        $statutoryEmp = $sssEmp + $phEmp + $hdmfEmp;
        $taxable = max(0, $gross - $statutoryEmp);

        // BIR Tax
        $birTax = 0;
        if ($taxable > 20833 && $taxable <= 33333) $birTax = ($taxable - 20833) * 0.15;
        else if ($taxable > 33333 && $taxable <= 66667) $birTax = 1875 + ($taxable - 33333) * 0.20;
        else if ($taxable > 66667 && $taxable <= 166667) $birTax = 8541.67 + ($taxable - 66667) * 0.25;

        $totalStatutory = $statutoryEmp + $birTax;

        $periodMonth=max(1,min(12,(int)($_POST['period_month']??date('n'))));$periodYear=max(2000,min(2100,(int)($_POST['period_year']??date('Y'))));
        $existing=$this->compliance->db->fetchOne("SELECT id FROM gov_contributions WHERE employee_id=? AND period_month=? AND period_year=?",[$empId,$periodMonth,$periodYear]);
        if($existing)$this->json('error','A contribution record already exists for this employee and period.',[],422);
        $contributionId=$this->compliance->create([
            'employee_id'         => $empId,
            'period_month'        => $periodMonth,
            'period_year'         => $periodYear,
            'gross_salary'        => $gross,
            'sss_employee'        => $sssEmp,
            'sss_employer'        => $sssComp,
            'philhealth_employee' => $phEmp,
            'philhealth_employer' => $phComp,
            'pagibig_employee'    => $hdmfEmp,
            'pagibig_employer'    => $hdmfComp,
            'bir_tax_withheld'    => $birTax,
            'total_statutory'     => $totalStatutory,'status'=>'Posted','admin_remarks'=>trim($_POST['admin_remarks']??''),'created_by'=>Auth::user()['id']
        ]);

        AuditLogger::log('ADD_CONTRIBUTION', 'Government Compliance', "Generated contribution for Employee ID {$empId}",null,['record_type'=>'contribution','record_id'=>$contributionId,'new_value'=>['employee_id'=>$empId,'period_month'=>$periodMonth,'period_year'=>$periodYear,'total_statutory'=>$totalStatutory]]);
        $this->json('success', 'Statutory remittance & BIR withholding logged successfully!');
    }

    public function submitRecord(){$this->employeePost();$id=(int)($_POST['record_id']??0);$r=$this->compliance->getRecord($id);if(!$r)$this->json('error','Record not found.',[],404);Auth::requireOwnership($r['employee_id']);if($r['status']==='Verified')$this->json('error','Verified data requires a correction request.',[],422);$value=$this->identifier($_POST['record_number']??'');if(!$value)$this->json('error','Enter a valid government identifier.',[],422);try{$file=$this->upload('supporting_file');$this->compliance->submitRecord($id,$value,$file,Auth::user()['id']);$this->notifications->createForAdmins('Government record needs verification','Employee ID '.$r['employee_id'].' submitted '.$r['agency'].' information.','warning','index.php?page=compliance','compliance',$id);AuditLogger::log('SUBMIT_GOVERNMENT_RECORD','Government Compliance',"Submitted {$r['agency']} for verification");$this->json('success','Government information submitted for verification.');}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function requestCorrection(){$this->employeePost();$id=(int)($_POST['record_id']??0);$r=$this->compliance->getRecord($id);if(!$r)$this->json('error','Record not found.',[],404);Auth::requireOwnership($r['employee_id']);if($r['status']!=='Verified')$this->json('error','Only verified information requires a correction request.',[],422);$value=$this->identifier($_POST['proposed_value']??'');$reason=trim($_POST['reason']??'');if(!$value||$reason==='')$this->json('error','Proposed value and reason are required.',[],422);if($this->compliance->db->fetchOne("SELECT id FROM government_corrections WHERE government_record_id=? AND status='Pending'",[$id]))$this->json('error','A correction is already pending.',[],422);$file=$this->upload('supporting_file',false);$cid=$this->compliance->db->insert('government_corrections',['government_record_id'=>$id,'employee_id'=>$r['employee_id'],'proposed_value'=>$value,'reason'=>$reason,'supporting_file'=>$file['path']??null,'original_name'=>$file['original']??null]);$this->notifications->createForAdmins('New government correction request','Employee ID '.$r['employee_id'].' requested a correction to '.$r['agency'].'.','info','index.php?page=compliance','compliance',$cid);AuditLogger::log('REQUEST_GOVERNMENT_CORRECTION','Government Compliance',"Requested {$r['agency']} correction ID {$cid}");$this->json('success','Correction request submitted.');}
    public function reviewRecord(){$this->adminPost();$id=(int)($_POST['id']??0);$status=sanitize_input($_POST['status']??'');$remarks=trim($_POST['remarks']??'');if(!in_array($status,['Verified','Rejected','Needs Correction'],true))$this->json('error','Invalid decision.',[],422);if($status!=='Verified'&&$remarks==='')$this->json('error','Remarks are required.',[],422);$r=$this->compliance->reviewRecord($id,$status,$remarks,Auth::user()['id']);$this->notifications->createForEmployee($r['employee_id'],'Government record '.strtolower($status),"{$r['agency']} information was {$status}.".($remarks?" Remarks: {$remarks}":''),$status==='Verified'?'success':'warning','index.php?page=compliance','compliance',$id);AuditLogger::log('GOVERNMENT_RECORD_'.strtoupper(str_replace(' ','_',$status)),'Government Compliance',"{$r['agency']} record ID {$id}: {$status}",null,['record_type'=>'government_record','record_id'=>$id,'old_value'=>['status'=>$r['status']],'new_value'=>['status'=>$status,'remarks'=>$remarks]]);$this->json('success',"Record marked {$status}.");}
    public function decideCorrection(){$this->adminPost();$id=(int)($_POST['id']??0);$status=sanitize_input($_POST['status']??'');$remarks=trim($_POST['remarks']??'');if(!in_array($status,['Approved','Rejected'],true))$this->json('error','Invalid correction decision.',[],422);if($status==='Rejected'&&$remarks==='')$this->json('error','Rejection remarks are required.',[],422);try{$c=$this->compliance->decideCorrection($id,$status,$remarks,Auth::user()['id']);$this->notifications->createForEmployee($c['employee_id'],'Government correction '.strtolower($status),"Your {$c['agency']} correction was {$status}.".($remarks?" Remarks: {$remarks}":''),$status==='Approved'?'success':'error','index.php?page=compliance');AuditLogger::log('CORRECTION_'.strtoupper($status),'Government Compliance',"Correction ID {$id}: {$status}",null,['record_type'=>'government_correction','record_id'=>$id,'old_value'=>['status'=>$c['status']],'new_value'=>['status'=>$status,'remarks'=>$remarks]]);$this->json('success',"Correction {$status}.");}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function updateContribution(){$this->adminPost();$id=(int)($_POST['id']??0);$row=$this->compliance->find($id);if(!$row)$this->json('error','Contribution not found.',[],404);$data=[];foreach(['gross_salary','sss_employee','sss_employer','philhealth_employee','philhealth_employer','pagibig_employee','pagibig_employer','bir_tax_withheld'] as $f)$data[$f]=max(0,(float)($_POST[$f]??$row[$f]));$data['total_statutory']=$data['sss_employee']+$data['philhealth_employee']+$data['pagibig_employee']+$data['bir_tax_withheld'];$data['status']='Corrected';$data['admin_remarks']=trim($_POST['admin_remarks']??'');$data['updated_by']=Auth::user()['id'];$this->compliance->update($id,$data);AuditLogger::log('UPDATE_CONTRIBUTION','Government Compliance',"Updated contribution ID {$id}");$this->json('success','Contribution record updated.');}
    public function downloadContribution(){Auth::requireAuth();$r=$this->compliance->find((int)($_GET['id']??0));if(!$r){http_response_code(404);exit('Contribution not found.');}Auth::requireOwnership($r['employee_id']);header('Content-Type:text/csv');header('Content-Disposition:attachment; filename="contribution-'.$r['period_year'].'-'.$r['period_month'].'.csv"');echo "Agency,Employee,Employer\\nSSS,{$r['sss_employee']},{$r['sss_employer']}\\nPhilHealth,{$r['philhealth_employee']},{$r['philhealth_employer']}\\nPag-IBIG,{$r['pagibig_employee']},{$r['pagibig_employer']}\\nBIR Tax,{$r['bir_tax_withheld']},0\\n";exit;}
    private function upload($field,$required=true){if(!isset($_FILES[$field])||$_FILES[$field]['error']===UPLOAD_ERR_NO_FILE){if($required)throw new RuntimeException('Supporting document is required.');return null;}$f=$_FILES[$field];if($f['error']!==UPLOAD_ERR_OK||$f['size']<1||$f['size']>5242880)throw new RuntimeException('Supporting file must be 5 MB or less.');$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!in_array($ext,['pdf','jpg','jpeg','png'],true)||!in_array($mime,['application/pdf','image/jpeg','image/png'],true))throw new RuntimeException('Only valid PDF, JPG, or PNG files are allowed.');$dir=ROOT_PATH.'public/uploads/compliance/';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to prepare upload storage.');$name=bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],$dir.$name))throw new RuntimeException('Unable to store supporting file.');return ['path'=>'uploads/compliance/'.$name,'original'=>preg_replace('/[^A-Za-z0-9._-]+/','-',basename($f['name']))];}
    public function exportReport(){Auth::requirePermission('manage_compliance');$agency=sanitize_input($_GET['agency']??'');$status=sanitize_input($_GET['status']??'');$rows=$this->compliance->getRecords();header('Content-Type:text/csv');header('Content-Disposition:attachment; filename="government-compliance-report-'.date('Ymd').'.csv"');$out=fopen('php://output','w');fputcsv($out,['Employee Code','Employee','Department','Agency','Record Number','Status','Admin Remarks']);foreach($rows as $r){if($agency!==''&&$r['agency']!==$agency)continue;if($status!==''&&$r['status']!==$status)continue;fputcsv($out,[$r['employee_code'],$r['first_name'].' '.$r['last_name'],$r['department_name'],$r['agency'],$r['record_number'],$r['status'],$r['admin_remarks']]);}fclose($out);exit;}
    public function downloadSupporting(){Auth::requireAuth();$type=$_GET['type']??'record';if($type==='correction'){$r=$this->compliance->getCorrection((int)($_GET['id']??0));$employee=$r['employee_id']??0;$stored=$r['supporting_file']??null;$original=$r['original_name']??'correction-support';}else{$r=$this->compliance->getRecord((int)($_GET['id']??0));$employee=$r['employee_id']??0;$stored=$r['supporting_file']??null;$original=$r['original_name']??'government-support';}if(!$r||!$stored){http_response_code(404);exit('Supporting file not found.');}Auth::requireOwnership($employee);$root=realpath(ROOT_PATH.'public/uploads/compliance');$path=realpath(ROOT_PATH.'public/'.str_replace(['/',chr(92)],DIRECTORY_SEPARATOR,ltrim($stored,'/'.chr(92))));if(!$root||!$path||!is_file($path)||strpos($path,$root.DIRECTORY_SEPARATOR)!==0){http_response_code(404);exit('File not found.');}header('Content-Type:'.(mime_content_type($path)?:'application/octet-stream'));header('Content-Disposition:attachment; filename="'.basename($original).'"');header('X-Content-Type-Options:nosniff');readfile($path);exit;}
    private function identifier($v){$v=preg_replace('/[^A-Za-z0-9-]/','',trim($v));return strlen($v)>=5&&strlen($v)<=50?$v:null;}private function adminPost(){Auth::requirePermission('manage_compliance');Auth::requireMethod('POST');$this->csrf();}private function employeePost(){Auth::requirePermission('view_own_contributions');Auth::requireMethod('POST');$this->csrf();}private function csrf(){if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid CSRF token.',[],403);}
}

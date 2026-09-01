<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Document.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Notification.php';

class DocumentController extends Controller {
    private $documentModel;
    private $notifications;

    public function __construct() {
        $this->documentModel = new Document();
        $this->notifications = new Notification();
    }

    public function index() {
        Auth::requireAuth();
        $this->documentModel->refreshExpiryStatuses();
        $this->sendExpiryNotifications();
        $employeeModel = new Employee();
        $user = Auth::user();
        $isHRAdmin = Auth::isAdmin();
        $empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'is_admin'           => $isHRAdmin,
            'documents'          => $this->documentModel->getDocumentsWithDetails($empId),
            'requirements'       => $this->documentModel->getRequirements($empId),
            'expiring_contracts' => $this->documentModel->getExpiringContracts(60, $empId),
            'categories'         => $this->documentModel->db->fetchAll("SELECT * FROM document_categories WHERE is_active=1 ORDER BY name"),
            'employees'          => $isHRAdmin ? $employeeModel->getAllWithDetails() : []
        ];

        $this->view('documents/index', $data);
    }

    public function contracts() {
        Auth::requireAuth();
        $this->documentModel->refreshExpiryStatuses();
        $this->sendExpiryNotifications();
        $user = Auth::user();
        $isHRAdmin=Auth::isAdmin();$empId = $isHRAdmin ? null : $user['employee_id'];

        $data = [
            'is_admin'=>$isHRAdmin,
            'contracts' => $this->documentModel->getContractsWithDetails($empId),
            'employees'=>$isHRAdmin?(new Employee())->getAllWithDetails():[]
        ];

        $this->view('documents/contracts', $data);
    }

    public function upload() {
        Auth::requireAuth();
        Auth::requireMethod('POST');
        $this->csrf();

        $user = Auth::user();
        $isHRAdmin = Auth::isAdmin();
        $empId = $isHRAdmin ? intval($_POST['employee_id'] ?? 0) : Auth::employeeId();
        $requirementId=(int)($_POST['requirement_id']??0);$requirement=$requirementId?$this->documentModel->getRequirement($requirementId):null;
        if($requirement){if(!$isHRAdmin)Auth::requireOwnership($requirement['employee_id']);$empId=(int)$requirement['employee_id'];}
        $categoryId=$requirement?(int)$requirement['category_id']:(int)($_POST['category_id']??0);
        $category=$this->documentModel->db->fetchOne("SELECT * FROM document_categories WHERE id=? AND is_active=1",[$categoryId]);
        $title=sanitize_input($_POST['title']??($requirement['title']??''));
        if($empId<1||!$category||$title==='')$this->json('error','Employee, document type, and title are required.',[],422);
        try {
            $file=$this->saveUpload('document_file','documents',explode(',',$category['allowed_extensions']),(int)$category['max_size_mb']*1048576);
            $docId=$this->documentModel->submit(['employee_id'=>$empId,'category_id'=>$categoryId,'title'=>$title,'document_number'=>sanitize_input($_POST['document_number']??''),'file_path'=>$file['path'],'file_size'=>$file['size'],'issue_date'=>$this->dateOrNull($_POST['issue_date']??''),'expiry_date'=>$this->dateOrNull($_POST['expiry_date']??''),'qr_code'=>'QR-EMP'.$empId.'-'.strtoupper(bin2hex(random_bytes(4))),'status'=>$isHRAdmin?'Approved':'Submitted','remarks'=>$isHRAdmin?'Uploaded by authorized Admin/HR.':null,'original_name'=>$file['original'],'mime_type'=>$file['mime'],'submitted_by'=>$user['id']],$requirementId?:null);
            if($isHRAdmin&&$requirementId)$this->documentModel->db->update('document_requirements',['status'=>'Approved'],"id=?",[$requirementId]);
            if(!$isHRAdmin)$this->notifications->createForAdmins('New document submission','Employee ID '.$empId.' submitted '.$title.' for review.','info','index.php?page=documents','documents',$docId);
            AuditLogger::log('UPLOAD_DOCUMENT','Document Management',"Uploaded {$title} (ID: {$docId})");
            $this->json('success',$isHRAdmin?'Authorized document uploaded and approved.':'Document submitted for review.',['id'=>(int)$docId]);
        } catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function delete() {
        Auth::requireAdmin();
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->json('error', 'Invalid document ID.');
        }

        $doc = $this->documentModel->getDocument($id);
        if (!$doc) {
            $this->json('error', 'Document not found.');
        }

        $this->documentModel->review($id, 'Rejected', 'Archived by Admin/HR through the legacy document action.', Auth::user()['id']);
        AuditLogger::log('ARCHIVE_DOCUMENT', 'Document Management', "Archived document: {$doc['title']} (ID: {$id})");
        $this->json('success', 'Document archived without deleting its history.');
    }

    public function download() {
        Auth::requireAuth();
        $id = intval($_GET['id'] ?? 0);
        $documentModel = new Document();
        $doc = $documentModel->find($id);

        if (!$doc) {
            http_response_code(404);
            exit('Document not found.');
        }
        Auth::requireOwnership($doc['employee_id']);

        $relativePath = str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, ltrim((string)$doc['file_path'], '/\\\\'));
        $filePath = ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . $relativePath;
        $uploadRoot = realpath(ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents');
        $resolvedPath = realpath($filePath);
        if (!$uploadRoot || !$resolvedPath || !is_file($resolvedPath) || strpos($resolvedPath, $uploadRoot . DIRECTORY_SEPARATOR) !== 0) {
            $receiptName = 'document-record-' . $id . '.html';
            $title = htmlspecialchars((string)$doc['title'], ENT_QUOTES, 'UTF-8');
            $documentNumber = htmlspecialchars((string)($doc['document_number'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $qrCode = htmlspecialchars((string)($doc['qr_code'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string)($doc['status'] ?? 'Recorded'), ENT_QUOTES, 'UTF-8');
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $receiptName . '"');
            header('X-Content-Type-Options: nosniff');
            AuditLogger::log('DOWNLOAD_DOCUMENT_RECEIPT', 'Document Management', "Downloaded legacy document receipt ID: {$id}");
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Document Record</title><style>'
                . 'body{font-family:Arial,sans-serif;background:#f4f7fb;margin:0;padding:40px;color:#111827}.record{max-width:760px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-top:8px solid #5145e5;border-radius:16px;padding:42px;box-shadow:0 14px 40px rgba(15,23,42,.08)}h1{margin:8px 0 30px}.eyebrow{color:#5145e5;font-weight:700;letter-spacing:2px}.row{padding:14px 0;border-bottom:1px solid #e2e8f0}.label{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:1px;margin-bottom:5px}.notice{margin-top:28px;padding:14px;background:#fff7ed;color:#9a3412;border-radius:10px;font-size:13px;line-height:1.5}'
                . '</style></head><body><main class="record"><div class="eyebrow">CORE 3 DOCUMENT MANAGEMENT</div><h1>Verified Document Record</h1><div class="row"><span class="label">Title</span><strong>' . $title . '</strong></div><div class="row"><span class="label">Document number</span>' . $documentNumber . '</div><div class="row"><span class="label">QR verification code</span>' . $qrCode . '</div><div class="row"><span class="label">Status</span>' . $status . '</div><div class="notice">This legacy seeded record does not include the original binary attachment. This receipt preserves its verified system metadata.</div></main></body></html>';
            exit;
        }

        $downloadName = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$doc['title']);
        $downloadName = trim($downloadName, '-') . '.' . pathinfo($resolvedPath, PATHINFO_EXTENSION);
        $mime = function_exists('mime_content_type') ? mime_content_type($resolvedPath) : 'application/octet-stream';
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($resolvedPath));
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('X-Content-Type-Options: nosniff');
        AuditLogger::log('DOWNLOAD_DOCUMENT', 'Document Management', "Downloaded document ID: {$id}");
        readfile($resolvedPath);
        exit;
    }

    public function storeType() {
        $this->adminPost();$name=sanitize_input($_POST['name']??'');
        if($name===''||strlen($name)>100)$this->json('error','A valid document type name is required.',[],422);
        $existing=$this->documentModel->db->fetchOne('SELECT id,name FROM document_categories WHERE LOWER(name)=LOWER(?) LIMIT 1',[$name]);
        if($existing)$this->json('success','This document type already exists. Continue by assigning it to an employee.',['id'=>(int)$existing['id'],'existing'=>true]);
        try{$id=$this->documentModel->db->insert('document_categories',['name'=>$name,'description'=>trim($_POST['description']??''),'is_required'=>!empty($_POST['is_required'])?1:0,'instructions'=>trim($_POST['instructions']??''),'allowed_extensions'=>$this->extensions($_POST['allowed_extensions']??'pdf,jpg,jpeg,png'),'max_size_mb'=>max(1,min(20,(int)($_POST['max_size_mb']??5))),'is_active'=>1]);AuditLogger::log('CREATE_DOCUMENT_TYPE','Document Management',"Created document type {$name} (ID: {$id})");$this->json('success','Document type created. Continue by assigning it to an employee.',['id'=>(int)$id,'existing'=>false]);}catch(Throwable $e){$this->json('error','Unable to create the document type. Please try again.',[],422);}
    }
    public function assignRequirement() {
        $this->adminPost();$employee=(int)($_POST['employee_id']??0);$category=(int)($_POST['category_id']??0);$title=sanitize_input($_POST['title']??'');
        if(!$this->exists('employees',$employee)||!$this->exists('document_categories',$category)||$title==='')$this->json('error','Employee, document type, and title are required.',[],422);
        $id=$this->documentModel->assignRequirement(['employee_id'=>$employee,'category_id'=>$category,'title'=>$title,'instructions'=>trim($_POST['instructions']??''),'due_date'=>$this->dateOrNull($_POST['due_date']??''),'status'=>'Pending','assigned_by'=>Auth::user()['id']]);
        $this->notifications->createForEmployee($employee,'Document required',"Please submit {$title}.",'warning','index.php?page=documents','documents',$id);AuditLogger::log('ASSIGN_DOCUMENT_REQUIREMENT','Document Management',"Assigned {$title} to employee ID {$employee}");$this->json('success','Document requirement assigned.',['id'=>(int)$id]);
    }
    public function review() {
        $this->adminPost();$id=(int)($_POST['id']??0);$status=sanitize_input($_POST['status']??'');$remarks=trim($_POST['remarks']??'');
        if(!in_array($status,['Under Review','Approved','Returned','Rejected'],true))$this->json('error','Invalid review decision.',[],422);
        if(in_array($status,['Returned','Rejected'],true)&&$remarks==='')$this->json('error','Remarks are required when returning or rejecting a document.',[],422);
        try{$doc=$this->documentModel->review($id,$status,$remarks,Auth::user()['id']);$tone=$status==='Approved'?'success':($status==='Returned'?'warning':'error');$this->notifications->createForEmployee($doc['employee_id'],'Document '.strtolower($status),"{$doc['title']} was {$status}.".($remarks?" Remarks: {$remarks}":''),$tone,'index.php?page=documents','documents',$id);AuditLogger::log('DOCUMENT_'.strtoupper(str_replace(' ','_',$status)),'Document Management',"Document ID {$id} set to {$status}",null,['record_type'=>'document','record_id'=>$id,'old_value'=>['status'=>$doc['status']],'new_value'=>['status'=>$status,'remarks'=>$remarks]]);$this->json('success',"Document marked {$status}.");}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }
    public function acknowledge() {$this->employeePost();$id=(int)($_POST['requirement_id']??0);$r=$this->documentModel->getRequirement($id);if(!$r)$this->json('error','Requirement not found.',[],404);Auth::requireOwnership($r['employee_id']);$this->documentModel->db->update('document_requirements',['acknowledged_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);AuditLogger::log('ACKNOWLEDGE_DOCUMENT_REQUIREMENT','Document Management',"Acknowledged requirement ID {$id}");$this->json('success','Requirement acknowledged.');}
    public function requestCorrection() {$this->employeePost();$id=(int)($_POST['id']??0);$doc=$this->documentModel->getDocument($id);if(!$doc)$this->json('error','Document not found.',[],404);Auth::requireOwnership($doc['employee_id']);$remarks=trim($_POST['remarks']??'');if($remarks==='')$this->json('error','Explain the requested correction.',[],422);$this->documentModel->db->update('documents',['status'=>'Under Review','remarks'=>'Employee correction request: '.$remarks],"id=?",[$id]);AuditLogger::log('REQUEST_DOCUMENT_CORRECTION','Document Management',"Requested correction for document ID {$id}");$this->json('success','Correction request sent to Admin/HR.');}
    public function storeContract() {$this->adminPost();$employee=(int)($_POST['employee_id']??0);if(!$this->exists('employees',$employee))$this->json('error','Select a valid employee.',[],422);try{$data=$this->contractInput();$file=$this->saveUpload('contract_file','contracts',['pdf'],10485760);$data+=['employee_id'=>$employee,'document_file'=>$file['path'],'original_name'=>$file['original'],'created_by'=>Auth::user()['id']];$id=$this->documentModel->db->insert('contracts',$data);$this->notifications->createForEmployee($employee,'New employment contract','A new contract is available for review.','info','index.php?page=documents_contracts');AuditLogger::log('CREATE_CONTRACT','Contract Management',"Created contract ID {$id}");$this->json('success','Contract uploaded.',['id'=>(int)$id]);}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function renewContract() {$this->adminPost();$id=(int)($_POST['id']??0);try{$data=$this->contractInput();$file=$this->saveUpload('contract_file','contracts',['pdf'],10485760);$data+=['document_file'=>$file['path'],'original_name'=>$file['original'],'created_by'=>Auth::user()['id']];[$old,$new]=$this->documentModel->renewContract($id,$data);$this->notifications->createForEmployee($old['employee_id'],'Contract renewed','Your renewed contract is available.','success','index.php?page=documents_contracts');AuditLogger::log('RENEW_CONTRACT','Contract Management',"Renewed contract ID {$id} as ID {$new}",null,['record_type'=>'contract','record_id'=>$new,'old_value'=>['contract_id'=>$id,'status'=>$old['status'],'end_date'=>$old['end_date']],'new_value'=>['contract_id'=>$new,'status'=>'Active','start_date'=>$data['start_date'],'end_date'=>$data['end_date']]]);$this->json('success','Contract renewed; the previous version was archived.',['id'=>(int)$new]);}catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}}
    public function downloadContract() {Auth::requireAuth();$c=$this->documentModel->getContract((int)($_GET['id']??0));if(!$c){http_response_code(404);exit('Contract not found.');}Auth::requireOwnership($c['employee_id']);$this->downloadStored($c['document_file'],'employment-contract-v'.$c['version_no']);}
    public function acknowledgeContract() {$this->employeePost();$id=(int)($_POST['id']??0);$c=$this->documentModel->getContract($id);if(!$c)$this->json('error','Contract not found.',[],404);Auth::requireOwnership($c['employee_id']);$this->documentModel->db->update('contracts',['acknowledged_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);AuditLogger::log('ACKNOWLEDGE_CONTRACT','Contract Management',"Acknowledged contract ID {$id}");$this->json('success','Contract acknowledged.');}
    private function contractInput() {$type=sanitize_input($_POST['contract_type']??'Employment');if(!in_array($type,['Employment','Probation','Regularization','Consultancy','Internship'],true))throw new RuntimeException('Invalid contract type.');$start=$this->dateOrNull($_POST['start_date']??'');$end=$this->dateOrNull($_POST['end_date']??'');if(!$start||($end&&strtotime($end)<strtotime($start)))throw new RuntimeException('Contract end date must be on or after its start date.');return ['contract_type'=>$type,'start_date'=>$start,'end_date'=>$end,'status'=>'Active','approval_status'=>'Approved','remarks'=>trim($_POST['remarks']??'')];}
    private function saveUpload($field,$folder,array $extensions,$max) {if(!isset($_FILES[$field])||$_FILES[$field]['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Select a file to upload.');$f=$_FILES[$field];if($f['size']<1||$f['size']>$max)throw new RuntimeException('The file exceeds the allowed size.');$ext=strtolower(pathinfo(basename($f['name']),PATHINFO_EXTENSION));$extensions=array_map('trim',$extensions);if(!in_array($ext,$extensions,true))throw new RuntimeException('This file extension is not allowed.');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$allowed=['application/pdf','image/jpeg','image/png','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];if(!in_array($mime,$allowed,true))throw new RuntimeException('The uploaded file content is not allowed.');$dir=ROOT_PATH.'public/uploads/'.$folder.'/';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to prepare upload storage.');$name=bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($f['tmp_name'],$dir.$name))throw new RuntimeException('Unable to store the uploaded file.');return ['path'=>'uploads/'.$folder.'/'.$name,'original'=>preg_replace('/[^A-Za-z0-9._-]+/','-',basename($f['name'])),'mime'=>$mime,'size'=>round($f['size']/1048576,2).' MB'];}
    private function downloadStored($relative,$fallback) {$root=realpath(ROOT_PATH.'public/uploads');$path=realpath(ROOT_PATH.'public/'.str_replace(['/',chr(92)],DIRECTORY_SEPARATOR,ltrim((string)$relative,'/'.chr(92))));if(!$root||!$path||!is_file($path)||strpos($path,$root.DIRECTORY_SEPARATOR)!==0){http_response_code(404);exit('File not found.');}$name=preg_replace('/[^A-Za-z0-9._-]+/','-',$fallback).'.'.pathinfo($path,PATHINFO_EXTENSION);header('Content-Type:'.(mime_content_type($path)?:'application/octet-stream'));header('Content-Length:'.filesize($path));header('Content-Disposition:attachment; filename="'.$name.'"');header('X-Content-Type-Options:nosniff');readfile($path);exit;}
    private function exists($table,$id){return $id?$this->documentModel->db->fetchOne("SELECT id FROM {$table} WHERE id=?",[$id]):false;}private function dateOrNull($v){if($v==='')return null;$d=DateTime::createFromFormat('Y-m-d',$v);return $d&&$d->format('Y-m-d')===$v?$v:null;}private function extensions($v){$a=array_filter(array_map(fn($x)=>preg_replace('/[^a-z0-9]/','',strtolower($x)),explode(',',$v)));return implode(',',array_unique($a))?:'pdf,jpg,jpeg,png';}
    private function sendExpiryNotifications() {
        if(!Auth::isAdmin())return;
        foreach($this->documentModel->db->fetchAll("SELECT id,employee_id,title,expiry_date FROM documents WHERE expiry_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 30 DAY) AND status='Approved'") as $d){$link='index.php?page=documents';$exists=$this->documentModel->db->fetchOne("SELECT n.id FROM notifications n JOIN users u ON u.id=n.user_id WHERE u.employee_id=? AND n.title='Document expiring' AND n.related_id=? AND n.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$d['employee_id'],$d['id']]);if(!$exists)$this->notifications->createForEmployee($d['employee_id'],'Document expiring',"{$d['title']} expires on {$d['expiry_date']}.",'warning',$link,'documents',$d['id']);$adminExists=$this->documentModel->db->fetchOne("SELECT id FROM notifications WHERE title='Expiring document' AND related_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$d['id']]);if(!$adminExists)$this->notifications->createForAdmins('Expiring document',"{$d['title']} expires on {$d['expiry_date']}.",'warning',$link,'documents',$d['id']);}
        foreach($this->documentModel->db->fetchAll("SELECT id,employee_id,contract_type,end_date FROM contracts WHERE status='Active' AND end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 60 DAY)") as $c){$link='index.php?page=documents_contracts';$exists=$this->documentModel->db->fetchOne("SELECT n.id FROM notifications n JOIN users u ON u.id=n.user_id WHERE u.employee_id=? AND n.title='Contract expiring' AND n.related_id=? AND n.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$c['employee_id'],$c['id']]);if(!$exists)$this->notifications->createForEmployee($c['employee_id'],'Contract expiring',"Your {$c['contract_type']} contract expires on {$c['end_date']}.",'warning',$link,'documents',$c['id']);$adminExists=$this->documentModel->db->fetchOne("SELECT id FROM notifications WHERE title='Expiring contract' AND related_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$c['id']]);if(!$adminExists)$this->notifications->createForAdmins('Expiring contract',"{$c['contract_type']} contract expires on {$c['end_date']}.",'warning',$link,'documents',$c['id']);}
    }
    private function adminPost(){Auth::requirePermission('manage_documents');Auth::requireMethod('POST');$this->csrf();}private function employeePost(){Auth::requirePermission('upload_own_documents');Auth::requireMethod('POST');$this->csrf();}private function csrf(){if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid CSRF token.',[],403);}
}

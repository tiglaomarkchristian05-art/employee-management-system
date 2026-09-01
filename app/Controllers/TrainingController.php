<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Training.php';
require_once APP_PATH . 'Models/Notification.php';
require_once APP_PATH . 'Models/Employee.php';

class TrainingController extends Controller {
    private $training;
    private $notifications;

    public function __construct() {
        $this->training = new Training();
        $this->notifications = new Notification();
    }

    public function dashboard() {
        Auth::requireAuth();
        $isAdmin=Auth::isAdmin();$employeeId=$isAdmin?null:Auth::employeeId();
        $data=[
            'is_admin'=>$isAdmin,
            'stats'=>$this->training->getDashboardStats($employeeId),
            'courses'=>$isAdmin?$this->training->getCoursesWithDetails(true):$this->training->getAvailableCourses($employeeId),
            'my_registrations'=>$isAdmin?[]:$this->training->getRegistrationsWithDetails($employeeId),
            'all_registrations'=>$isAdmin?$this->training->getRegistrationsWithDetails():[],
            'skills_matrix'=>$this->training->getSkillsMatrix($employeeId),
            'categories'=>$isAdmin?$this->training->db->fetchAll("SELECT * FROM training_categories ORDER BY name"):[],
            'trainers'=>$isAdmin?$this->training->db->fetchAll("SELECT * FROM trainers ORDER BY name"):[],
            'employees'=>$isAdmin?(new Employee())->getAllWithDetails():[],
            'departments'=>$isAdmin?$this->training->db->fetchAll("SELECT id,name FROM departments ORDER BY name"):[],
            'positions'=>$isAdmin?$this->training->db->fetchAll("SELECT id,title,department_id FROM positions ORDER BY title"):[],
        ];
        $this->view('training/dashboard',$data);
    }

    public function courses() {
        Auth::requireAuth();$employeeId=Auth::isAdmin()?null:Auth::employeeId();
        $this->view('training/courses',['courses'=>Auth::isAdmin()?$this->training->getCoursesWithDetails(true):$this->training->getAvailableCourses($employeeId),'my_registrations'=>Auth::isAdmin()?[]:$this->training->getRegistrationsWithDetails($employeeId)]);
    }

    public function details() {
        Auth::requireAuth();$id=(int)($_GET['id']??0);$course=$this->training->getCourseWithDetails($id);
        if(!$course){http_response_code(404);exit('Training not found.');}
        $registration=Auth::isSelfService()?$this->training->getRegistrationForEmployee($id,Auth::employeeId()):null;
        if(Auth::isSelfService() && !$registration && (!$course['is_active'] || !in_array($course['status'],['Scheduled','Ongoing'],true))) Auth::deny();
        $this->view('training/details',['course'=>$course,'registration'=>$registration,'participants'=>Auth::isAdmin()?$this->training->getRegistrationsWithDetails(null,$id):[]]);
    }

    public function matrix() {Auth::requireAuth();$this->view('training/matrix',['skills'=>$this->training->getSkillsMatrix(Auth::isAdmin()?null:Auth::employeeId())]);}

    public function store() {
        $this->adminPost();
        try {
            $data=$this->courseInput();$data['created_by']=Auth::user()['id'];
            if($file=$this->saveUpload('material_file','materials',['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip'],10*1024*1024))$data['material_file']=$file;
            $id=$this->training->create($data);
            AuditLogger::log('CREATE_TRAINING','Training & Development',"Created training {$data['title']} (ID: {$id})",null,['record_type'=>'training','record_id'=>$id,'new_value'=>['title'=>$data['title'],'status'=>$data['status'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date']]]);
            $this->json('success','Training created successfully.',['id'=>(int)$id]);
        } catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function update() {
        $this->adminPost();$id=(int)($_POST['id']??0);$course=$this->training->getCourseWithDetails($id);
        if(!$course)$this->json('error','Training not found.',[],404);
        try {
            $data=$this->courseInput();
            if((int)$data['capacity']<(int)$course['enrolled_count'])throw new RuntimeException('Capacity cannot be lower than the current participant count.');
            if($file=$this->saveUpload('material_file','materials',['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','zip'],10*1024*1024))$data['material_file']=$file;
            $scheduleChanged=$course['start_date']!==$data['start_date']||$course['end_date']!==$data['end_date']||$course['venue']!==$data['venue'];
            $this->training->update($id,$data);
            if($scheduleChanged)$this->notifyParticipants($id,'Training schedule updated',"{$data['title']} is scheduled for {$data['start_date']} at {$data['venue']}.",'warning');
            AuditLogger::log('UPDATE_TRAINING','Training & Development',"Updated training {$data['title']} (ID: {$id})",null,['record_type'=>'training','record_id'=>$id,'old_value'=>['status'=>$course['status'],'start_date'=>$course['start_date'],'end_date'=>$course['end_date'],'venue'=>$course['venue']],'new_value'=>['status'=>$data['status'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],'venue'=>$data['venue']]]);
            $this->json('success','Training updated successfully.');
        } catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function cancel() {
        $this->adminPost();$id=(int)($_POST['id']??0);$course=$this->training->getCourseWithDetails($id);
        if(!$course)$this->json('error','Training not found.',[],404);
        $this->training->update($id,['status'=>'Cancelled','is_active'=>0]);
        $this->training->db->query("UPDATE training_registrations SET status='Cancelled' WHERE course_id=? AND status NOT IN ('Completed','Failed')",[$id]);
        $this->notifyParticipants($id,'Training cancelled',"{$course['title']} has been cancelled.",'error');
        AuditLogger::log('CANCEL_TRAINING','Training & Development',"Cancelled training {$course['title']} (ID: {$id})");
        $this->json('success','Training cancelled and archived.');
    }

    public function remind() {
        $this->adminPost();$id=(int)($_POST['id']??0);$course=$this->training->getCourseWithDetails($id);
        if(!$course)$this->json('error','Training not found.',[],404);
        if(in_array($course['status'],['Completed','Cancelled'],true))$this->json('error','Reminders cannot be sent for this training.',[],422);
        $this->notifyParticipants($id,'Training reminder',"Reminder: {$course['title']} starts on {$course['start_date']} at {$course['venue']}.",'info');
        AuditLogger::log('SEND_TRAINING_REMINDER','Training & Development',"Sent participant reminder for training ID {$id}");
        $this->json('success','Training reminder sent to all participants.');
    }

    public function assign() {
        $this->adminPost();$courseId=(int)($_POST['course_id']??0);$ids=$_POST['employee_ids']??[];
        $departmentId=(int)($_POST['department_id']??0);if($departmentId)$ids=array_merge((array)$ids,$this->training->getDepartmentEmployeeIds($departmentId));
        try {
            $result=$this->training->assignEmployees($courseId,(array)$ids,Auth::user()['id']);$course=$this->training->getCourseWithDetails($courseId);
            foreach($result['assigned'] as $employeeId)$this->notifications->createForEmployee($employeeId,'Training assignment',"You were assigned to {$course['title']} on {$course['start_date']}.",'info',"index.php?page=training_details&id={$courseId}");
            AuditLogger::log('ASSIGN_TRAINING','Training & Development',"Assigned ".count($result['assigned'])." employee(s) to training ID {$courseId}",null,['record_type'=>'training','record_id'=>$courseId,'new_value'=>['employee_ids'=>$result['assigned']]]);
            $message=count($result['assigned']).' employee(s) assigned.';if($result['duplicates'])$message.=' '.count($result['duplicates']).' duplicate active assignment(s) skipped.';
            $this->json('success',$message,$result);
        } catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function register() {
        $this->employeePost();$courseId=(int)($_POST['course_id']??0);
        try {
            $id=$this->training->applyForCourse($courseId,Auth::employeeId());$course=$this->training->getCourseWithDetails($courseId);
            $this->notifications->createForAdmins('New training application','An employee applied for '.$course['title'].'.','info','index.php?page=training_details&id='.$courseId,'training',$id);
            AuditLogger::log('APPLY_TRAINING','Training & Development',"Applied for {$course['title']} (registration ID: {$id})");
            $this->json('success','Training application submitted for Admin/HR review.',['id'=>$id]);
        } catch(Throwable $e){$this->json('warning',$e->getMessage(),[],422);}
    }

    public function confirm() {
        $this->employeePost();$id=(int)($_POST['registration_id']??0);$registration=$this->training->getRegistration($id);
        if(!$registration) $this->json('error','Participation record not found.',[],404);
        Auth::requireOwnership($registration['employee_id']);
        if($registration['status']!=='Assigned')$this->json('error','Only an assigned training can be confirmed.',[],422);
        $this->training->db->update('training_registrations',['status'=>'Confirmed'],"id=?",[$id]);
        AuditLogger::log('CONFIRM_TRAINING','Training & Development',"Confirmed participation for registration ID {$id}");
        $this->json('success','Participation confirmed.');
    }

    public function updateParticipant() {
        $this->adminPost();$id=(int)($_POST['registration_id']??0);$registration=$this->training->getRegistration($id);
        if(!$registration)$this->json('error','Participation record not found.',[],404);
        $status=sanitize_input($_POST['status']??'');if(!in_array($status,Training::PARTICIPATION_STATUSES,true))$this->json('error','Invalid participation status.',[],422);
        $attendance=max(0,min(100,(int)($_POST['attendance_percentage']??0)));$assessment=$_POST['assessment_result']??'';$assessment=$assessment===''?null:(float)$assessment;
        if($assessment!==null&&($assessment<0||$assessment>100))$this->json('error','Assessment result must be between 0 and 100.',[],422);
        if($status==='Completed'&&($attendance<=0||$assessment===null))$this->json('error','Record attendance and an assessment result before completion.',[],422);
        try {
            $data=['status'=>$status,'attendance_percentage'=>$attendance,'assessment_result'=>$assessment,'result_notes'=>sanitize_input($_POST['result_notes']??'')];
            if($status==='Completed'&&!empty($registration['completed_at']))$data['completed_at']=$registration['completed_at'];elseif($status==='Completed')$data['completed_at']=date('Y-m-d');else $data['completed_at']=null;
            if($certificate=$this->saveUpload('certificate_file','certificates',['pdf'],5*1024*1024))$data['certificate_file']=$certificate;
            if($status==='Completed'&&empty($data['certificate_file'])&&empty($registration['certificate_file']))$data['certificate_file']='generated';
            $previous=$registration['status'];$this->training->db->update('training_registrations',$data,"id=?",[$id]);
            if($previous==='Applied'&&in_array($status,['Confirmed','Cancelled'],true))$this->notifications->createForEmployee($registration['employee_id'],'Training application '.strtolower($status),"Your application for {$registration['course_title']} was {$status}.",$status==='Confirmed'?'success':'error',"index.php?page=training_details&id={$registration['course_id']}");
            if($assessment!==null)$this->notifications->createForEmployee($registration['employee_id'],'Training result published',"Your result for {$registration['course_title']} is {$assessment}%.",'info',"index.php?page=training_details&id={$registration['course_id']}");
            if($status==='Completed')$this->notifications->createForEmployee($registration['employee_id'],'Certificate available',"Your certificate for {$registration['course_title']} is ready.",'success',"index.php?page=training_certificate&id={$id}");
            AuditLogger::log('UPDATE_TRAINING_PARTICIPANT','Training & Development',"Updated registration ID {$id}: {$status}, attendance {$attendance}, result ".($assessment??'N/A'),null,['record_type'=>'training_registration','record_id'=>$id,'old_value'=>['status'=>$previous,'attendance_percentage'=>$registration['attendance_percentage']??null,'assessment_result'=>$registration['assessment_result']??null],'new_value'=>['status'=>$status,'attendance_percentage'=>$attendance,'assessment_result'=>$assessment,'certificate_issued'=>!empty($data['certificate_file'])]]);
            if($attendance!==(int)($registration['attendance_percentage']??0))AuditLogger::log('RECORD_TRAINING_ATTENDANCE','Training & Development',"Recorded {$attendance}% attendance for registration ID {$id}",null,['record_type'=>'training_registration','record_id'=>$id,'old_value'=>['attendance_percentage'=>$registration['attendance_percentage']??null],'new_value'=>['attendance_percentage'=>$attendance]]);
            if($assessment!==null)AuditLogger::log('UPDATE_TRAINING_RESULT','Training & Development',"Published result for registration ID {$id}",null,['record_type'=>'training_registration','record_id'=>$id,'old_value'=>['assessment_result'=>$registration['assessment_result']??null],'new_value'=>['assessment_result'=>$assessment]]);
            if(!empty($data['certificate_file']))AuditLogger::log('ISSUE_TRAINING_CERTIFICATE','Training & Development',"Issued certificate for registration ID {$id}",null,['record_type'=>'training_registration','record_id'=>$id,'new_value'=>['certificate_issued'=>true]]);
            $this->json('success','Participant record updated.');
        } catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function uploadRequirement() {
        $this->employeePost();$id=(int)($_POST['registration_id']??0);$registration=$this->training->getRegistration($id);
        if(!$registration)$this->json('error','Participation record not found.',[],404);Auth::requireOwnership($registration['employee_id']);
        try {$file=$this->saveUpload('requirements_file','requirements',['pdf','jpg','jpeg','png'],5*1024*1024,true);$this->training->db->update('training_registrations',['requirements_file'=>$file],"id=?",[$id]);AuditLogger::log('UPLOAD_TRAINING_REQUIREMENT','Training & Development',"Uploaded requirement for registration ID {$id}");$this->json('success','Training requirement uploaded.');}
        catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function downloadMaterial() {Auth::requireAuth();$course=$this->training->getCourseWithDetails((int)($_GET['id']??0));if(!$course||!$course['material_file']){http_response_code(404);exit('Training material not found.');}if(Auth::isSelfService()&&!$this->training->getRegistrationForEmployee($course['id'],Auth::employeeId()))Auth::deny();$this->downloadFile($course['material_file'],'training-material');}
    public function downloadRequirement() {Auth::requireAdmin();$registration=$this->training->getRegistration((int)($_GET['id']??0));if(!$registration||!$registration['requirements_file']){http_response_code(404);exit('Requirement file not found.');}$this->downloadFile($registration['requirements_file'],'training-requirement');}

    public function quiz() {
        Auth::requireSelfService();$courseId=(int)($_GET['course_id']??0);$registration=$this->training->getRegistrationForEmployee($courseId,Auth::employeeId());
        if(!$registration||!in_array($registration['status'],['Confirmed','Attended'],true))Auth::deny('Confirmed participation is required before taking this assessment.');
        $course=$this->training->find($courseId);if(!$course){http_response_code(404);exit('Training not found.');}
        $this->view('training/quiz',['course'=>$course,'questions'=>$this->training->getQuizQuestions($courseId)]);
    }

    public function submitQuiz() {
        $this->employeePost();$courseId=(int)($_POST['course_id']??0);$registration=$this->training->getRegistrationForEmployee($courseId,Auth::employeeId());
        if(!$registration||!in_array($registration['status'],['Confirmed','Attended'],true))Auth::deny();
        $questions=$this->training->getQuizQuestions($courseId);$answers=$_POST['answers']??[];$correct=0;foreach($questions as $q)if(isset($answers[$q['id']])&&$answers[$q['id']]===$q['correct_option'])$correct++;
        $score=count($questions)?round($correct/count($questions)*100):0;$this->training->db->update('training_registrations',['quiz_score'=>$score],"id=?",[$registration['id']]);
        AuditLogger::log('SUBMIT_TRAINING_ASSESSMENT','Training & Development',"Submitted assessment for course ID {$courseId}; raw score {$score}%");
        $this->json('success',"Assessment submitted with a raw score of {$score}%. Admin/HR will review and publish the official result.",['score'=>$score]);
    }

    public function certificate() {
        Auth::requireAuth();$registration=$this->training->getRegistration((int)($_GET['id']??0));
        if(!$registration||$registration['status']!=='Completed'||!$registration['certificate_file']){http_response_code(404);exit('Completed training certificate not found.');}
        Auth::requireOwnership($registration['employee_id']);
        if($registration['certificate_file']!=='generated')$this->downloadFile($registration['certificate_file'],'training-certificate');
        $number='CERT-'.str_pad((string)$registration['id'],6,'0',STR_PAD_LEFT);$name=htmlspecialchars($registration['first_name'].' '.$registration['last_name'],ENT_QUOTES,'UTF-8');$title=htmlspecialchars($registration['course_title'],ENT_QUOTES,'UTF-8');$date=htmlspecialchars($registration['end_date']?:$registration['start_date'],ENT_QUOTES,'UTF-8');$score=number_format((float)($registration['assessment_result']??$registration['quiz_score']),1);
        header('Content-Type:text/html;charset=UTF-8');header('Content-Disposition:attachment; filename="'.strtolower($number).'.html"');header('X-Content-Type-Options:nosniff');AuditLogger::log('DOWNLOAD_CERTIFICATE','Training & Development',"Downloaded {$number}");
        echo '<!doctype html><html><head><meta charset="utf-8"><title>'.$number.'</title><style>body{font-family:Arial;background:#f4f7fb;padding:40px;color:#111827}.certificate{max-width:900px;margin:auto;background:#fff;border:12px double #5145e5;padding:64px;text-align:center}.eyebrow,.course{color:#5145e5}.name{font-size:42px;margin:25px}.course{font-size:25px}.meta{margin-top:35px;color:#64748b;line-height:1.8}</style></head><body><main class="certificate"><div class="eyebrow">CORE 3 HRMS</div><h1>Certificate of Completion</h1><p>This certifies that</p><div class="name">'.$name.'</div><p>has successfully completed</p><div class="course">'.$title.'</div><div class="meta">Completion date: '.$date.'<br>Assessment result: '.$score.'%<br>Certificate number: '.$number.'</div></main></body></html>';exit;
    }

    private function courseInput() {
        $title=sanitize_input($_POST['title']??'');$description=trim($_POST['description']??'');$category=(int)($_POST['category_id']??0);$trainer=(int)($_POST['trainer_id']??0);$start=$_POST['start_date']??'';$end=$_POST['end_date']??'';$capacity=(int)($_POST['capacity']??0);$status=sanitize_input($_POST['status']??'Draft');$venue=sanitize_input($_POST['venue']??'');$courseType=sanitize_input($_POST['course_type']??'Internal');
        $targetDepartment=(int)($_POST['target_department_id']??0);$targetPosition=(int)($_POST['target_position_id']??0);$prerequisite=(int)($_POST['prerequisite_course_id']??0);$difficulty=sanitize_input($_POST['difficulty_level']??'Intermediate');$retraining=max(0,min(120,(int)($_POST['retraining_months']??0)));
        if($title===''||strlen($title)>150)throw new RuntimeException('A training title of up to 150 characters is required.');
        if($description==='')throw new RuntimeException('A training description is required.');
        if($venue==='')throw new RuntimeException('A venue or platform is required.');
        if(!in_array($courseType,['Internal','External','Online','Mandatory'],true))throw new RuntimeException('Invalid course type.');
        if(!$this->training->db->fetchOne("SELECT id FROM training_categories WHERE id=?",[$category]))throw new RuntimeException('Select a valid category.');
        if($trainer&&!$this->training->db->fetchOne("SELECT id FROM trainers WHERE id=?",[$trainer]))throw new RuntimeException('Select a valid trainer.');
        if($targetDepartment&&!$this->training->db->fetchOne("SELECT id FROM departments WHERE id=?",[$targetDepartment]))throw new RuntimeException('Select a valid target department.');
        if($targetPosition&&!$this->training->db->fetchOne("SELECT id FROM positions WHERE id=?",[$targetPosition]))throw new RuntimeException('Select a valid target position.');
        if($prerequisite&&!$this->training->db->fetchOne("SELECT id FROM training_courses WHERE id=?",[$prerequisite]))throw new RuntimeException('Select a valid prerequisite training.');
        if($prerequisite&&(int)($_POST['id']??0)===$prerequisite)throw new RuntimeException('A training cannot be its own prerequisite.');
        if(!in_array($difficulty,['Beginner','Intermediate','Advanced'],true))throw new RuntimeException('Select a valid difficulty level.');
        if(!$start||!$end||strtotime($end)<strtotime($start))throw new RuntimeException('End date must be on or after the start date.');
        if($capacity<1||$capacity>10000)throw new RuntimeException('Capacity must be between 1 and 10,000.');
        if(!in_array($status,Training::COURSE_STATUSES,true))throw new RuntimeException('Invalid training status.');
        return ['title'=>$title,'description'=>$description,'category_id'=>$category,'trainer_id'=>$trainer?:null,'venue'=>$venue,'start_date'=>$start,'end_date'=>$end,'capacity'=>$capacity,'requirements'=>trim($_POST['requirements']??''),'target_department_id'=>$targetDepartment?:null,'target_position_id'=>$targetPosition?:null,'required_skills'=>trim($_POST['required_skills']??''),'prerequisite_course_id'=>$prerequisite?:null,'difficulty_level'=>$difficulty,'certification_provided'=>sanitize_input($_POST['certification_provided']??'')?:null,'retraining_months'=>$retraining,'course_type'=>$courseType,'duration_hours'=>max(1,(int)($_POST['duration_hours']??8)),'budget'=>max(0,(float)($_POST['budget']??0)),'status'=>$status,'is_active'=>$status==='Cancelled'?0:1];
    }

    private function adminPost(){Auth::requirePermission('manage_training');Auth::requireMethod('POST');$this->csrf();}
    private function employeePost(){Auth::requirePermission('enroll_training');Auth::requireMethod('POST');$this->csrf();}
    private function csrf(){if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid CSRF token.',[],403);}
    private function notifyParticipants($courseId,$title,$message,$type){foreach($this->training->getRegistrationsWithDetails(null,$courseId) as $row)$this->notifications->createForEmployee($row['employee_id'],$title,$message,$type,"index.php?page=training_details&id={$courseId}");}

    private function saveUpload($field,$folder,array $extensions,$maxBytes,$required=false) {
        if(!isset($_FILES[$field])||$_FILES[$field]['error']===UPLOAD_ERR_NO_FILE){if($required)throw new RuntimeException('Select a file to upload.');return null;}
        $file=$_FILES[$field];if($file['error']!==UPLOAD_ERR_OK)throw new RuntimeException('The file upload failed.');if((int)$file['size']<=0||(int)$file['size']>$maxBytes)throw new RuntimeException('The uploaded file exceeds the allowed size.');
        $extension=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));if(!in_array($extension,$extensions,true))throw new RuntimeException('This file type is not allowed.');
        $dir=ROOT_PATH.'public/uploads/training/'.$folder.'/';if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to prepare the upload directory.');
        $name=bin2hex(random_bytes(16)).'.'.$extension;if(!move_uploaded_file($file['tmp_name'],$dir.$name))throw new RuntimeException('Unable to save the uploaded file.');
        return 'uploads/training/'.$folder.'/'.$name;
    }

    private function downloadFile($relative,$fallback) {
        $root=realpath(ROOT_PATH.'public/uploads/training');$path=realpath(ROOT_PATH.'public/'.str_replace(['/',chr(92)],DIRECTORY_SEPARATOR,ltrim($relative,'/'.chr(92))));
        if(!$root||!$path||!is_file($path)||strpos($path,$root.DIRECTORY_SEPARATOR)!==0){http_response_code(404);exit('File not found.');}
        $extension=pathinfo($path,PATHINFO_EXTENSION);header('Content-Type:'.(mime_content_type($path)?:'application/octet-stream'));header('Content-Length:'.filesize($path));header('Content-Disposition:attachment; filename="'.$fallback.'.'.$extension.'"');header('X-Content-Type-Options:nosniff');readfile($path);exit;
    }
}

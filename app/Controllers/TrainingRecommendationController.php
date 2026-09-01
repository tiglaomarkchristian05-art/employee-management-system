<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/TrainingRecommendation.php';
require_once APP_PATH . 'Models/Training.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Notification.php';

class TrainingRecommendationController extends Controller {
    private TrainingRecommendation $recommendations;
    private Training $training;
    private Notification $notifications;

    public function __construct(){
        $this->recommendations=new TrainingRecommendation();
        $this->training=new Training();
        $this->notifications=new Notification();
    }

    public function index(){
        Auth::requireAuth();$admin=Auth::isAdmin();$employeeId=$admin?null:Auth::employeeId();
        $this->view('training/recommendations',[
            'is_admin'=>$admin,
            'recommendations'=>$this->recommendations->recommendations($employeeId),
            'summary'=>$admin?$this->recommendations->summary():[],
            'employees'=>$admin?(new Employee())->getAllWithDetails():[],
            'departments'=>$admin?$this->recommendations->db->fetchAll('SELECT id,name FROM departments ORDER BY name'):[],
            'positions'=>$admin?$this->recommendations->db->fetchAll('SELECT id,title FROM positions ORDER BY title'):[],
            'courses'=>$admin?$this->training->getCoursesWithDetails(true):[]
        ]);
    }

    public function analyze(){
        $this->adminPost();$employeeId=(int)($_POST['employee_id']??0);
        try{
            $result=$this->recommendations->analyze($employeeId?:null);
            foreach($result['notify_employee_ids'] as $id)$this->notifications->createForEmployee($id,'New training recommendations available','Training Needs Analysis identified job-related training recommendations for you.','info','index.php?page=training_recommendations','training');
            if($result['high_priority']>0)$this->notifications->createForAdmins('High-priority training needs detected',$result['high_priority'].' high-priority recommendation(s) require HR review.','warning','index.php?page=training_recommendations','training');
            AuditLogger::log('GENERATE_AI_TRAINING_NEEDS','AI Training Recommendations','Generated '.$result['recommendations_generated'].' recommendation(s) for '.$result['employees_analyzed'].' employee(s).',null,['record_type'=>'ai_training_analysis','new_value'=>['algorithm'=>TrainingRecommendation::ALGORITHM_VERSION,'scope_employee_id'=>$employeeId?:null,'result'=>$result]]);
            $this->json('success','Analysis completed: '.$result['recommendations_generated'].' recommendation(s) generated.',$result);
        }catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function review(){
        $this->adminPost();$id=(int)($_POST['id']??0);$decision=$_POST['decision']??'';$reason=trim($_POST['dismissed_reason']??'');
        if(!in_array($decision,['Accepted','Dismissed'],true))$this->json('error','Select a valid review decision.',[],422);
        $row=$this->recommendations->find($id);if(!$row||!in_array($row['status'],['Pending Review','Accepted'],true))$this->json('error','Reviewable recommendation not found.',[],404);
        $this->recommendations->update($id,['status'=>$decision,'reviewed_by'=>(int)Auth::user()['id'],'reviewed_at'=>date('Y-m-d H:i:s'),'dismissed_reason'=>$decision==='Dismissed'?($reason?:'Dismissed after HR review'):null]);
        AuditLogger::log('AI_RECOMMENDATION_'.strtoupper($decision),'AI Training Recommendations','Recommendation ID '.$id.' marked '.$decision.'.',null,['record_type'=>'ai_training_recommendation','record_id'=>$id,'old_value'=>['status'=>$row['status']],'new_value'=>['status'=>$decision,'dismissed_reason'=>$decision==='Dismissed'?$reason:null]]);
        $this->json('success','Recommendation marked '.$decision.'.');
    }

    public function assign(){
        $this->adminPost();$id=(int)($_POST['id']??0);$row=$this->recommendations->find($id);
        if(!$row||!in_array($row['status'],['Pending Review','Accepted'],true))$this->json('error','Assignable recommendation not found.',[],404);
        try{
            $result=$this->training->assignEmployees((int)$row['training_id'],[(int)$row['employee_id']],Auth::user()['id']);
            if(!$result['assigned'])throw new RuntimeException('The employee already has an active assignment for this training.');
            $registration=$this->training->getRegistrationForEmployee((int)$row['training_id'],(int)$row['employee_id']);
            $course=$this->training->getCourseWithDetails((int)$row['training_id']);
            $this->recommendations->update($id,['status'=>'Assigned','reviewed_by'=>(int)Auth::user()['id'],'reviewed_at'=>date('Y-m-d H:i:s'),'assigned_registration_id'=>(int)$registration['id']]);
            $this->notifications->createForEmployee((int)$row['employee_id'],'Recommended training assigned','HR assigned the recommended training: '.$course['title'].'.','info','index.php?page=training_details&id='.(int)$row['training_id'],'training',(int)$registration['id']);
            AuditLogger::log('ASSIGN_AI_RECOMMENDED_TRAINING','AI Training Recommendations','Assigned recommendation ID '.$id.' through training registration ID '.$registration['id'].'.',null,['record_type'=>'ai_training_recommendation','record_id'=>$id,'old_value'=>['status'=>$row['status']],'new_value'=>['status'=>'Assigned','training_id'=>(int)$row['training_id'],'employee_id'=>(int)$row['employee_id']]]);
            $this->json('success','Recommended training assigned through the existing training workflow.');
        }catch(Throwable $e){$this->json('error',$e->getMessage(),[],422);}
    }

    public function saveHistorical(){
        $this->adminPost();$employeeId=(int)($_POST['employee_id']??0);$courseId=(int)($_POST['course_id']??0);$status=$_POST['status']??'';
        $allowed=['Attended','Absent','Completed','Failed'];if(!in_array($status,$allowed,true))$this->json('error','Select a valid historical outcome.',[],422);
        $employee=$this->recommendations->db->fetchOne('SELECT id FROM employees WHERE id=?',[$employeeId]);$course=$this->training->getCourseWithDetails($courseId);
        if(!$employee||!$course)$this->json('error','Select a valid employee and training.',[],422);
        $attendance=max(0,min(100,(int)($_POST['attendance_percentage']??0)));$score=$_POST['assessment_result']??'';$score=$score===''?null:(float)$score;
        if($score!==null&&($score<0||$score>100))$this->json('error','Assessment result must be between 0 and 100.',[],422);
        if($status==='Completed'&&($attendance<=0||$score===null))$this->json('error','Completed history requires attendance and an assessment result.',[],422);
        $completedAt=$_POST['completed_at']??'';if($status==='Completed'&&(!$completedAt||strtotime($completedAt)>time()))$this->json('error','Provide a valid historical completion date.',[],422);
        $existing=$this->training->getRegistrationForEmployee($courseId,$employeeId);$data=['status'=>$status,'attendance_percentage'=>$attendance,'assessment_result'=>$score,'result_notes'=>trim($_POST['result_notes']??''),'completed_at'=>$status==='Completed'?$completedAt:null,'assigned_by'=>(int)Auth::user()['id']];
        if($existing)$this->recommendations->db->update('training_registrations',$data,'id=?',[(int)$existing['id']]);else $this->recommendations->db->insert('training_registrations',['course_id'=>$courseId,'employee_id'=>$employeeId]+$data);
        AuditLogger::log('ENCODE_HISTORICAL_TRAINING','AI Training Recommendations','Encoded '.$status.' historical training for employee ID '.$employeeId.' and training ID '.$courseId.'.',null,['record_type'=>'training_registration','record_id'=>$existing['id']??null,'new_value'=>['employee_id'=>$employeeId,'training_id'=>$courseId,'status'=>$status,'attendance'=>$attendance,'assessment'=>$score,'completed_at'=>$completedAt?:null]]);
        $this->json('success','Historical training record saved for future analysis.');
    }

    public function export(){
        Auth::requireRole(['Super Admin','HR Manager']);
        $rows=$this->recommendations->recommendations();header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="ai-training-needs-'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');fputcsv($out,['Employee Code','Employee','Department','Position','Recommended Training','Score','Priority','Detected Gap','Explanation','Status','Algorithm','Generated']);
        foreach($rows as $r)fputcsv($out,[$r['employee_code'],$r['employee_name'],$r['department_name'],$r['position_title'],$r['training_title'],$r['recommendation_score'],$r['priority'],$r['detected_gap'],$r['reason'],$r['status'],$r['algorithm_version'],$r['generated_at']]);fclose($out);exit;
    }

    private function adminPost(){Auth::requirePermission('manage_training');Auth::requireMethod('POST');if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid CSRF token.',[],403);}
}
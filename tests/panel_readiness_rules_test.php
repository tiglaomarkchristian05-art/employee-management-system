<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/config/config.php';require ROOT_PATH.'config/database.php';require ROOT_PATH.'core/Database.php';
require APP_PATH.'Models/Document.php';require APP_PATH.'Models/Notification.php';require APP_PATH.'Services/TrainingCompletionRules.php';require APP_PATH.'Services/StatutoryCalculator.php';
function ruleCheck($ok,$message){if(!$ok)throw new RuntimeException('FAIL: '.$message);echo "PASS: $message\n";}
$db=Database::getInstance();$pdo=$db->getConnection();$pdo->beginTransaction();
try{
    $model=new Document();
    $id=$db->insert('contracts',['employee_id'=>7,'contract_type'=>'Project-Based','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d',strtotime('+45 days')),'status'=>'Active','renewal_status'=>'Not Required']);
    foreach([30,60,90] as $days){$db->query("UPDATE system_settings SET setting_value=? WHERE setting_key='contract_expiration_warning_days'",[(string)$days]);ruleCheck($model->contractWarningDays()===$days,'Configured warning '.$days.' days loads');$model->refreshContractStatuses(null,$id);$r=$model->getContract($id);ruleCheck($r['renewal_status']===($days===30?'Not Required':'For Review'),'45-day contract reacts to '.$days.'-day threshold');}
    $hrRole=$db->fetchOne("SELECT id FROM roles WHERE name='HR Officer'");$roleId=$hrRole['id']??$db->insert('roles',['name'=>'HR Officer']);
    $userId=$db->insert('users',['username'=>'local.qa.hr_officer.'.bin2hex(random_bytes(4)),'password'=>password_hash(bin2hex(random_bytes(12)),PASSWORD_DEFAULT),'role_id'=>$roleId,'is_active'=>1]);
    $notes=new Notification();$title='LOCAL QA role routing '.bin2hex(random_bytes(4));
    $notes->createForAdmins($title,'Operational test','info','index.php?page=documents','documents',null);
    $recipients=$db->fetchAll('SELECT r.name FROM notifications n JOIN users u ON u.id=n.user_id JOIN roles r ON r.id=u.role_id WHERE n.title=?',[$title]);$names=array_column($recipients,'name');
    ruleCheck(in_array('HR Manager',$names)&&in_array('HR Officer',$names)&&!in_array('Super Admin',$names),'Operational routing includes HR Officer, not Super Admin');
    $notes->createForAdmins($title.' security','Security test','warning','index.php?page=admin_settings','system');
    $recipients=$db->fetchAll('SELECT r.name FROM notifications n JOIN users u ON u.id=n.user_id JOIN roles r ON r.id=u.role_id WHERE n.title=?',[$title.' security']);ruleCheck(array_unique(array_column($recipients,'name'))===['Super Admin'],'Security notification limited to Super Admin');
    $before=(int)$db->fetchOne('SELECT COUNT(*) n FROM notifications')['n'];$notes->createForAdmins($title,'Operational test','info','index.php?page=documents','documents',null);ruleCheck((int)$db->fetchOne('SELECT COUNT(*) n FROM notifications')['n']===$before,'Exact unread HR notifications do not duplicate');
}finally{$pdo->rollBack();}
$base=['attendance_percentage'=>95,'assessment_result'=>80,'assessment_required'=>1,'minimum_passing_score'=>80,'minimum_attendance_percentage'=>90];
ruleCheck(TrainingCompletionRules::failure($base)===null,'Configured passing score accepted at exact boundary');
ruleCheck(TrainingCompletionRules::failure(array_replace($base,['assessment_result'=>79.99]))!==null,'Below configured passing score rejected');
ruleCheck(TrainingCompletionRules::failure(array_replace($base,['assessment_required'=>0,'assessment_result'=>null]))===null,'Optional assessment permits missing score');
ruleCheck(TrainingCompletionRules::failure(array_replace($base,['attendance_percentage'=>89]))!==null,'Configured attendance minimum enforced');
ruleCheck(TrainingCompletionRules::failure(['attendance_percentage'=>1,'assessment_result'=>75])===null,'Legacy defaults retained');
$calc=new StatutoryCalculator();
foreach([0,-1,INF,250000] as $gross){try{$calc->calculate($gross);throw new LogicException('Expected validation error');}catch(RuntimeException $e){ruleCheck(true,'Invalid/unsupported amount rejected: '.(string)$gross);}}
try{$calc->assertConfirmed(date('Y-m-d'));throw new LogicException('Expected confirmation guard');}catch(RuntimeException $e){ruleCheck(strpos($e->getMessage(),'confirmation')!==false,'Unconfirmed legacy rates cannot be posted');}
echo "Focused rule tests passed; configuration and temporary HR Officer rolled back.\n";

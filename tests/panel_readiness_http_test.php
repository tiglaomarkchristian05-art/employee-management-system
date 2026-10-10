<?php
// Local HTTP integration test. Uses temporary authenticated sessions, not password
// resets. Keeps one Maria version-history proof; disposable fixtures are removed.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
ob_start();
require dirname(__DIR__).'/config/config.php';require ROOT_PATH.'config/database.php';require ROOT_PATH.'core/Database.php';
require APP_PATH.'Models/Notification.php';require APP_PATH.'Models/Separation.php';require APP_PATH.'Models/Benefit.php';require APP_PATH.'Services/TrainingCertificateService.php';require APP_PATH.'Services/StatutoryCalculator.php';
if(APP_ENV!=='local'||!in_array(DB_HOST,['localhost','127.0.0.1','::1'],true))throw new RuntimeException('Local testing only.');
session_destroy();
$db=Database::getInstance();$sessions=[];$courses=[];$separationId=0;$randomUser=0;$claimId=0;$paths=[];$passed=0;$correctionId=0;
$base='http://localhost/employee/index.php';
function check($ok,$message){global $passed;if(!$ok)throw new RuntimeException('FAIL: '.$message);$passed++;echo "PASS: $message\n";}
function asUser($username){global $db,$sessions;
    if(isset($sessions[$username]))return;
    $u=$db->fetchOne('SELECT u.*,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE username=?',[$username]);
    if(!$u)throw new RuntimeException('Missing test account '.$username);
    $sid=bin2hex(random_bytes(24));session_id($sid);session_start();
    $_SESSION=['user_id'=>$u['id'],'auth_version'=>$u['auth_version'],'role_name'=>$u['role_name'],'employee_id'=>$u['employee_id'],'last_activity_at'=>time(),'csrf_token'=>bin2hex(random_bytes(32))];
    $sessions[$username]=['id'=>$sid,'csrf'=>$_SESSION['csrf_token']];session_write_close();
}
function request($username,$page,$fields=null,$query=[]){global $sessions,$base;
    asUser($username);$s=$sessions[$username];$ch=curl_init($base.'?'.http_build_query(['page'=>$page]+$query));
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_COOKIE=>'PHPSESSID='.$s['id'],CURLOPT_HTTPHEADER=>['Accept: application/json','X-Requested-With: XMLHttpRequest']]);
    if($fields!==null){$fields['csrf_token']=$s['csrf'];curl_setopt($ch,CURLOPT_POST,true);$multipart=false;foreach($fields as $v)if($v instanceof CURLFile)$multipart=true;curl_setopt($ch,CURLOPT_POSTFIELDS,$multipart?$fields:http_build_query($fields));}
    $body=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
    if($body===false)throw new RuntimeException($error);return ['code'=>$status,'body'=>$body,'json'=>json_decode($body,true)];
}
function success($r,$message){check($r['code']===200&&($r['json']['status']??'')==='success',$message.' '.(($r['json']['status']??'')==='success'?'':substr($r['body'],0,350)));return $r['json']['data'];}
function denied($r,$message){check(in_array($r['code'],[403,404],true),$message.' (HTTP '.$r['code'].')');}
function fileUpload(){return new CURLFile(ROOT_PATH.'sample-training-material-data-privacy.pdf','application/pdf','LOCAL-QA-document.pdf');}
try{
    $notificationStart=(int)$db->fetchOne('SELECT COALESCE(MAX(id),0) n FROM notifications')['n'];
    $auditStart=(int)$db->fetchOne('SELECT MAX(id) n FROM audit_logs')['n'];
    $history=$db->fetchAll('SELECT * FROM training_certificate_versions WHERE registration_id=63 ORDER BY version_no');
    $hashes=[];foreach($history as $v)$hashes[$v['file_path']]=hash_file('sha256',ROOT_PATH.'public/'.$v['file_path']);
    $carlo=$db->fetchOne("SELECT s.* FROM separations s JOIN employees e ON e.id=s.employee_id WHERE e.employee_code='DEMO-C3-006' ORDER BY s.id DESC LIMIT 1");
    $items=$db->fetchAll('SELECT c.*,u.username FROM clearances c JOIN users u ON u.id=c.responsible_user_id WHERE separation_id=?',[$carlo['id']]);$byUser=array_column($items,null,'username');
    foreach(['demo.it_approver','demo.finance_approver','mchen'] as $name){
        $r=request($name,'separation_clearance',null,['id'=>$carlo['id']]);check($r['code']===200&&strpos($r['body'],'Assigned Clearance Items')!==false,$name.' opens assigned Carlo item');
        preg_match_all('/name="clearance_id" value="(\d+)"/',$r['body'],$visible);echo 'VISIBLE ITEMS: '.$name.' '.json_encode($visible[1])."\n";
        if(strpos($r['body'],'Fatal error')!==false)throw new RuntimeException(strip_tags(substr($r['body'],strpos($r['body'],'Fatal error'),900)));
        foreach($items as $item)check((strpos($r['body'],'name="clearance_id" value="'.$item['id'].'"')!==false)===($item['username']===$name),$name.' sees only own item '.$item['id']);
    }
    denied(request('demo.it_approver','separation_update_clearance',['clearance_id'=>$byUser['demo.finance_approver']['id'],'status'=>'Cleared']),'IT cannot update Finance');
    denied(request('demo.finance_approver','separation_update_clearance',['clearance_id'=>$byUser['demo.it_approver']['id'],'status'=>'Cleared']),'Finance cannot update IT');
    denied(request('demo.john','separation_clearance',null,['id'=>$carlo['id']]),'Employee cannot access Carlo case');
    denied(request('demo.it_approver','separation_clearance',null,['id'=>1]),'Approver cannot access unrelated Clarissa case');
    foreach(['documents','documents_contracts','benefits','benefits_loans','training','compliance'] as $page)denied(request('demo.it_approver',$page),'Unlinked approver has no broad '.$page.' self-service access');
    foreach(['documents','documents_contracts','benefits','benefits_loans'] as $page)denied(request('admin',$page),'System Admin does not get unfiltered '.$page.' records');
    $ch=curl_init('http://localhost/employee/uploads/loans/275350b603bb49cd5d0c7ca593834111.pdf');curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]);curl_exec($ch);check(curl_getinfo($ch,CURLINFO_HTTP_CODE)===403,'Apache blocks direct loan upload URL');curl_close($ch);
    $randomUser=(int)$db->insert('users',['username'=>'local.qa.unassigned.'.bin2hex(random_bytes(4)),'password'=>password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT),'role_id'=>3,'is_active'=>1,'mfa_mode'=>'off']);
    $randomName=$db->fetchOne('SELECT username FROM users WHERE id=?',[$randomUser])['username'];denied(request($randomName,'separation_clearance',null,['id'=>$carlo['id']]),'Unassigned Department Head denied Carlo');
    check(!(new Separation())->authorizedApprover(3,3),'Self-approval disallowed by approver validation');

    // Actual updates and finalization use a disposable case, not Carlo.
    $separationId=(int)$db->insert('separations',['employee_id'=>7,'separation_type'=>'Other','notice_date'=>date('Y-m-d'),'effective_date'=>date('Y-m-d'),'final_working_date'=>date('Y-m-d'),'reason'=>'LOCAL QA disposable authorization case','status'=>'Processing']);
    $fields=['id'=>$separationId,'include'=>[],'responsible'=>[],'requirement'=>[],'mandatory'=>[]];
    foreach(['demo.it_approver','demo.finance_approver'] as $name){$i=$byUser[$name];$d=$i['department_id'];$fields['include'][$d]=1;$fields['responsible'][$d]=$i['responsible_user_id'];$fields['requirement'][$d]='LOCAL QA return assigned property';$fields['mandatory'][$d]=1;}
    success(request('hr_manager','separation_generate_clearance',$fields),'HR generates disposable named clearance');
    $qaItems=$db->fetchAll('SELECT c.*,u.username FROM clearances c JOIN users u ON u.id=c.responsible_user_id WHERE separation_id=?',[$separationId]);
    foreach($qaItems as $item){success(request($item['username'],'separation_update_clearance',['clearance_id'=>$item['id'],'status'=>'Cleared','accountability_details'=>'','comments'=>'LOCAL QA verified','department_id'=>999999,'responsible_user_id'=>1,'employee_id'=>9,'is_mandatory'=>0,'clearance_requirement'=>'tamper']),'Assigned user can update permitted fields');$after=$db->fetchOne('SELECT * FROM clearances WHERE id=?',[$item['id']]);check($after['department_id']===$item['department_id']&&$after['responsible_user_id']===$item['responsible_user_id']&&$after['is_mandatory']===$item['is_mandatory']&&$after['clearance_requirement']===$item['clearance_requirement'],'Approver cannot change assignment/configuration');}
    success(request('hr_manager','separation_complete',['id'=>$separationId]),'Resolved disposable case reaches Ready for Payroll');
    $r=request('hr_manager','separation_complete',['id'=>$carlo['id']]);check($r['code']===422&&strpos($r['body'],'unresolved')!==false,'Carlo final clearance still blocked');

    // One retained Maria document proof. Original profile and documents untouched.
    $title='LOCAL QA - Panel document version proof';
    $current=$db->fetchOne('SELECT * FROM documents WHERE employee_id=4 AND title=? ORDER BY id DESC LIMIT 1',[$title]);
    if(!$current){$d=success(request('employee','documents_upload',['title'=>$title,'category_id'=>1,'document_file'=>fileUpload()]),'Maria submits v1');$current=$db->fetchOne('SELECT * FROM documents WHERE id=?',[$d['id']]);}
    if((int)$current['version_no']===1){
        success(request('hr_manager','documents_review',['id'=>$current['id'],'status'=>'Returned','remarks'=>'LOCAL QA: replace with the corrected readable copy.']),'HR returns Maria v1');
        denied(request('demo.john','documents_upload',['replaces_document_id'=>$current['id'],'category_id'=>1,'title'=>$title,'document_file'=>fileUpload()]),'John cannot replace Maria document');
        $d=success(request('employee','documents_upload',['title'=>$title,'category_id'=>1,'replaces_document_id'=>$current['id'],'document_file'=>fileUpload()]),'Maria resubmits v2');$current=$db->fetchOne('SELECT * FROM documents WHERE id=?',[$d['id']]);
    }
    if($current['status']!=='Approved')success(request('hr_manager','documents_review',['id'=>$current['id'],'status'=>'Approved','remarks'=>'LOCAL QA: corrected copy approved.']),'HR approves v2');
    $current=$db->fetchOne('SELECT * FROM documents WHERE id=?',[$current['id']]);$old=$db->fetchOne('SELECT * FROM documents WHERE id=?',[$current['replaces_document_id']]);
    check((int)$current['version_no']===2&&$old['status']==='Returned'&&(int)$old['reviewed_by']===2&&$old['reviewed_at']&&$current['status']==='Approved'&&(int)$current['reviewed_by']===2,'Maria v1 status/reviewer retained; v2 approved and current');
    check((bool)$db->fetchOne('SELECT id FROM document_versions WHERE document_id=? AND version_no=1 AND file_path=?',[$current['id'],$old['file_path']]),'document_versions preserves v1 file snapshot');
    foreach([$old,$current] as $doc){check(is_file(ROOT_PATH.'public/'.$doc['file_path']),'Document v'.$doc['version_no'].' physical file preserved');denied(request('demo.john','documents_download',null,['id'=>$doc['id']]),'John denied Maria document v'.$doc['version_no']);}
    check(request('hr_manager','documents_review',['id'=>$old['id'],'status'=>'Approved','remarks'=>'Must not rewrite history'])['code']===422,'Superseded document review blocked');
    echo 'MARIA_PROOF: v1='.$old['id'].' v2='.$current['id']."\n";

    // Three isolated course rules; real participant completion invokes PDF service.
    foreach(['passed'=>[1,90,200],'failed'=>[1,42,422],'optional'=>[0,null,200]] as $case=>[$required,$score,$expected]){
        $courseId=(int)$db->insert('training_courses',['category_id'=>1,'title'=>'LOCAL QA certificate '.$case,'description'=>'Disposable rule validation','course_type'=>'Internal','duration_hours'=>4,'venue'=>'Local QA','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d'),'status'=>'Scheduled','capacity'=>30,'assessment_required'=>$required,'minimum_passing_score'=>80,'minimum_attendance_percentage'=>90]);$courses[]=$courseId;
        success(request('hr_manager','training_assign',['course_id'=>$courseId,'employee_ids'=>[7]]),'Actual HR training assignment '.$case);
        $reg=$db->fetchOne('SELECT * FROM training_registrations WHERE course_id=?',[$courseId]);
        $low=request('hr_manager','training_update_participant',['registration_id'=>$reg['id'],'status'=>'Completed','attendance_percentage'=>89,'assessment_result'=>$score??'']);check($low['code']===422,'Configured attendance minimum blocks '.$case);
        $result=request('hr_manager','training_update_participant',['registration_id'=>$reg['id'],'status'=>'Completed','attendance_percentage'=>95,'assessment_result'=>$score??'']);check($result['code']===$expected,'Certificate completion case '.$case.' expected HTTP '.$expected.' received '.$result['code'].' '.($result['code']===$expected?'':substr($result['body'],0,250)));
        $saved=$db->fetchOne('SELECT * FROM training_registrations WHERE id=?',[$reg['id']]);
        if($expected===200){check($saved['certificate_file']&&is_file(ROOT_PATH.'public/'.$saved['certificate_file']),'Real automated PDF generated for '.$case);$paths[]=$saved['certificate_file'];check((bool)$db->fetchOne('SELECT id FROM employee_certifications WHERE related_training_registration_id=?',[$reg['id']]),'Registry synchronized for '.$case);if($case==='optional')copy(ROOT_PATH.'public/'.$saved['certificate_file'],__DIR__.'/artifacts/panel-optional-assessment.pdf');}
        else check(!$saved['certificate_file'],'Failed assessment generates no PDF');
    }

    // Benefit submit/review through actual routes, using eligible John and same plan.
    $benefit=new Benefit();
    [$eligible]=$benefit->eligibility(7,8);check($eligible,'John eligible for dedicated QA benefit application');
    $d=success(request('demo.john','benefits_submit_claim',['benefit_id'=>7,'claim_type'=>'LOCAL QA panel evidence','amount'=>100,'requirement_file'=>fileUpload()]),'Actual benefit submission');$claimId=(int)$d['id'];
    $claim=$db->fetchOne('SELECT * FROM benefit_claims WHERE id=?',[$claimId]);$paths[]=$claim['requirement_file'];
    success(request('hr_manager','admin_update_claim_status',['id'=>$claimId,'status'=>'Under Review','admin_remarks'=>'LOCAL QA notification/audit proof']),'Actual HR benefit review');
    denied(request('demo.liza','benefits_requirement',null,['id'=>$claimId]),'Liza denied John benefit attachment');

    // Ownership checks against real rows; 404 is deliberate non-disclosure.
    denied(request('demo.john','training_certificate',null,['id'=>63]),'John denied Paolo certificate PDF');
    $cert=$db->fetchOne('SELECT id FROM employee_certifications WHERE related_training_registration_id=63');denied(request('demo.john','certifications_download',null,['id'=>$cert['id']]),'John denied Paolo registry download');
    $contract=$db->fetchOne('SELECT id FROM contracts WHERE employee_id=9 AND status=\'Active\' ORDER BY id DESC LIMIT 1');denied(request('demo.john','contracts_download',null,['id'=>$contract['id']]),'John denied Liza contract');
    denied(request('demo.john','benefits_loan_detail',null,['loan_id'=>7]),'John denied Paolo loan details');
    denied(request('demo.john','compliance_download_contribution',null,['id'=>1]),'John denied Maria contribution download');
    $n=$db->fetchOne("SELECT id FROM notifications WHERE user_id=4 AND module='documents' ORDER BY id DESC LIMIT 1");check((bool)$n,'Owned Maria notification exists for negative test');denied(request('demo.john','notification_open',null,['id'=>$n['id']]),'John denied Maria notification');
    $r=request('demo.john','certifications',null,['verification_code'=>$history[0]['verification_code']]);check($r['code']===200&&strpos($r['body'],'CERT-2026-000063')===false,'John cannot verify another employee certificate through registry code');
    $gov=$db->fetchOne("SELECT g.id FROM government_records g WHERE g.employee_id=4 AND g.status='Verified' AND NOT EXISTS(SELECT 1 FROM government_corrections c WHERE c.government_record_id=g.id AND c.status='Pending') LIMIT 1");
    check((bool)$gov,'Verified government record available for isolated correction test');
    success(request('employee','compliance_request_correction',['record_id'=>$gov['id'],'proposed_value'=>'LOCAL-QA-12345','reason'=>'LOCAL QA ownership test','supporting_file'=>fileUpload()]),'Government correction workflow remains usable');
    $correction=$db->fetchOne("SELECT * FROM government_corrections WHERE government_record_id=? AND reason='LOCAL QA ownership test' ORDER BY id DESC LIMIT 1",[$gov['id']]);$correctionId=(int)$correction['id'];$paths[]=$correction['supporting_file'];
    denied(request('demo.john','compliance_supporting_file',null,['type'=>'correction','id'=>$correctionId]),'John denied Maria government supporting download');
    $otherReg=$db->fetchOne('SELECT id FROM training_registrations WHERE employee_id=9 LIMIT 1');denied(request('demo.john','training_confirm',['registration_id'=>$otherReg['id']]),'John denied changing Liza training record');
    $ch=curl_init('http://localhost/employee/public/'.$history[0]['file_path']);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15]);curl_exec($ch);check(curl_getinfo($ch,CURLINFO_HTTP_CODE)===403,'Apache blocks direct public/uploads certificate URL');curl_close($ch);
    $r=request('admin','compliance_calculator',null,['gross'=>250000]);check($r['code']===422&&strpos($r['body'],'supported')!==false,'High income returns configuration warning, not zero');
    $r=request('hr_manager','compliance_generate',['employee_id'=>7,'gross_salary'=>50000,'period_month'=>10,'period_year'=>2026]);check($r['code']===422&&strpos($r['body'],'confirmation')!==false,'Unconfirmed statutory rules cannot post money');
    $r=request('admin','compliance_calculator',null,['gross'=>50000]);success($r,'Supported legacy preview explicitly unvalidated');

    // Exact unread duplicate suppression and role routing use real notifications.
    $note=$db->fetchOne("SELECT * FROM notifications WHERE title='Clearance responsibility assigned' AND user_id=32 AND is_read=0 ORDER BY id DESC LIMIT 1");
    $before=(int)$db->fetchOne('SELECT COUNT(*) n FROM notifications')['n'];(new Notification())->createForUser($note['user_id'],$note['title'],$note['message'],$note['type'],$note['link'],$note['module'],$note['related_id']);check((int)$db->fetchOne('SELECT COUNT(*) n FROM notifications')['n']===$before,'Exact unread assignment notification deduplicated');
    $logs=$db->fetchAll('SELECT * FROM audit_logs WHERE id>?',[$auditStart]);
    foreach(['TRAINING_ASSIGNED','CERTIFICATE_GENERATED','SUBMIT_BENEFIT_APPLICATION','BENEFIT_APPLICATION_UNDER_REVIEW','CLEARANCE_STATUS_UPDATED','FINAL_CLEARANCE_COMPLETED','FINAL_CLEARANCE_BLOCKED'] as $action){$matching=array_filter($logs,fn($r)=>$r['action']===$action);check((bool)$matching,'Fresh audit event '.$action);foreach($matching as $log)check($log['user_id']&&$log['role_name']&&$log['record_id']&&$log['ip_address']&&$log['created_at'],'Audit has actor/role/record/IP/time for '.$action);}
    foreach(['DOCUMENT_SUBMITTED','DOCUMENT_RETURNED','DOCUMENT_RESUBMITTED','DOCUMENT_APPROVED'] as $action)check((bool)$db->fetchOne('SELECT id FROM audit_logs WHERE action=? AND record_id IN (?,?)',[$action,$old['id'],$current['id']]),'Maria proof audit '.$action);
    $notificationRows=$db->fetchAll('SELECT n.title,n.user_id,u.username,r.name role,n.module,n.related_id,n.link,n.is_read FROM notifications n JOIN users u ON u.id=n.user_id JOIN roles r ON r.id=u.role_id WHERE (n.module=\'documents\' AND n.related_id IN (?,?)) OR (n.module=\'benefits\' AND n.related_id=?) OR (n.module=\'training\' AND n.related_id IN (SELECT id FROM training_registrations WHERE course_id IN ('.implode(',',$courses).'))) ORDER BY n.id',[$current['id'],$old['id'],$claimId]);
    foreach($notificationRows as $n){check($n['role']!=='Super Admin','Operational notification excludes Super Admin');echo 'NOTIFICATION: '.json_encode($n)."\n";}
    foreach($hashes as $path=>$hash)check(hash_file('sha256',ROOT_PATH.'public/'.$path)===$hash,'Paolo historical PDF unchanged: '.basename($path));
    check(count($db->fetchAll('SELECT id FROM training_certificate_versions WHERE registration_id=63'))===count($history),'Paolo certificate version count unchanged');
    check((new Separation())->blockers($carlo['id'])>0,'Carlo blocked demo state preserved');
    check($db->fetchOne('SELECT final_pay_processing_status FROM separations WHERE id=1')['final_pay_processing_status']==='Ready for Payroll','Clarissa Ready for Payroll preserved');
    echo "HTTP integration suite passed: $passed checks.\n";
}finally{
    // Remove only notifications belonging to this run's disposable fixtures.
    foreach($courses as $id){
        $regs=$db->fetchAll('SELECT id FROM training_registrations WHERE course_id=?',[$id]);
        foreach($regs as $reg)$db->query("DELETE FROM notifications WHERE id>? AND module='training' AND related_id=?",[$notificationStart,$reg['id']]);
        $db->query("DELETE FROM notifications WHERE id>? AND module='training' AND link=?",[$notificationStart,'index.php?page=training_details&id='.$id]);
    }
    if($separationId){
        $clearances=$db->fetchAll('SELECT id FROM clearances WHERE separation_id=?',[$separationId]);
        foreach($clearances as $item)$db->query("DELETE FROM notifications WHERE id>? AND module='separation' AND related_id=? AND title='Clearance responsibility assigned'",[$notificationStart,$item['id']]);
        $db->query("DELETE FROM notifications WHERE id>? AND module='separation' AND link=?",[$notificationStart,'index.php?page=separation_clearance&id='.$separationId]);
    }
    if($claimId)$db->query("DELETE FROM notifications WHERE id>? AND module='benefits' AND related_id=?",[$notificationStart,$claimId]);
    if($correctionId){$db->query("DELETE FROM notifications WHERE id>? AND module='compliance' AND related_id=?",[$notificationStart,$correctionId]);$db->delete('government_corrections','id=?',[$correctionId]);}
    foreach($courses as $id){$db->query('DELETE FROM employee_certifications WHERE related_training_id=?',[$id]);$db->query('DELETE FROM training_registrations WHERE course_id=?',[$id]);$db->delete('training_courses','id=?',[$id]);}
    if($separationId)$db->delete('separations','id=?',[$separationId]);
    if($claimId)$db->delete('benefit_claims','id=?',[$claimId]);
    if($randomUser)$db->delete('users','id=?',[$randomUser]);
    foreach($paths as $path){$full=realpath(ROOT_PATH.'public/'.$path);$root=realpath(ROOT_PATH.'public/uploads');if($full&&$root&&strpos($full,$root.DIRECTORY_SEPARATOR)===0)unlink($full);}
    foreach($sessions as $s){$path=rtrim(session_save_path(),'/\\').DIRECTORY_SEPARATOR.'sess_'.$s['id'];if(is_file($path))unlink($path);}
    ob_end_flush();
}

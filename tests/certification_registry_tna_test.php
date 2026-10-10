<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/EmployeeCertification.php';
require dirname(__DIR__).'/app/Models/TrainingRecommendation.php';

function certCheck($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}
$db=Database::getInstance();$pdo=$db->getConnection();$pdo->beginTransaction();
try{
    $employee=$db->fetchOne("SELECT id FROM employees WHERE status IN ('Active','Probationary') ORDER BY id LIMIT 1");
    $id=(int)$db->insert('employee_certifications',['employee_id'=>$employee['id'],'certification_name'=>'Data Privacy Certification','certification_type'=>'Compliance','issuing_organization'=>'Test Authority','issue_date'=>date('Y-m-d',strtotime('-1 year')),'expiration_date'=>date('Y-m-d',strtotime('-1 day')),'status'=>'Valid']);
    $registry=new EmployeeCertification();$changes=$registry->refreshStatuses();$row=$registry->record($id);
    certCheck($row['status']==='Expired','expired credential is maintained as Expired');
    certCheck((bool)array_filter($changes,fn($change)=>(int)$change['id']===$id),'expiration creates a reportable state transition');
    foreach($registry->records((int)$employee['id']) as $owned)certCheck((int)$owned['employee_id']===(int)$employee['id'],'employee-scoped query does not expose another employee');
    $trainingCertificate=$db->fetchOne("SELECT id FROM training_registrations WHERE status='Completed' AND certificate_file IS NOT NULL ORDER BY id LIMIT 1");
    if($trainingCertificate){$first=$registry->syncTrainingCertificate((int)$trainingCertificate['id']);$second=$registry->syncTrainingCertificate((int)$trainingCertificate['id']);certCheck($first===$second,'existing training certificate maps to one registry record without duplication');}

    $engine=new TrainingRecommendation();$method=new ReflectionMethod($engine,'score');$method->setAccessible(true);
    $employeeProfile=['id'=>$employee['id'],'position_id'=>1,'department_id'=>1,'position_title'=>'Recruiter','department_name'=>'Recruitment'];
    $base=['id'=>9001,'category_id'=>1,'capacity'=>30,'enrolled_count'=>0,'prerequisite_course_id'=>null,'retraining_months'=>0,'target_position_id'=>null,'target_position_name'=>null,'target_department_id'=>null,'target_department_name'=>null,'category_name'=>'Compliance','description'=>'','difficulty_level'=>'Intermediate'];
    $history=['rows'=>[],'by_course'=>[],'completed'=>[]];$credential=[['certification_name'=>'Data Privacy Certification','certification_type'=>'Compliance','expiration_date'=>date('Y-m-d',strtotime('-1 day')),'status'=>'Expired']];
    $related=$method->invoke($engine,$employeeProfile,$base+['title'=>'Data Privacy and Applicant Information Security','required_skills'=>'data privacy, applicant security','certification_provided'=>'Data Privacy Training Certificate'],$history,[],$credential);
    certCheck($related&&$related['breakdown']['certification_need']===35.0,'expired credential increases only a matching training score');
    certCheck(strpos($related['reason'],'Data Privacy Certification is expired')!==false,'recommendation explanation states the expired certification');
    $unrelated=$method->invoke($engine,$employeeProfile,$base+['id'=>9002,'title'=>'Forklift Operations Safety','required_skills'=>'forklift operation','certification_provided'=>'Forklift Operator License'],$history,[],$credential);
    certCheck($unrelated===null||$unrelated['breakdown']['certification_need']===0.0,'unrelated training receives no certification relevance boost');
    echo "All certification registry and TNA scenarios passed.\n";
}finally{$pdo->rollBack();}

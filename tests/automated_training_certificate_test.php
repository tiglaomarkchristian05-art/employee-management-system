<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Services/TrainingCertificateService.php';

function pdfCheck($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}
$db=Database::getInstance();$courseId=0;$registrationId=0;$paths=[];
try{
    $employee=$db->fetchOne("SELECT id FROM employees WHERE status IN ('Active','Probationary') ORDER BY id LIMIT 1");$category=$db->fetchOne('SELECT id FROM training_categories ORDER BY id LIMIT 1');$user=$db->fetchOne("SELECT id FROM users WHERE is_active=1 ORDER BY id LIMIT 1");
    $courseId=(int)$db->insert('training_courses',['category_id'=>$category['id'],'title'=>'Automated Certificate Layout Validation With A Long Professional Training Title','description'=>'Certificate test','course_type'=>'Internal','duration_hours'=>8,'venue'=>'ISMERS Training Center','start_date'=>date('Y-m-d',strtotime('-2 days')),'end_date'=>date('Y-m-d',strtotime('-1 day')),'status'=>'Completed','capacity'=>30,'certification_provided'=>'Professional Development Certificate','certification_validity_months'=>12]);
    $registrationId=(int)$db->insert('training_registrations',['course_id'=>$courseId,'employee_id'=>$employee['id'],'status'=>'Confirmed','attendance_percentage'=>100,'assessment_result'=>90]);
    $service=new TrainingCertificateService();
    [$ok]=$service->eligibility($registrationId);pdfCheck(!$ok,'enrollment without Completed status does not generate a certificate');
    $db->update('training_registrations',['status'=>'Completed','attendance_percentage'=>0],'id=?',[$registrationId]);[$ok]=$service->eligibility($registrationId);pdfCheck(!$ok,'missing attendance blocks certificate generation');
    $db->update('training_registrations',['attendance_percentage'=>100,'assessment_result'=>70],'id=?',[$registrationId]);[$ok]=$service->eligibility($registrationId);pdfCheck(!$ok,'failed assessment blocks certificate generation');
    $db->update('training_registrations',['assessment_result'=>90,'completed_at'=>date('Y-m-d')],'id=?',[$registrationId]);$issued=$service->generate($registrationId,(int)$user['id']);$paths[]=$issued['path'];$absolute=dirname(__DIR__).'/public/'.$issued['path'];
    pdfCheck($issued['generated']&&is_file($absolute)&&substr(file_get_contents($absolute),0,4)==='%PDF','eligible completion generates a valid PDF file');
    pdfCheck((bool)preg_match('/^CERT-'.date('Y').'-\d{6}$/',$issued['number']),'certificate number is unique and traceable');
    $again=$service->generate($registrationId,(int)$user['id']);pdfCheck(!$again['generated']&&$again['path']===$issued['path'],'reopening a completed training reuses the certificate');
    $registry=$db->fetchAll('SELECT * FROM employee_certifications WHERE related_training_registration_id=?',[$registrationId]);pdfCheck(count($registry)===1&&$registry[0]['certificate_number']===$issued['number'],'registry is linked once without duplication');
    pdfCheck($registry[0]['expiration_date']===date('Y-m-d',strtotime('+12 months')),'configured certificate validity sets registry expiration');
    $regenerated=$service->generate($registrationId,(int)$user['id'],true,'Automated formatting test');$paths[]=$regenerated['path'];pdfCheck($regenerated['version']===2&&$regenerated['path']!==$issued['path'],'authorized regeneration creates a preserved second version');
    $versions=$db->fetchAll('SELECT * FROM training_certificate_versions WHERE registration_id=? ORDER BY version_no',[$registrationId]);pdfCheck(count($versions)===2&&is_file(dirname(__DIR__).'/public/'.$versions[0]['file_path']),'previous certificate version is preserved');
    $artifactDir=__DIR__.'/artifacts';if(!is_dir($artifactDir))mkdir($artifactDir,0755,true);$sample=$artifactDir.'/sample-automated-training-certificate.pdf';copy(dirname(__DIR__).'/public/'.$regenerated['path'],$sample);echo 'SAMPLE_PDF='.$sample."\nAll automated certificate scenarios passed.\n";
}finally{
    if($registrationId)$db->delete('training_registrations','id=?',[$registrationId]);if($courseId)$db->delete('training_courses','id=?',[$courseId]);
    foreach($paths as $path){$absolute=dirname(__DIR__).'/public/'.$path;if(is_file($absolute))unlink($absolute);}
}

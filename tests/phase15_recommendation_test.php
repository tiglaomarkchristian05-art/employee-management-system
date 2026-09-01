<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/Training.php';
require dirname(__DIR__).'/app/Models/TrainingRecommendation.php';

function check($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}
$db=Database::getInstance();$pdo=$db->getConnection();$pdo->beginTransaction();
try{
    $employee=$db->fetchOne("SELECT e.id,e.department_id,e.position_id FROM employees e WHERE e.id=1");
    check((bool)$employee,'test employee exists');
    $db->query("UPDATE training_courses SET title='Data Privacy and Applicant Protection',description='Data privacy applicant data security for recruitment',required_skills='Data Privacy, Applicant Data Security',target_department_id=?,target_position_id=?,prerequisite_course_id=NULL,status='Scheduled',is_active=1,start_date=CURRENT_DATE,end_date=DATE_ADD(CURRENT_DATE,INTERVAL 30 DAY),capacity=30 WHERE id=6",[$employee['department_id'],$employee['position_id']]);
    $db->insert('skills_matrix',['employee_id'=>1,'skill_name'=>'Data Privacy','proficiency_level'=>'Beginner','target_level'=>'Advanced','verified_by'=>'Phase 15 test']);
    $course=$db->fetchOne('SELECT category_id FROM training_courses WHERE id=6');
    $db->insert('training_registrations',['course_id'=>3,'employee_id'=>1,'status'=>'Failed','attendance_percentage'=>100,'assessment_result'=>40,'result_notes'=>'Controlled rollback test']);
    $db->query('UPDATE training_courses SET category_id=? WHERE id=6',[$db->fetchOne('SELECT category_id FROM training_courses WHERE id=3')['category_id']]);
    $engine=new TrainingRecommendation();$engine->analyze(1);
    $recommendation=$db->fetchOne("SELECT * FROM ai_training_recommendations WHERE employee_id=1 AND training_id=6 AND status='Pending Review'");
    check((bool)$recommendation,'relevant role and skill gap produces a recommendation');
    check((float)$recommendation['recommendation_score']>=70 && $recommendation['priority']==='High','skill, role and weak assessment produce High priority');
    check(strpos($recommendation['reason'],'proficiency gaps')!==false && strpos($recommendation['reason'],'weak or failed')!==false,'recommendation contains an explainable reason');

    $engine->analyze(2);
    $irrelevant=$db->fetchOne("SELECT id FROM ai_training_recommendations WHERE employee_id=2 AND training_id=6 AND status<>'Expired'");
    check(!$irrelevant,'irrelevant employee receives no recommendation');

    $db->insert('training_registrations',['course_id'=>6,'employee_id'=>1,'status'=>'Completed','attendance_percentage'=>100,'assessment_result'=>90,'completed_at'=>date('Y-m-d')]);
    $engine->analyze(1);
    $completed=$db->fetchOne("SELECT id FROM ai_training_recommendations WHERE employee_id=1 AND training_id=6 AND status<>'Expired'");
    check(!$completed,'completed non-recurring training is excluded');

    $db->query('DELETE FROM training_registrations WHERE course_id=6 AND employee_id=1');
    $db->query('UPDATE training_courses SET prerequisite_course_id=3 WHERE id=6');
    $engine->analyze(1);
    $blocked=$db->fetchOne("SELECT id FROM ai_training_recommendations WHERE employee_id=1 AND training_id=6 AND status<>'Expired'");
    check(!$blocked,'unmet mandatory prerequisite excludes immediate recommendation');

    $pdo->rollBack();echo "All Phase 15 recommendation scenarios passed; transaction rolled back.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$e->getMessage()."\n");exit(1);}
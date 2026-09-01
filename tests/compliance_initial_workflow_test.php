<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/Compliance.php';

function check($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}
";}
$db=Database::getInstance();
$pdo=$db->getConnection();
$model=new Compliance();
$source=$db->fetchOne("SELECT * FROM government_records WHERE status='Verified' ORDER BY id LIMIT 1");
check((bool)$source,'verified source record exists');
$employeeBefore=$db->fetchOne('SELECT sss_no,philhealth_no,pagibig_no,tin_no FROM employees WHERE id=?',[(int)$source['employee_id']]);
$pdo->beginTransaction();
try{
    $db->update('government_records',[
        'record_number'=>null,'status'=>'Missing','supporting_file'=>null,'original_name'=>null,
        'admin_remarks'=>null,'submitted_by'=>null,'verified_by'=>null,'verified_at'=>null
    ],'id=?',[(int)$source['id']]);
    check($model->getRecord((int)$source['id'])['status']==='Missing','record can begin as Missing');

    $first='TEST-'.$source['agency'].'-2026-001';
    $model->submitRecord((int)$source['id'],$first,['path'=>'uploads/compliance/test-support.pdf','original'=>'test-support.pdf'],1);
    $row=$model->getRecord((int)$source['id']);
    check($row['status']==='Pending Verification','employee submission becomes Pending Verification');
    check($row['record_number']===$first,'submitted identifier persists');
    check($row['supporting_file']==='uploads/compliance/test-support.pdf','supporting-file metadata persists');

    $model->reviewRecord((int)$source['id'],'Needs Correction','Please verify the number.',1);
    $row=$model->getRecord((int)$source['id']);
    check($row['status']==='Needs Correction','Admin can return record as Needs Correction');
    check($row['admin_remarks']==='Please verify the number.','return remarks persist');

    $corrected='TEST-'.$source['agency'].'-2026-002';
    $model->submitRecord((int)$source['id'],$corrected,['path'=>'uploads/compliance/test-corrected.pdf','original'=>'test-corrected.pdf'],1);
    $row=$model->getRecord((int)$source['id']);
    check($row['status']==='Pending Verification','employee resubmission returns to Pending Verification');
    check($row['record_number']===$corrected,'corrected identifier persists');
    check($row['admin_remarks']===null,'old return remarks are cleared on resubmission');

    $model->reviewRecord((int)$source['id'],'Verified','Verified during isolated test.',1);
    $row=$model->getRecord((int)$source['id']);
    check($row['status']==='Verified','Admin can verify resubmitted record');
    check(!empty($row['verified_at'])&&(int)$row['verified_by']===1,'verification actor and timestamp persist');

    echo "Compliance initial workflow integration test passed.
";
} finally {
    if($pdo->inTransaction())$pdo->rollBack();
}
$restored=$db->fetchOne('SELECT * FROM government_records WHERE id=?',[(int)$source['id']]);
$employeeAfter=$db->fetchOne('SELECT sss_no,philhealth_no,pagibig_no,tin_no FROM employees WHERE id=?',[(int)$source['employee_id']]);
check($restored['status']===$source['status']&&$restored['record_number']===$source['record_number'],'government record restored after rollback');
check($employeeAfter==$employeeBefore,'employee government fields restored after rollback');
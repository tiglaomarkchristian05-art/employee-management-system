<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/Separation.php';

function check($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}

$db=Database::getInstance();
$model=new Separation();
$separationId=0;
try{
    $employee=$db->fetchOne("SELECT id FROM employees WHERE status IN ('Active','Probationary') ORDER BY id DESC LIMIT 1");
    $department=$db->fetchOne('SELECT id,name FROM departments ORDER BY id LIMIT 1');
    $approver=$db->fetchOne("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.is_active=1 AND r.name IN ('HR Manager','HR Officer','Department Head') AND (u.employee_id IS NULL OR u.employee_id<>?) ORDER BY u.id LIMIT 1",[$employee['id']]);
    check($employee&&$department&&$approver,'test employee, department, and authorized approver exist');

    $separationId=(int)$db->insert('separations',[
        'employee_id'=>(int)$employee['id'],'separation_type'=>'Other','notice_date'=>date('Y-m-d'),
        'proposed_last_working_date'=>date('Y-m-d',strtotime('+30 days')),'effective_date'=>date('Y-m-d',strtotime('+30 days')),
        'final_working_date'=>date('Y-m-d',strtotime('+30 days')),'reason'=>'Isolated exit-clearance test','status'=>'Processing'
    ]);
    $model->createClearance($separationId,[[
        'department_id'=>(int)$department['id'],'responsible_user_id'=>(int)$approver['id'],
        'clearance_requirement'=>'Return issued property and close department accountabilities','is_mandatory'=>1
    ]]);
    $item=$model->getClearanceStatus($separationId)[0]??null;
    check($item&&(int)$item['responsible_user_id']===(int)$approver['id']&&!empty($item['responsible_name']),'selected responsible person is stored and displayed');
    check($item['clearance_requirement']==='Return issued property and close department accountabilities','explicit clearance requirement is stored');

    $selfUser=$db->fetchOne("SELECT id FROM users WHERE employee_id=? AND is_active=1 LIMIT 1",[$employee['id']]);
    if($selfUser)check(!$model->authorizedApprover((int)$selfUser['id'],(int)$employee['id']),'employee cannot be selected to approve their own clearance');
    else echo "PASS: selected employee has no approver account and cannot self-clear\n";

    try{$model->complete($separationId,(int)$approver['id']);throw new RuntimeException('FAIL: pending mandatory item did not block completion');}
    catch(RuntimeException $e){check(strpos($e->getMessage(),'cannot be completed')!==false,'pending mandatory item blocks final clearance');}

    $db->update('clearances',['status'=>'Cleared','clearance_date'=>date('Y-m-d H:i:s')],'id=?',[(int)$item['id']]);
    $assetId=(int)$db->insert('asset_returns',['separation_id'=>$separationId,'item_name'=>'Temporary test laptop','returned'=>0]);
    try{$model->complete($separationId,(int)$approver['id']);throw new RuntimeException('FAIL: unresolved asset did not block completion');}
    catch(RuntimeException $e){check(strpos($e->getMessage(),'asset accountabilities')!==false,'unreturned asset blocks final clearance even when mandatory items are cleared');}

    $db->update('asset_returns',['returned'=>1,'returned_at'=>date('Y-m-d H:i:s')],'id=?',[$assetId]);
    $completed=$model->complete($separationId,(int)$approver['id']);
    check($completed['status']==='Completed'&&(int)$completed['eligible_for_final_pay']===1&&$completed['final_pay_processing_status']==='Ready for Payroll','resolved clearance and assets allow completion and Payroll handoff');
    try{$model->assertClearanceMutable($separationId);throw new RuntimeException('FAIL: completed final clearance remained mutable');}
    catch(RuntimeException $e){check($e->getMessage()==='Completed final clearance is read-only.','completed final clearance cannot be reopened through clearance or asset updates');}
    check(array_key_exists('responsible_name',$item)&&array_key_exists('clearance_requirement',$item)&&array_key_exists('comments',$item)&&array_key_exists('clearance_date',$item),'employee-visible clearance fields are available from the owned record');
    echo "All exit-clearance accountability scenarios passed.\n";
}finally{
    if($separationId)$db->delete('separations','id=?',[$separationId]);
}

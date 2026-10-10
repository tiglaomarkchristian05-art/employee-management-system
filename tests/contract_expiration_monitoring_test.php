<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/Document.php';

function checkContract($condition,$message){
    if(!$condition)throw new RuntimeException('FAIL: '.$message);
    echo "PASS: {$message}\n";
}

$db=Database::getInstance();
$model=new Document();
$contractIds=[];
try{
    $employee=$db->fetchOne("SELECT id FROM employees WHERE status IN ('Active','Probationary') ORDER BY id LIMIT 1");
    checkContract((bool)$employee,'an active test employee exists');
    $employeeId=(int)$employee['id'];
    checkContract($model->contractWarningDays()===60,'configured 60-day warning period is loaded');

    $contractId=(int)$db->insert('contracts',[
        'employee_id'=>$employeeId,'contract_type'=>'Project-Based','start_date'=>date('Y-m-d'),
        'end_date'=>date('Y-m-d',strtotime('+120 days')),'status'=>'Active','renewal_status'=>'Not Required',
        'approval_status'=>'Approved','remarks'=>'Isolated contract monitoring test'
    ]);
    $contractIds[]=$contractId;
    $model->refreshContractStatuses(60,$contractId);
    $active=$model->getContract($contractId);
    checkContract($active['status']==='Active'&&$active['renewal_status']==='Not Required','active contract remains outside the warning period');
    checkContract($active['contract_type']==='Project-Based','client-specific contract type is preserved');

    $db->update('contracts',['end_date'=>date('Y-m-d',strtotime('+45 days')),'status'=>'Active','renewal_status'=>'Not Required'],'id=?',[$contractId]);
    $model->refreshContractStatuses(60,$contractId);
    $review=$model->getContract($contractId);
    checkContract($review['status']==='Active'&&$review['renewal_status']==='For Review','contract entering the threshold is marked For Review');
    $detail=null;
    foreach($model->getContractsWithDetails($employeeId) as $row)if((int)$row['id']===$contractId)$detail=$row;
    checkContract($detail&&(int)$detail['days_remaining']===45,'exact Days Remaining is calculated');
    checkContract($detail['lifecycle_status']==='Expiring Soon','warning-period contract displays Expiring Soon');

    $db->update('contracts',['end_date'=>date('Y-m-d'),'status'=>'Active','renewal_status'=>'Not Required'],'id=?',[$contractId]);
    $model->refreshContractStatuses(60,$contractId);
    $required=$model->getContract($contractId);
    checkContract($required['status']==='Active'&&$required['renewal_status']==='Renewal Required','contract ending today requires renewal');

    $db->update('contracts',['end_date'=>date('Y-m-d',strtotime('-1 day')),'status'=>'Active','renewal_status'=>'Not Required'],'id=?',[$contractId]);
    $model->refreshContractStatuses(60,$contractId);
    $expired=$model->getContract($contractId);
    checkContract($expired['status']==='Expired'&&$expired['renewal_status']==='Renewal Required','past contract is marked Expired and Renewal Required');

    [$old,$newId]=$model->renewContract($contractId,[
        'contract_type'=>'Project-Based','start_date'=>date('Y-m-d'),'end_date'=>date('Y-m-d',strtotime('+365 days')),
        'status'=>'Active','renewal_status'=>'Not Required','approval_status'=>'Approved','remarks'=>'Test renewal'
    ]);
    $contractIds[]=(int)$newId;
    $oldVersion=$model->getContract($contractId);
    $newVersion=$model->getContract((int)$newId);
    checkContract($oldVersion['status']==='Renewed'&&$oldVersion['renewal_status']==='Renewed','renewed contract is marked Renewed');
    checkContract((int)$newVersion['previous_contract_id']===$contractId&&(int)$newVersion['version_no']===(int)$old['version_no']+1,'renewal preserves and links the previous version');
    checkContract($newVersion['status']==='Active'&&$newVersion['renewal_status']==='Not Required','new contract version starts Active');
    echo "All contract expiration and renewal monitoring scenarios passed.\n";
}finally{
    foreach(array_reverse($contractIds) as $id)$db->delete('contracts','id=?',[$id]);
}

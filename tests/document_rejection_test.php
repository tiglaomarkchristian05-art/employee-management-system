<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/core/AuditLogger.php';
require dirname(__DIR__).'/app/Models/Document.php';
require dirname(__DIR__).'/app/Models/Notification.php';

function check($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}
$db=Database::getInstance();$document=new Document();$notifications=new Notification();$temporaryId=0;
try{
    $source=$db->fetchOne("SELECT * FROM documents WHERE status='Approved' ORDER BY id DESC LIMIT 1");
    check((bool)$source,'approved source document exists');
    $temporaryId=(int)$db->insert('documents',[
        'employee_id'=>(int)$source['employee_id'],'category_id'=>(int)$source['category_id'],'requirement_id'=>null,
        'title'=>'Temporary Rejection Workflow Test','document_number'=>'TEMP-REJECT-'.date('His'),'file_path'=>$source['file_path'],
        'file_size'=>$source['file_size'],'issue_date'=>$source['issue_date'],'expiry_date'=>$source['expiry_date'],
        'status'=>'Submitted','original_name'=>$source['original_name'],'mime_type'=>$source['mime_type'],'version_no'=>1,'submitted_by'=>1
    ]);
    $remarks='Document image is incomplete; upload a complete readable copy.';
    $old=$document->review($temporaryId,'Rejected',$remarks,1);
    $notifications->createForEmployee((int)$old['employee_id'],'Document rejected',$old['title'].' was Rejected. Remarks: '.$remarks,'error','index.php?page=documents','documents',$temporaryId);
    $_SESSION['user_id']=1;$_SESSION['role_name']='Super Admin';$_SESSION['employee_id']=1;
    AuditLogger::log('DOCUMENT_REJECTED','Document Management',"Document ID {$temporaryId} set to Rejected",1,['record_type'=>'document','record_id'=>$temporaryId,'old_value'=>['status'=>$old['status']],'new_value'=>['status'=>'Rejected','remarks'=>$remarks]]);
    $saved=$db->fetchOne('SELECT status,remarks,reviewed_by,reviewed_at FROM documents WHERE id=?',[$temporaryId]);
    check($saved['status']==='Rejected','status persisted as Rejected');
    check($saved['remarks']===$remarks,'rejection remarks persisted');
    check((int)$saved['reviewed_by']===1&&!empty($saved['reviewed_at']),'reviewer and timestamp persisted');
    check((bool)$db->fetchOne("SELECT id FROM notifications WHERE module='documents' AND related_id=? AND title='Document rejected'",[$temporaryId]),'Employee rejection notification created');
    check((bool)$db->fetchOne("SELECT id FROM audit_logs WHERE action='DOCUMENT_REJECTED' AND record_id=?",[$temporaryId]),'DOCUMENT_REJECTED audit entry created');
    echo "Document rejection integration test passed.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}finally{
    if($temporaryId){$db->query("DELETE FROM notifications WHERE module='documents' AND related_id=?",[$temporaryId]);$db->query("DELETE FROM audit_logs WHERE action='DOCUMENT_REJECTED' AND record_id=?",[$temporaryId]);$db->query('DELETE FROM documents WHERE id=?',[$temporaryId]);echo "Temporary rejection test data removed.\n";}
}
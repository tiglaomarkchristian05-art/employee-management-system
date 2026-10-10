<?php
require_once ROOT_PATH.'core/Model.php';
class Separation extends Model {
 public function assignedItems($userId,$separationId=null){
  $sql="SELECT c.*,CONCAT(e.first_name,' ',e.last_name) employee_name,e.employee_code,s.employee_id FROM clearances c JOIN separations s ON s.id=c.separation_id JOIN employees e ON e.id=s.employee_id JOIN users u ON u.id=c.responsible_user_id WHERE c.responsible_user_id=? AND (u.employee_id IS NULL OR u.employee_id<>s.employee_id)";
  $params=[(int)$userId];if($separationId!==null){$sql.=' AND c.separation_id=?';$params[]=(int)$separationId;}return $this->db->fetchAll($sql.' ORDER BY c.id',$params);
 }
 protected $table='separations';
 const ACTIVE=['Submitted','Under Review','Approved','Processing','Clearance Ongoing'];
 public function getSeparationsWithDetails($employeeId=null){$sql="SELECT s.*,e.first_name,e.last_name,e.employee_code,e.hire_date,e.basic_salary,d.name department_name,p.title position_title,(SELECT COUNT(*) FROM clearances c WHERE c.separation_id=s.id) clearance_total,(SELECT COUNT(*) FROM clearances c WHERE c.separation_id=s.id AND c.status='Cleared') clearance_cleared FROM separations s JOIN employees e ON e.id=s.employee_id LEFT JOIN departments d ON d.id=e.department_id LEFT JOIN positions p ON p.id=e.position_id";$params=[];if($employeeId){$sql.=' WHERE s.employee_id=?';$params[]=(int)$employeeId;}return $this->db->fetchAll($sql.' ORDER BY s.id DESC',$params);}
 public function details($id,$employeeId=null){$sql="SELECT s.*,e.first_name,e.last_name,e.employee_code,e.hire_date,e.basic_salary,d.name department_name,p.title position_title FROM separations s JOIN employees e ON e.id=s.employee_id LEFT JOIN departments d ON d.id=e.department_id LEFT JOIN positions p ON p.id=e.position_id WHERE s.id=?";$p=[(int)$id];if($employeeId){$sql.=' AND s.employee_id=?';$p[]=(int)$employeeId;}return $this->db->fetchOne($sql,$p);}
 public function submitRequest($employeeId,$type,$reason,$remarks,$date,$file,$original){$pdo=$this->db->getConnection();$pdo->beginTransaction();try{$active=$this->db->fetchOne("SELECT id FROM separations WHERE employee_id=? AND status IN ('Submitted','Under Review','Approved','Processing','Clearance Ongoing') FOR UPDATE",[$employeeId]);if($active)throw new RuntimeException('You already have an active separation request.');$id=$this->create(['employee_id'=>$employeeId,'separation_type'=>$type,'notice_date'=>date('Y-m-d'),'proposed_last_working_date'=>$date,'effective_date'=>$date,'reason'=>$reason,'resignation_file'=>$file,'resignation_original_name'=>$original,'employee_remarks'=>$remarks?:null,'status'=>'Submitted']);$pdo->commit();return $id;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}}
 public function createClearance($id,array $items=[]){
  $sep=$this->details($id);
  if(!$sep||!in_array($sep['status'],['Approved','Processing'],true))throw new RuntimeException('Separation must be approved before clearance is generated.');
  if(!$items){
   foreach($this->db->fetchAll('SELECT id,name FROM departments ORDER BY name') as $department)$items[]=[
    'department_id'=>(int)$department['id'],'department_name'=>$department['name'],'responsible_user_id'=>null,
    'clearance_requirement'=>$department['name'].' clearance verification','is_mandatory'=>1
   ];
  }
  if(!$items)throw new RuntimeException('Select at least one participating department.');
  $pdo=$this->db->getConnection();$pdo->beginTransaction();
  try{
   $created=0;
   foreach($items as $item){
    $department=$this->db->fetchOne('SELECT id,name FROM departments WHERE id=?',[(int)($item['department_id']??0)]);
    if(!$department)throw new RuntimeException('A selected clearance department is invalid.');
    $responsible=(int)($item['responsible_user_id']??0);
    if(!$this->authorizedApprover($responsible,(int)$sep['employee_id']))throw new RuntimeException('Select an authorized responsible person who is not the employee being cleared.');
    $requirement=trim((string)($item['clearance_requirement']??''));
    if($requirement===''||mb_strlen($requirement)>180)throw new RuntimeException('Each participating department requires a clearance requirement of up to 180 characters.');
    $this->db->insert('clearances',[
     'separation_id'=>(int)$id,'department_id'=>(int)$department['id'],'department_name'=>$department['name'],
     'responsible_user_id'=>$responsible,'clearance_requirement'=>$requirement,
     'is_mandatory'=>!empty($item['is_mandatory'])?1:0,'status'=>'Pending'
    ]);
    $created++;
   }
   $this->update($id,['status'=>'Clearance Ongoing','clearance_created_at'=>date('Y-m-d H:i:s')]);
   $pdo->commit();return $created;
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
 public function getClearanceStatus($id){return $this->db->fetchAll("SELECT c.*,COALESCE(NULLIF(CONCAT(e.first_name,' ',e.last_name),' '),u.username) responsible_name FROM clearances c LEFT JOIN users u ON u.id=c.responsible_user_id LEFT JOIN employees e ON e.id=u.employee_id WHERE c.separation_id=? ORDER BY c.id",[(int)$id]);}
 public function getClearance($id){return $this->db->fetchOne('SELECT c.*,s.employee_id,s.status separation_status FROM clearances c JOIN separations s ON s.id=c.separation_id WHERE c.id=?',[(int)$id]);}
 public function assertClearanceMutable($id){$sep=$this->details((int)$id);if(!$sep)throw new RuntimeException('Separation record not found.');if($sep['status']==='Completed')throw new RuntimeException('Completed final clearance is read-only.');if($sep['status']!=='Clearance Ongoing')throw new RuntimeException('Clearance items may only be updated while clearance is ongoing.');return $sep;}
 public function authorizedApprover($userId,$excludedEmployeeId=0){return $this->db->fetchOne("SELECT u.id,u.employee_id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.is_active=1 AND r.name IN ('HR Manager','HR Officer','Department Head') AND (u.employee_id IS NULL OR u.employee_id<>?)",[(int)$userId,(int)$excludedEmployeeId]);}
 public function getAssetReturns($id){return $this->db->fetchAll('SELECT * FROM asset_returns WHERE separation_id=? ORDER BY id',[(int)$id]);}
 public function getFinalPay($id){return $this->db->fetchOne('SELECT * FROM final_pays WHERE separation_id=?',[(int)$id]);}
 public function getExitInterview($id){return $this->db->fetchOne('SELECT * FROM exit_interviews WHERE separation_id=?',[(int)$id]);}
 public function blockingSummary($id){
  $clearance=$this->db->fetchOne("SELECT COUNT(*) total FROM clearances WHERE separation_id=? AND is_mandatory=1 AND status<>'Cleared'",[(int)$id]);
  $assets=$this->db->fetchOne('SELECT COUNT(*) total FROM asset_returns WHERE separation_id=? AND returned=0',[(int)$id]);
  return ['mandatory_clearance_items'=>(int)($clearance['total']??0),'unresolved_assets'=>(int)($assets['total']??0)];
 }
 public function blockers($id){$summary=$this->blockingSummary($id);return $summary['mandatory_clearance_items']+$summary['unresolved_assets'];}
 public function complete($id,$userId){
  $pdo=$this->db->getConnection();$pdo->beginTransaction();
  try{
   $sep=$this->db->fetchOne('SELECT * FROM separations WHERE id=? FOR UPDATE',[(int)$id]);
   if(!$sep||$sep['status']!=='Clearance Ongoing')throw new RuntimeException('Clearance is not ready for final completion.');
   $blocking=$this->blockingSummary($id);
   if($blocking['mandatory_clearance_items']>0||$blocking['unresolved_assets']>0)throw new RuntimeException('Final clearance cannot be completed while mandatory clearance items or asset accountabilities remain unresolved.');
   $this->update($id,['status'=>'Completed','completed_at'=>date('Y-m-d H:i:s'),'reviewed_by'=>$userId,'eligible_for_final_pay'=>1,'final_pay_processing_status'=>'Ready for Payroll']);
   $pdo->commit();return $this->details($id);
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 }
}

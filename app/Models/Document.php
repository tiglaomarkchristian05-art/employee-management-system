<?php
require_once ROOT_PATH . 'core/Model.php';

class Document extends Model {
    protected $table='documents';
    const STATUSES=['Pending','Submitted','Under Review','Approved','Returned','Rejected','Expired','Renewal Required'];

    public function contractWarningDays() {
        $row=$this->db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key='contract_expiration_warning_days'");
        $days=(int)($row['setting_value']??60);
        return in_array($days,[30,60,90],true)?$days:60;
    }
    public function refreshExpiryStatuses() {
        $this->db->query("UPDATE documents SET status=IF(status='Approved','Expired','Renewal Required') WHERE expiry_date<CURRENT_DATE AND status IN ('Approved','Submitted','Under Review')");
        return $this->refreshContractStatuses();
    }
    public function refreshContractStatuses($warningDays=null,$contractId=null) {
        $warningDays=$warningDays===null?$this->contractWarningDays():(int)$warningDays;
        if(!in_array($warningDays,[30,60,90],true))$warningDays=60;
        $sql="SELECT * FROM contracts WHERE status IN ('Active','Expired')";$params=[];
        if($contractId){$sql.=' AND id=?';$params[]=(int)$contractId;}
        $changes=[];
        foreach($this->db->fetchAll($sql,$params) as $contract){
            $days=$contract['end_date']===null?null:(int)((new DateTime(date('Y-m-d')))->diff(new DateTime($contract['end_date']))->format('%r%a'));
            if($days===null){$status='Active';$renewal='Not Required';}
            elseif($days<0){$status='Expired';$renewal='Renewal Required';}
            elseif($days===0){$status='Active';$renewal='Renewal Required';}
            elseif($days<=$warningDays){$status='Active';$renewal='For Review';}
            else{$status='Active';$renewal='Not Required';}
            if($contract['status']!==$status||$contract['renewal_status']!==$renewal){
                $this->db->update('contracts',['status'=>$status,'renewal_status'=>$renewal],'id=?',[(int)$contract['id']]);
                $changes[]=['id'=>(int)$contract['id'],'employee_id'=>(int)$contract['employee_id'],'contract_type'=>$contract['contract_type'],'end_date'=>$contract['end_date'],'days_remaining'=>$days,'old_status'=>$contract['status'],'new_status'=>$status,'old_renewal_status'=>$contract['renewal_status'],'new_renewal_status'=>$renewal];
            }
        }
        return $changes;
    }
    public function getDocumentsWithDetails($employeeId=null) {
        $sql="SELECT d.*,(SELECT COUNT(*) FROM documents successor WHERE successor.replaces_document_id=d.id) has_newer_version,cat.name category_name,cat.category_group,e.first_name,e.last_name,e.employee_code,r.title requirement_title,
                     COALESCE(NULLIF(CONCAT(re.first_name,' ',re.last_name),' '),reviewer.username) reviewer_name
              FROM documents d JOIN document_categories cat ON cat.id=d.category_id JOIN employees e ON e.id=d.employee_id
              LEFT JOIN document_requirements r ON r.id=d.requirement_id
              LEFT JOIN users reviewer ON reviewer.id=d.reviewed_by LEFT JOIN employees re ON re.id=reviewer.employee_id";
        $params=[];if($employeeId){$sql.=" WHERE d.employee_id=?";$params[]=$employeeId;}
        return $this->db->fetchAll($sql." ORDER BY d.id DESC",$params);
    }
    public function getDocument($id) {
        return $this->db->fetchOne("SELECT d.*,cat.name category_name,e.first_name,e.last_name FROM documents d JOIN document_categories cat ON cat.id=d.category_id JOIN employees e ON e.id=d.employee_id WHERE d.id=?",[$id]);
    }
    public function getRequirements($employeeId=null) {
        $sql="SELECT r.*,c.name category_name,e.first_name,e.last_name,e.employee_code,d.remarks,d.expiry_date,d.issue_date,d.original_name
              FROM document_requirements r JOIN document_categories c ON c.id=r.category_id JOIN employees e ON e.id=r.employee_id
              LEFT JOIN documents d ON d.id=r.current_document_id";
        $params=[];if($employeeId){$sql.=" WHERE r.employee_id=?";$params[]=$employeeId;}
        return $this->db->fetchAll($sql." ORDER BY r.is_active DESC,r.id DESC",$params);
    }
    public function getRequirement($id){return $this->db->fetchOne("SELECT * FROM document_requirements WHERE id=?",[$id]);}
    public function assignRequirement(array $data){return $this->db->insert('document_requirements',$data);}
    public function submit(array $data,$requirementId=null,$replacementId=null) {
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try {
            $previous=null;$version=1;
            if($requirementId){
                $requirement=$this->db->fetchOne("SELECT * FROM document_requirements WHERE id=? FOR UPDATE",[$requirementId]);
                if(!$requirement)throw new RuntimeException('Document requirement not found.');
                if((int)$requirement['employee_id']!==(int)$data['employee_id']||(int)$requirement['category_id']!==(int)$data['category_id'])throw new RuntimeException('Document owner or category does not match the requirement.');
                $previous=$requirement['current_document_id']?$this->find($requirement['current_document_id']):null;
                if($previous&&!in_array($previous['status'],['Returned','Rejected','Expired','Renewal Required'],true))throw new RuntimeException('This requirement already has an active submission.');
                $version=$previous?(int)$previous['version_no']+1:1;
            }
            elseif($replacementId){
                $previous=$this->db->fetchOne('SELECT * FROM documents WHERE id=? FOR UPDATE',[(int)$replacementId]);
                if(!$previous||(int)$previous['employee_id']!==(int)$data['employee_id']||(int)$previous['category_id']!==(int)$data['category_id'])throw new RuntimeException('Document owner or category does not match.');
                if($previous['requirement_id'])throw new RuntimeException('Resubmit through the assigned requirement.');
                if(!in_array($previous['status'],['Returned','Rejected','Expired','Renewal Required'],true))throw new RuntimeException('This document is not open for resubmission.');
                if($this->db->fetchOne('SELECT id FROM documents WHERE replaces_document_id=?',[$previous['id']]))throw new RuntimeException('A newer version already exists.');
                $version=(int)$previous['version_no']+1;
            }
            $data['requirement_id']=$requirementId?:null;$data['version_no']=$version;$data['replaces_document_id']=$previous['id']??null;
            $id=$this->create($data);
            if($previous)$this->db->insert('document_versions',['document_id'=>$id,'version_no'=>$previous['version_no'],'file_path'=>$previous['file_path'],'original_name'=>$previous['original_name'],'file_size'=>$previous['file_size'],'mime_type'=>$previous['mime_type'],'status'=>$previous['status'],'remarks'=>$previous['remarks']]);
            if($requirementId)$this->db->update('document_requirements',['current_document_id'=>$id,'status'=>'Submitted'],"id=?",[$requirementId]);
            $pdo->commit();return $id;
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public function review($id,$status,$remarks,$reviewer) {
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try{$doc=$this->db->fetchOne('SELECT * FROM documents WHERE id=? FOR UPDATE',[$id]);if(!$doc)throw new RuntimeException('Document not found.');
            if($this->db->fetchOne('SELECT id FROM documents WHERE replaces_document_id=?',[$id]))throw new RuntimeException('This historical version is preserved. Review the newest version instead.');
            $this->db->update('documents',['status'=>$status,'remarks'=>$remarks,'reviewed_by'=>$reviewer,'reviewed_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);
            if($doc['requirement_id'])$this->db->update('document_requirements',['status'=>$status],"id=?",[$doc['requirement_id']]);
            $pdo->commit();return $doc;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public function getContractsWithDetails($employeeId=null){
        $sql="SELECT c.*,DATEDIFF(c.end_date,CURRENT_DATE) days_remaining,e.first_name,e.last_name,e.employee_code,d.name department_name FROM contracts c JOIN employees e ON e.id=c.employee_id LEFT JOIN departments d ON d.id=e.department_id";
        $params=[];if($employeeId){$sql.=" WHERE c.employee_id=?";$params[]=$employeeId;}
        $rows=$this->db->fetchAll($sql." ORDER BY c.employee_id,c.version_no DESC,c.id DESC",$params);$warning=$this->contractWarningDays();
        foreach($rows as &$row){$days=$row['days_remaining']===null?null:(int)$row['days_remaining'];$row['lifecycle_status']=$row['status']==='Renewed'?'Renewed':($row['status']==='Expired'?'Expired':($row['renewal_status']==='Renewal Required'?'Renewal Required':($days!==null&&$days>0&&$days<=$warning?'Expiring Soon':'Active')));}unset($row);
        return $rows;
    }
    public function getContract($id){return $this->db->fetchOne("SELECT c.*,e.first_name,e.last_name FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.id=?",[$id]);}
    public function getExpiringContracts($days=null,$employeeId=null){
        $days=$days===null?$this->contractWarningDays():(int)$days;
        $sql="SELECT c.*,e.first_name,e.last_name,e.employee_code FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.status='Active' AND c.end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL ? DAY)";
        $params=[$days];if($employeeId){$sql.=" AND c.employee_id=?";$params[]=$employeeId;}return $this->db->fetchAll($sql." ORDER BY c.end_date",$params);
    }
    public function renewContract($id,array $data){
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try{$old=$this->getContract($id);if(!$old)throw new RuntimeException('Contract not found.');$this->db->update('contracts',['status'=>'Renewed','renewal_status'=>'Renewed','renewed_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);
            $data['employee_id']=$old['employee_id'];$data['previous_contract_id']=$id;$data['version_no']=(int)$old['version_no']+1;$data['renewal_status']='Not Required';$new=$this->db->insert('contracts',$data);$pdo->commit();return [$old,$new];
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}

<?php
require_once ROOT_PATH . 'core/Model.php';

class Document extends Model {
    protected $table='documents';
    const STATUSES=['Pending','Submitted','Under Review','Approved','Returned','Rejected','Expired','Renewal Required'];

    public function refreshExpiryStatuses() {
        $this->db->query("UPDATE documents SET status=IF(status='Approved','Expired','Renewal Required') WHERE expiry_date<CURRENT_DATE AND status IN ('Approved','Submitted','Under Review')");
        $this->db->query("UPDATE contracts SET status='Expired' WHERE end_date<CURRENT_DATE AND status='Active'");
    }
    public function getDocumentsWithDetails($employeeId=null) {
        $sql="SELECT d.*,cat.name category_name,e.first_name,e.last_name,e.employee_code,r.title requirement_title
              FROM documents d JOIN document_categories cat ON cat.id=d.category_id JOIN employees e ON e.id=d.employee_id
              LEFT JOIN document_requirements r ON r.id=d.requirement_id";
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
    public function submit(array $data,$requirementId=null) {
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try {
            $previous=null;$version=1;
            if($requirementId){
                $requirement=$this->db->fetchOne("SELECT * FROM document_requirements WHERE id=? FOR UPDATE",[$requirementId]);
                if(!$requirement)throw new RuntimeException('Document requirement not found.');
                $previous=$requirement['current_document_id']?$this->find($requirement['current_document_id']):null;
                if($previous&&!in_array($previous['status'],['Returned','Rejected','Expired','Renewal Required'],true))throw new RuntimeException('This requirement already has an active submission.');
                $version=$previous?(int)$previous['version_no']+1:1;
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
        try{$doc=$this->getDocument($id);if(!$doc)throw new RuntimeException('Document not found.');
            $this->db->update('documents',['status'=>$status,'remarks'=>$remarks,'reviewed_by'=>$reviewer,'reviewed_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);
            if($doc['requirement_id'])$this->db->update('document_requirements',['status'=>$status],"id=?",[$doc['requirement_id']]);
            $pdo->commit();return $doc;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
    public function getContractsWithDetails($employeeId=null){
        $sql="SELECT c.*,e.first_name,e.last_name,e.employee_code,d.name department_name FROM contracts c JOIN employees e ON e.id=c.employee_id LEFT JOIN departments d ON d.id=e.department_id";
        $params=[];if($employeeId){$sql.=" WHERE c.employee_id=?";$params[]=$employeeId;}
        return $this->db->fetchAll($sql." ORDER BY c.employee_id,c.version_no DESC,c.id DESC",$params);
    }
    public function getContract($id){return $this->db->fetchOne("SELECT c.*,e.first_name,e.last_name FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.id=?",[$id]);}
    public function getExpiringContracts($days=30,$employeeId=null){
        $sql="SELECT c.*,e.first_name,e.last_name,e.employee_code FROM contracts c JOIN employees e ON e.id=c.employee_id WHERE c.status='Active' AND c.end_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL ? DAY)";
        $params=[$days];if($employeeId){$sql.=" AND c.employee_id=?";$params[]=$employeeId;}return $this->db->fetchAll($sql." ORDER BY c.end_date",$params);
    }
    public function renewContract($id,array $data){
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try{$old=$this->getContract($id);if(!$old)throw new RuntimeException('Contract not found.');$this->db->update('contracts',['status'=>'Renewed','renewed_at'=>date('Y-m-d H:i:s')],"id=?",[$id]);
            $data['employee_id']=$old['employee_id'];$data['previous_contract_id']=$id;$data['version_no']=(int)$old['version_no']+1;$new=$this->db->insert('contracts',$data);$pdo->commit();return [$old,$new];
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}

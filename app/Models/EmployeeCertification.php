<?php
require_once ROOT_PATH.'core/Model.php';

class EmployeeCertification extends Model {
    protected $table='employee_certifications';
    public const STATUSES=['Valid','Expiring Soon','Expired','Pending Verification','Revoked'];

    public function warningDays(): int {
        $row=$this->db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key='certification_expiration_warning_days'");
        return max(1,min(365,(int)($row['setting_value']??60)));
    }

    public function refreshStatuses(): array {
        $days=$this->warningDays();$changes=[];
        $rows=$this->db->fetchAll("SELECT * FROM employee_certifications WHERE status NOT IN ('Pending Verification','Revoked')");
        foreach($rows as $row){
            $new='Valid';
            if($row['expiration_date']){$remaining=(int)$this->db->fetchOne('SELECT DATEDIFF(?,CURRENT_DATE) days',[$row['expiration_date']])['days'];$new=$remaining<0?'Expired':($remaining<=$days?'Expiring Soon':'Valid');}
            if($new!==$row['status']){$this->db->update($this->table,['status'=>$new],'id=?',[$row['id']]);$changes[]=$row+['new_status'=>$new];}
        }
        return $changes;
    }

    public function records(?int $employeeId=null): array {
        $sql="SELECT ec.*,DATEDIFF(ec.expiration_date,CURRENT_DATE) days_remaining,CONCAT(e.first_name,' ',e.last_name) employee_name,e.employee_code,c.title training_title,COALESCE(CONCAT(u.username,' / ',COALESCE(CONCAT(ve.first_name,' ',ve.last_name),'System')),'System migration') verifier_name
              FROM employee_certifications ec JOIN employees e ON e.id=ec.employee_id LEFT JOIN training_courses c ON c.id=ec.related_training_id LEFT JOIN users u ON u.id=ec.verified_by LEFT JOIN employees ve ON ve.id=u.employee_id";
        $params=[];if($employeeId){$sql.=' WHERE ec.employee_id=?';$params[]=$employeeId;}
        return $this->db->fetchAll($sql.' ORDER BY COALESCE(ec.expiration_date,\'9999-12-31\'),ec.id DESC',$params);
    }

    public function record(int $id): ?array {$row=$this->db->fetchOne('SELECT * FROM employee_certifications WHERE id=?',[$id]);return $row?:null;}
    public function verifyCode(string $code,?int $employeeId=null): ?array {$sql="SELECT ec.*,CONCAT(e.first_name,' ',e.last_name) employee_name,c.title training_title FROM employee_certifications ec JOIN employees e ON e.id=ec.employee_id LEFT JOIN training_courses c ON c.id=ec.related_training_id WHERE ec.verification_code=?";$params=[$code];if($employeeId){$sql.=' AND ec.employee_id=?';$params[]=$employeeId;}$row=$this->db->fetchOne($sql,$params);return $row?:null;}

    public function syncTrainingCertificate(int $registrationId,?int $verifiedBy=null): ?int {
        $source=$this->db->fetchOne("SELECT r.*,c.title,c.certification_provided,c.certification_validity_months,c.trainer_id,t.name trainer_name FROM training_registrations r JOIN training_courses c ON c.id=r.course_id LEFT JOIN trainers t ON t.id=c.trainer_id WHERE r.id=? AND r.status='Completed' AND r.certificate_file IS NOT NULL",[$registrationId]);
        if(!$source)return null;
        $existing=$this->db->fetchOne('SELECT id FROM employee_certifications WHERE related_training_registration_id=?',[$registrationId]);
        $issue=$source['completed_at']?:date('Y-m-d');$validity=max(0,(int)$source['certification_validity_months']);$expiry=$validity?date('Y-m-d',strtotime($issue.' +'.$validity.' months')):null;
        $data=['employee_id'=>$source['employee_id'],'certification_name'=>$source['certification_provided']?:$source['title'].' Certificate','certification_type'=>'Training Completion','issuing_organization'=>$source['trainer_name']?:'Internal HR','issue_date'=>$issue,'expiration_date'=>$expiry,'status'=>'Valid','supporting_file'=>$source['certificate_file'],'certificate_number'=>$source['certificate_number'],'verification_code'=>$source['certificate_verification_code'],'certificate_version'=>(int)$source['certificate_version'],'related_training_id'=>$source['course_id'],'verified_by'=>$verifiedBy,'verification_date'=>date('Y-m-d H:i:s')];
        if($existing){$this->db->update($this->table,$data,'id=?',[$existing['id']]);return (int)$existing['id'];}
        return (int)$this->db->insert($this->table,$data+['related_training_registration_id'=>$registrationId]);
    }
}

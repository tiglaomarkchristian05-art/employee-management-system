<?php
require_once APP_PATH.'Models/EmployeeCertification.php';
require_once APP_PATH.'Services/CertificatePdfRenderer.php';
require_once APP_PATH.'Services/TrainingCompletionRules.php';

class TrainingCertificateService {
    private Database $db;
    public function __construct(){$this->db=Database::getInstance();}

    public function eligibility(int $registrationId): array {
        $record=$this->record($registrationId);
        if(!$record)return [false,'Training registration not found.',null];
        if($record['status']!=='Completed')return [false,'Registration must be marked Completed.', $record];
        if($failure=TrainingCompletionRules::failure($record))return [false,$failure,$record];
        if(in_array($record['course_status'],['Draft','Cancelled'],true))return [false,'A draft or cancelled training cannot issue certificates.', $record];
        return [true,'Eligible',$record];
    }

    public function generate(int $registrationId,int $issuedBy,bool $regenerate=false,string $reason=''): array {
        [$eligible,$message,$record]=$this->eligibility($registrationId);if(!$eligible)throw new RuntimeException($message);
        $existing=(string)($record['certificate_file']??'');
        if(!$regenerate&&$existing!==''&&$existing!=='generated')return ['generated'=>false,'record'=>$record,'path'=>$existing];
        $version=max(1,(int)$record['certificate_version']+1);
        $number=$record['certificate_number']?:'CERT-'.date('Y').'-'.str_pad((string)$registrationId,6,'0',STR_PAD_LEFT);
        $verification=$record['certificate_verification_code']?:strtoupper(bin2hex(random_bytes(12)));
        $issuedAt=date('Y-m-d H:i:s');$relative='uploads/training/certificates/generated/certificate-'.$registrationId.'-v'.$version.'.pdf';$absolute=ROOT_PATH.'public/'.$relative;$dir=dirname($absolute);
        if(!is_dir($dir)&&!mkdir($dir,0755,true))throw new RuntimeException('Unable to prepare certificate storage.');
        $setting=$this->db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key='company_name'");
        $company=$setting['setting_value']??'ISMERS';
        $pdf=(new CertificatePdfRenderer())->render($record,$number,$verification,$issuedAt,$company);
        if(file_put_contents($absolute,$pdf,LOCK_EX)===false||filesize($absolute)<1000){@unlink($absolute);throw new RuntimeException('PDF certificate generation failed. The completed training record was preserved.');}
        $pdo=$this->db->getConnection();$pdo->beginTransaction();
        try{
            $this->db->update('training_registrations',['certificate_file'=>$relative,'certificate_number'=>$number,'certificate_issued_at'=>$issuedAt,'certificate_verification_code'=>$verification,'certificate_version'=>$version],'id=?',[$registrationId]);
            $this->db->insert('training_certificate_versions',['registration_id'=>$registrationId,'certificate_number'=>$number,'version_no'=>$version,'file_path'=>$relative,'verification_code'=>$verification,'issue_reason'=>$reason?:($regenerate?'Authorized regeneration':'Automatic issue'),'issued_by'=>$issuedBy,'issued_at'=>$issuedAt]);
            (new EmployeeCertification())->syncTrainingCertificate($registrationId,$issuedBy);
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();@unlink($absolute);throw $e;}
        return ['generated'=>true,'record'=>$this->record($registrationId),'path'=>$relative,'number'=>$number,'version'=>$version];
    }

    public function record(int $id): ?array {
        $row=$this->db->fetchOne("SELECT r.*,c.title course_title,c.status course_status,c.start_date,c.end_date,c.duration_hours,c.assessment_required,c.minimum_passing_score,c.minimum_attendance_percentage,c.course_type,c.description,c.training_purpose,c.required_skills,c.certification_provided,c.certification_validity_months,cat.name category_name,t.name trainer_name,e.first_name,e.last_name,e.employee_code
          FROM training_registrations r JOIN training_courses c ON c.id=r.course_id LEFT JOIN training_categories cat ON cat.id=c.category_id LEFT JOIN trainers t ON t.id=c.trainer_id JOIN employees e ON e.id=r.employee_id WHERE r.id=?",[$id]);return $row?:null;
    }

}

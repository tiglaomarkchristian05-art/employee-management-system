-- Structured employee credentials; training-issued certificates remain in training_registrations.
CREATE TABLE IF NOT EXISTS employee_certifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  certification_name VARCHAR(180) NOT NULL,
  certification_type VARCHAR(100) NOT NULL,
  issuing_organization VARCHAR(180) NULL,
  issue_date DATE NULL,
  expiration_date DATE NULL,
  status ENUM('Valid','Expiring Soon','Expired','Pending Verification','Revoked') NOT NULL DEFAULT 'Pending Verification',
  supporting_file VARCHAR(255) NULL,
  related_training_id INT NULL,
  related_training_registration_id INT NULL,
  verified_by INT NULL,
  verification_date DATETIME NULL,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cert_training_registration (related_training_registration_id),
  INDEX idx_cert_employee_status (employee_id,status),
  INDEX idx_cert_expiration (expiration_date,status),
  CONSTRAINT fk_cert_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_cert_training FOREIGN KEY (related_training_id) REFERENCES training_courses(id) ON DELETE SET NULL,
  CONSTRAINT fk_cert_registration FOREIGN KEY (related_training_registration_id) REFERENCES training_registrations(id) ON DELETE SET NULL,
  CONSTRAINT fk_cert_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_cert_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO employee_certifications
 (employee_id,certification_name,certification_type,issuing_organization,issue_date,status,supporting_file,related_training_id,related_training_registration_id,verification_date)
SELECT r.employee_id,COALESCE(NULLIF(c.certification_provided,''),CONCAT(c.title,' Certificate')),'Training Completion',
       COALESCE(t.name,'Internal HR'),COALESCE(r.completed_at,c.end_date),'Valid',r.certificate_file,c.id,r.id,COALESCE(r.updated_at,NOW())
FROM training_registrations r
JOIN training_courses c ON c.id=r.course_id
LEFT JOIN trainers t ON t.id=c.trainer_id
WHERE r.status='Completed' AND r.certificate_file IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM employee_certifications ec WHERE ec.related_training_registration_id=r.id);

INSERT INTO system_settings (setting_key,setting_value,description)
SELECT 'certification_expiration_warning_days','60','Certification expiration warning period in days'
WHERE NOT EXISTS (SELECT 1 FROM system_settings WHERE setting_key='certification_expiration_warning_days');

-- Phase 6: internal government record verification and contribution tracking.
CREATE TABLE IF NOT EXISTS government_records (
 id INT AUTO_INCREMENT PRIMARY KEY,employee_id INT NOT NULL,agency ENUM('SSS','PhilHealth','Pag-IBIG','TIN/BIR') NOT NULL,
 record_number VARCHAR(50) NULL,status ENUM('Missing','Submitted','Pending Verification','Verified','Rejected','Needs Correction') NOT NULL DEFAULT 'Missing',
 supporting_file VARCHAR(255) NULL,original_name VARCHAR(255) NULL,admin_remarks TEXT NULL,submitted_by INT NULL,verified_by INT NULL,
 verified_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_government_employee_agency(employee_id,agency),INDEX idx_government_status_agency(status,agency),
 FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT IGNORE INTO government_records(employee_id,agency,record_number,status)
 SELECT id,'SSS',NULLIF(TRIM(sss_no),''),IF(NULLIF(TRIM(sss_no),'') IS NULL,'Missing','Verified') FROM employees;
INSERT IGNORE INTO government_records(employee_id,agency,record_number,status)
 SELECT id,'PhilHealth',NULLIF(TRIM(philhealth_no),''),IF(NULLIF(TRIM(philhealth_no),'') IS NULL,'Missing','Verified') FROM employees;
INSERT IGNORE INTO government_records(employee_id,agency,record_number,status)
 SELECT id,'Pag-IBIG',NULLIF(TRIM(pagibig_no),''),IF(NULLIF(TRIM(pagibig_no),'') IS NULL,'Missing','Verified') FROM employees;
INSERT IGNORE INTO government_records(employee_id,agency,record_number,status)
 SELECT id,'TIN/BIR',NULLIF(TRIM(tin_no),''),IF(NULLIF(TRIM(tin_no),'') IS NULL,'Missing','Verified') FROM employees;
CREATE TABLE IF NOT EXISTS government_corrections (
 id INT AUTO_INCREMENT PRIMARY KEY,government_record_id INT NOT NULL,employee_id INT NOT NULL,proposed_value VARCHAR(50) NOT NULL,
 reason TEXT NOT NULL,supporting_file VARCHAR(255) NULL,original_name VARCHAR(255) NULL,
 status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',admin_remarks TEXT NULL,reviewed_by INT NULL,reviewed_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_correction_employee_status(employee_id,status),FOREIGN KEY(government_record_id) REFERENCES government_records(id) ON DELETE CASCADE,
 FOREIGN KEY(employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE gov_contributions
 ADD COLUMN IF NOT EXISTS status ENUM('Draft','Posted','Corrected','Archived') NOT NULL DEFAULT 'Posted' AFTER total_statutory,
 ADD COLUMN IF NOT EXISTS admin_remarks TEXT NULL AFTER status,
 ADD COLUMN IF NOT EXISTS supporting_file VARCHAR(255) NULL AFTER admin_remarks,
 ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER supporting_file,
 ADD COLUMN IF NOT EXISTS updated_by INT NULL AFTER created_by,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
 ADD UNIQUE INDEX IF NOT EXISTS uq_contribution_employee_period(employee_id,period_year,period_month);

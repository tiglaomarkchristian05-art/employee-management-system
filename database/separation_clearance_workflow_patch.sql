-- Phase 9: Separation and Exit Clearance workflow (idempotent).
ALTER TABLE separations MODIFY COLUMN separation_type VARCHAR(40) NOT NULL;
UPDATE separations SET separation_type='End of Contract' WHERE separation_type='Contract End';
ALTER TABLE separations MODIFY COLUMN separation_type ENUM('Resignation','End of Contract','Retirement','Termination','Other') NOT NULL;
ALTER TABLE separations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Submitted';
UPDATE separations SET status='Clearance Ongoing' WHERE status='Pending Clearance';
UPDATE separations SET status='Submitted' WHERE status='Initiated';
ALTER TABLE separations MODIFY COLUMN status ENUM('Submitted','Under Review','Approved','Rejected','Processing','Clearance Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Submitted';
ALTER TABLE separations
 ADD COLUMN IF NOT EXISTS proposed_last_working_date DATE NULL AFTER notice_date,
 ADD COLUMN IF NOT EXISTS final_working_date DATE NULL AFTER effective_date,
 ADD COLUMN IF NOT EXISTS resignation_file VARCHAR(255) NULL AFTER reason,
 ADD COLUMN IF NOT EXISTS resignation_original_name VARCHAR(255) NULL AFTER resignation_file,
 ADD COLUMN IF NOT EXISTS employee_remarks TEXT NULL AFTER resignation_original_name,
 ADD COLUMN IF NOT EXISTS admin_remarks TEXT NULL AFTER status,
 ADD COLUMN IF NOT EXISTS reviewed_by INT NULL AFTER admin_remarks,
 ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL AFTER reviewed_by,
 ADD COLUMN IF NOT EXISTS clearance_created_at DATETIME NULL AFTER reviewed_at,
 ADD COLUMN IF NOT EXISTS final_clearance_file VARCHAR(255) NULL AFTER clearance_created_at,
 ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL AFTER final_clearance_file,
 ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL AFTER completed_at,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at,
 ADD INDEX IF NOT EXISTS idx_separation_employee_status(employee_id,status);
UPDATE separations SET proposed_last_working_date=effective_date,final_working_date=effective_date WHERE proposed_last_working_date IS NULL;
ALTER TABLE clearances MODIFY COLUMN department_name VARCHAR(120) NOT NULL;
ALTER TABLE clearances MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending';
UPDATE clearances SET status='With Accountability' WHERE status='Rejected';
ALTER TABLE clearances MODIFY COLUMN status ENUM('Pending','Cleared','With Accountability') NOT NULL DEFAULT 'Pending';
ALTER TABLE clearances
 ADD COLUMN IF NOT EXISTS department_id INT NULL AFTER separation_id,
 ADD COLUMN IF NOT EXISTS is_mandatory TINYINT(1) NOT NULL DEFAULT 1 AFTER department_name,
 ADD COLUMN IF NOT EXISTS accountability_details TEXT NULL AFTER status,
 ADD COLUMN IF NOT EXISTS required_document_file VARCHAR(255) NULL AFTER accountability_details,
 ADD COLUMN IF NOT EXISTS required_document_original_name VARCHAR(255) NULL AFTER required_document_file,
 ADD COLUMN IF NOT EXISTS document_uploaded_at DATETIME NULL AFTER required_document_original_name,
 ADD COLUMN IF NOT EXISTS verified_by_user_id INT NULL AFTER cleared_by,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER comments,
 ADD UNIQUE INDEX IF NOT EXISTS uq_clearance_separation_department(separation_id,department_name);
ALTER TABLE asset_returns
 ADD COLUMN IF NOT EXISTS remarks TEXT NULL AFTER returned,
 ADD COLUMN IF NOT EXISTS returned_at DATETIME NULL AFTER verified_by,
 ADD COLUMN IF NOT EXISTS verified_by_user_id INT NULL AFTER returned_at;
ALTER TABLE exit_interviews
 ADD COLUMN IF NOT EXISTS conducted_at DATETIME NULL AFTER rehire_recommendation,
 ADD COLUMN IF NOT EXISTS recorded_by INT NULL AFTER conducted_at,
 ADD COLUMN IF NOT EXISTS admin_remarks TEXT NULL AFTER recorded_by;

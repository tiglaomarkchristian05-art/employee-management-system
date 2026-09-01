-- Phase 7: Benefits Management workflow (idempotent).
ALTER TABLE benefit_plans
 ADD COLUMN IF NOT EXISTS eligibility_rules TEXT NULL AFTER description,
 ADD COLUMN IF NOT EXISTS required_documents TEXT NULL AFTER eligibility_rules,
 ADD COLUMN IF NOT EXISTS max_amount DECIMAL(12,2) NULL AFTER required_documents,
 ADD COLUMN IF NOT EXISTS application_start DATE NULL AFTER max_amount,
 ADD COLUMN IF NOT EXISTS application_end DATE NULL AFTER application_start,
 ADD COLUMN IF NOT EXISTS minimum_tenure_months INT NOT NULL DEFAULT 0 AFTER application_end,
 ADD COLUMN IF NOT EXISTS eligible_employment_statuses VARCHAR(150) NOT NULL DEFAULT 'Active,Probationary' AFTER minimum_tenure_months,
 ADD COLUMN IF NOT EXISTS eligible_department_ids VARCHAR(255) NULL AFTER eligible_employment_statuses,
 ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER eligible_department_ids,
 ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER is_active,
 ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER created_by,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
UPDATE benefit_plans SET max_amount=coverage_amount WHERE max_amount IS NULL AND coverage_amount>0;
ALTER TABLE benefit_claims MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Submitted';
UPDATE benefit_claims SET status='Submitted' WHERE status='Pending';
UPDATE benefit_claims SET status='Released' WHERE status='Paid';
ALTER TABLE benefit_claims MODIFY COLUMN status ENUM('Submitted','Under Review','Returned','Approved','Rejected','Processing','Released','Completed','Cancelled') NOT NULL DEFAULT 'Submitted';
ALTER TABLE benefit_claims
 ADD COLUMN IF NOT EXISTS application_notes TEXT NULL AFTER receipt_file,
 ADD COLUMN IF NOT EXISTS requirement_file VARCHAR(255) NULL AFTER application_notes,
 ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) NULL AFTER requirement_file,
 ADD COLUMN IF NOT EXISTS admin_remarks TEXT NULL AFTER status,
 ADD COLUMN IF NOT EXISTS reviewed_by INT NULL AFTER admin_remarks,
 ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL AFTER reviewed_by,
 ADD COLUMN IF NOT EXISTS released_at DATETIME NULL AFTER reviewed_at,
 ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL AFTER released_at,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER submitted_at,
 ADD INDEX IF NOT EXISTS idx_benefit_claim_employee_status(employee_id,status),
 ADD INDEX IF NOT EXISTS idx_benefit_claim_plan_status(benefit_id,status);

-- Pre-Oral Defense recommendations: evidence, traceability, and configurable master data.
-- Idempotent so it is safe on container restarts.

ALTER TABLE training_courses
  ADD COLUMN IF NOT EXISTS training_purpose TEXT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS training_need_reason TEXT NULL AFTER training_purpose,
  ADD COLUMN IF NOT EXISTS basis_source VARCHAR(80) NULL AFTER training_need_reason,
  ADD COLUMN IF NOT EXISTS expected_outcome TEXT NULL AFTER basis_source;

INSERT INTO training_categories (name, description)
SELECT
  'Compliance, Data Privacy & Information Security',
  'Mandatory training related to regulatory compliance, data privacy, information security, employee policies, and government requirements.'
WHERE NOT EXISTS (
  SELECT 1 FROM training_categories
  WHERE LOWER(name)=LOWER('Compliance, Data Privacy & Information Security')
);

ALTER TABLE ai_training_recommendations
  ADD COLUMN IF NOT EXISTS data_basis_used TEXT NULL AFTER detected_gap;

ALTER TABLE document_categories
  ADD COLUMN IF NOT EXISTS category_group ENUM('Employment','Pre-Employment','Government','Training','Other') NOT NULL DEFAULT 'Other' AFTER name;

ALTER TABLE contracts
  ADD COLUMN IF NOT EXISTS renewal_status ENUM('Not Required','For Review','Renewal Required','Renewed','Ended') NOT NULL DEFAULT 'Not Required' AFTER status;

ALTER TABLE contracts
  MODIFY COLUMN contract_type VARCHAR(80) NOT NULL DEFAULT 'Employment';

INSERT INTO system_settings (setting_key,setting_value,description)
SELECT 'contract_expiration_warning_days','60','Contract expiration warning period in days (30, 60, or 90)'
WHERE NOT EXISTS (SELECT 1 FROM system_settings WHERE setting_key='contract_expiration_warning_days');

ALTER TABLE benefit_plans
  ADD COLUMN IF NOT EXISTS purpose TEXT NULL AFTER name,
  ADD COLUMN IF NOT EXISTS eligible_position_ids VARCHAR(255) NULL AFTER eligible_department_ids,
  ADD COLUMN IF NOT EXISTS approval_required TINYINT(1) NOT NULL DEFAULT 1 AFTER eligible_position_ids;

ALTER TABLE clearances
  ADD COLUMN IF NOT EXISTS responsible_user_id INT NULL AFTER department_name,
  ADD COLUMN IF NOT EXISTS clearance_requirement VARCHAR(180) NULL AFTER responsible_user_id,
  ADD INDEX IF NOT EXISTS idx_clearance_responsible (responsible_user_id);

ALTER TABLE separations
  ADD COLUMN IF NOT EXISTS eligible_for_final_pay TINYINT(1) NOT NULL DEFAULT 0 AFTER final_clearance_file,
  ADD COLUMN IF NOT EXISTS final_pay_processing_status ENUM('Not Ready','Ready for Payroll','Sent to Payroll','Processing','Completed') NOT NULL DEFAULT 'Not Ready' AFTER eligible_for_final_pay;

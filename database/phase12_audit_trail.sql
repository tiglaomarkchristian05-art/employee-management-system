-- Phase 12: immutable structured audit metadata and search indexes.
ALTER TABLE audit_logs
  ADD COLUMN IF NOT EXISTS role_name VARCHAR(100) NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS employee_id INT NULL AFTER role_name,
  ADD COLUMN IF NOT EXISTS record_type VARCHAR(100) NULL AFTER module,
  ADD COLUMN IF NOT EXISTS record_id INT NULL AFTER record_type,
  ADD COLUMN IF NOT EXISTS old_value LONGTEXT NULL AFTER description,
  ADD COLUMN IF NOT EXISTS new_value LONGTEXT NULL AFTER old_value,
  ADD INDEX IF NOT EXISTS idx_audit_created (created_at,id),
  ADD INDEX IF NOT EXISTS idx_audit_module_action (module,action),
  ADD INDEX IF NOT EXISTS idx_audit_actor (user_id,role_name);

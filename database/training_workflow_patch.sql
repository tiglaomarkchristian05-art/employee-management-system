-- Phase 4: complete Training and Development workflow.
-- This patch is intentionally idempotent for container restarts.

ALTER TABLE training_courses
  ADD COLUMN IF NOT EXISTS status ENUM('Draft','Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled' AFTER is_active,
  ADD COLUMN IF NOT EXISTS capacity INT NOT NULL DEFAULT 30 AFTER status,
  ADD COLUMN IF NOT EXISTS requirements TEXT NULL AFTER capacity,
  ADD COLUMN IF NOT EXISTS material_file VARCHAR(255) NULL AFTER requirements,
  ADD COLUMN IF NOT EXISTS created_by INT NULL AFTER material_file,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE training_registrations
  ADD COLUMN IF NOT EXISTS requirements_file VARCHAR(255) NULL AFTER certificate_file,
  ADD COLUMN IF NOT EXISTS assessment_result DECIMAL(5,2) NULL AFTER quiz_score,
  ADD COLUMN IF NOT EXISTS result_notes TEXT NULL AFTER assessment_result,
  ADD COLUMN IF NOT EXISTS assigned_by INT NULL AFTER result_notes,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER evaluation_rating;

ALTER TABLE training_registrations MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Applied';
UPDATE training_registrations SET status='Applied' WHERE status='Pending';
UPDATE training_registrations SET status='Confirmed' WHERE status='Approved';
UPDATE training_registrations SET status='Cancelled' WHERE status='Rejected';
ALTER TABLE training_registrations MODIFY COLUMN status ENUM('Assigned','Applied','Confirmed','Attended','Absent','Completed','Failed','Cancelled') NOT NULL DEFAULT 'Applied';
ALTER TABLE training_registrations ADD UNIQUE INDEX IF NOT EXISTS uq_training_employee (course_id,employee_id);

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(50) NOT NULL DEFAULT 'info',
  link VARCHAR(255) NULL,
  module VARCHAR(50) NOT NULL DEFAULT 'system',
  related_id INT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_notifications_user_created (user_id,created_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS module VARCHAR(50) NOT NULL DEFAULT 'system' AFTER link,
  ADD COLUMN IF NOT EXISTS related_id INT NULL AFTER module,
  ADD INDEX IF NOT EXISTS idx_notifications_recipient_read (user_id,is_read,id);

-- Phase 15: explainable AI-based training recommendations and training-needs analysis.
ALTER TABLE training_courses
  ADD COLUMN IF NOT EXISTS target_department_id INT NULL AFTER requirements,
  ADD COLUMN IF NOT EXISTS target_position_id INT NULL AFTER target_department_id,
  ADD COLUMN IF NOT EXISTS required_skills TEXT NULL AFTER target_position_id,
  ADD COLUMN IF NOT EXISTS prerequisite_course_id INT NULL AFTER required_skills,
  ADD COLUMN IF NOT EXISTS difficulty_level ENUM('Beginner','Intermediate','Advanced') NOT NULL DEFAULT 'Intermediate' AFTER prerequisite_course_id,
  ADD COLUMN IF NOT EXISTS certification_provided VARCHAR(150) NULL AFTER difficulty_level,
  ADD COLUMN IF NOT EXISTS retraining_months INT NOT NULL DEFAULT 0 AFTER certification_provided,
  ADD INDEX IF NOT EXISTS idx_training_target_department (target_department_id),
  ADD INDEX IF NOT EXISTS idx_training_target_position (target_position_id),
  ADD INDEX IF NOT EXISTS idx_training_prerequisite (prerequisite_course_id);

ALTER TABLE training_registrations
  ADD COLUMN IF NOT EXISTS completed_at DATE NULL AFTER result_notes,
  ADD INDEX IF NOT EXISTS idx_training_history (employee_id,status,completed_at);

UPDATE training_registrations r
JOIN training_courses c ON c.id=r.course_id
SET r.completed_at=COALESCE(c.end_date,DATE(r.updated_at))
WHERE r.status='Completed' AND r.completed_at IS NULL;

CREATE TABLE IF NOT EXISTS ai_training_recommendations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  training_id INT NOT NULL,
  recommendation_score DECIMAL(5,2) NOT NULL,
  priority ENUM('High','Medium','Low') NOT NULL,
  reason TEXT NOT NULL,
  detected_gap VARCHAR(255) NOT NULL,
  score_breakdown JSON NULL,
  algorithm_version VARCHAR(50) NOT NULL DEFAULT 'content_similarity_v1',
  status ENUM('Pending Review','Accepted','Dismissed','Assigned','Expired') NOT NULL DEFAULT 'Pending Review',
  generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  dismissed_reason TEXT NULL,
  assigned_registration_id INT NULL,
  UNIQUE KEY uq_ai_employee_training (employee_id,training_id),
  INDEX idx_ai_priority_status (priority,status),
  INDEX idx_ai_generated (generated_at,id),
  CONSTRAINT fk_ai_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_training FOREIGN KEY (training_id) REFERENCES training_courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_ai_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ai_registration FOREIGN KEY (assigned_registration_id) REFERENCES training_registrations(id) ON DELETE SET NULL
) ENGINE=InnoDB;
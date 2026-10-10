-- Existing courses retain assessment-required, passing-score 75 and attendance > 0.
ALTER TABLE training_courses
 ADD COLUMN IF NOT EXISTS assessment_required TINYINT(1) NOT NULL DEFAULT 1,
 ADD COLUMN IF NOT EXISTS minimum_passing_score DECIMAL(5,2) NOT NULL DEFAULT 75,
 ADD COLUMN IF NOT EXISTS minimum_attendance_percentage DECIMAL(5,2) NULL DEFAULT NULL;

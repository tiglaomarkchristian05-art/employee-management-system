ALTER TABLE training_courses
  ADD COLUMN IF NOT EXISTS certification_validity_months INT NOT NULL DEFAULT 0 AFTER certification_provided;

ALTER TABLE training_registrations
  ADD COLUMN IF NOT EXISTS certificate_number VARCHAR(40) NULL AFTER certificate_file,
  ADD COLUMN IF NOT EXISTS certificate_issued_at DATETIME NULL AFTER certificate_number,
  ADD COLUMN IF NOT EXISTS certificate_verification_code VARCHAR(64) NULL AFTER certificate_issued_at,
  ADD COLUMN IF NOT EXISTS certificate_version INT NOT NULL DEFAULT 0 AFTER certificate_verification_code,
  ADD UNIQUE INDEX IF NOT EXISTS uq_training_certificate_number (certificate_number),
  ADD UNIQUE INDEX IF NOT EXISTS uq_training_certificate_verification (certificate_verification_code);

ALTER TABLE employee_certifications
  ADD COLUMN IF NOT EXISTS certificate_number VARCHAR(40) NULL AFTER supporting_file,
  ADD COLUMN IF NOT EXISTS verification_code VARCHAR(64) NULL AFTER certificate_number,
  ADD COLUMN IF NOT EXISTS certificate_version INT NOT NULL DEFAULT 0 AFTER verification_code,
  ADD UNIQUE INDEX IF NOT EXISTS uq_registry_certificate_number (certificate_number);

CREATE TABLE IF NOT EXISTS training_certificate_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  certificate_number VARCHAR(40) NOT NULL,
  version_no INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  verification_code VARCHAR(64) NOT NULL,
  issue_reason VARCHAR(255) NULL,
  issued_by INT NULL,
  issued_at DATETIME NOT NULL,
  UNIQUE KEY uq_certificate_version (registration_id,version_no),
  CONSTRAINT fk_certificate_version_registration FOREIGN KEY (registration_id) REFERENCES training_registrations(id) ON DELETE CASCADE,
  CONSTRAINT fk_certificate_version_user FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

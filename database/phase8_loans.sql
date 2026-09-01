-- Phase 8: Loans Management workflow (idempotent).
CREATE TABLE IF NOT EXISTS loan_programs (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL,
 description TEXT NULL, eligibility_rules TEXT NULL, required_documents TEXT NULL,
 maximum_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
 minimum_term_months INT NOT NULL DEFAULT 1, maximum_term_months INT NOT NULL DEFAULT 36,
 interest_rate DECIMAL(5,2) NOT NULL DEFAULT 0, minimum_tenure_months INT NOT NULL DEFAULT 0,
 eligible_employment_statuses VARCHAR(150) NOT NULL DEFAULT 'Active,Probationary',
 eligible_department_ids VARCHAR(255) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_loan_program_name(name)
) ENGINE=InnoDB;
INSERT IGNORE INTO loan_programs(name,description,maximum_amount,minimum_term_months,maximum_term_months,interest_rate) VALUES
('Company Emergency Loan','Short-term assistance for qualified employees',50000,3,24,2.00),
('Company Salary Loan','Salary-based company financing',100000,6,36,2.00),
('Pag-IBIG Multi-Purpose Loan','Internally tracked Pag-IBIG loan application',100000,6,36,0.00),
('SSS Salary Loan','Internally tracked SSS salary loan application',100000,6,24,0.00);
ALTER TABLE loans MODIFY COLUMN loan_type VARCHAR(120) NOT NULL;
ALTER TABLE loans MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Submitted';
UPDATE loans SET status='Submitted' WHERE status='Pending';
UPDATE loans SET status='Paid' WHERE status='Fully Paid';
ALTER TABLE loans MODIFY COLUMN status ENUM('Submitted','Under Review','Returned','Approved','Rejected','Released','Active','Paid','Cancelled') NOT NULL DEFAULT 'Submitted';
ALTER TABLE loans
 ADD COLUMN IF NOT EXISTS loan_program_id INT NULL AFTER employee_id,
 ADD COLUMN IF NOT EXISTS requested_amount DECIMAL(12,2) NULL AFTER loan_type,
 ADD COLUMN IF NOT EXISTS approved_amount DECIMAL(12,2) NULL AFTER requested_amount,
 ADD COLUMN IF NOT EXISTS application_notes TEXT NULL AFTER approved_amount,
 ADD COLUMN IF NOT EXISTS requirement_file VARCHAR(255) NULL AFTER application_notes,
 ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) NULL AFTER requirement_file,
 ADD COLUMN IF NOT EXISTS admin_remarks TEXT NULL AFTER balance_remaining,
 ADD COLUMN IF NOT EXISTS reviewed_by INT NULL AFTER admin_remarks,
 ADD COLUMN IF NOT EXISTS reviewed_at DATETIME NULL AFTER reviewed_by,
 ADD COLUMN IF NOT EXISTS release_date DATE NULL AFTER reviewed_at,
 ADD COLUMN IF NOT EXISTS released_by INT NULL AFTER release_date,
 ADD COLUMN IF NOT EXISTS paid_at DATETIME NULL AFTER released_by,
 ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER requested_at,
 ADD INDEX IF NOT EXISTS idx_loans_employee_status(employee_id,status),
 ADD INDEX IF NOT EXISTS idx_loans_program_status(loan_program_id,status);
UPDATE loans SET requested_amount=principal_amount WHERE requested_amount IS NULL;
UPDATE loans SET approved_amount=principal_amount WHERE approved_amount IS NULL AND status IN ('Approved','Released','Active','Paid');
UPDATE loans l JOIN loan_programs p ON p.name=CASE l.loan_type WHEN 'Emergency' THEN 'Company Emergency Loan' WHEN 'Salary' THEN 'Company Salary Loan' WHEN 'Pag-IBIG' THEN 'Pag-IBIG Multi-Purpose Loan' WHEN 'SSS' THEN 'SSS Salary Loan' ELSE l.loan_type END SET l.loan_program_id=p.id,l.loan_type=p.name WHERE l.loan_program_id IS NULL;
CREATE TABLE IF NOT EXISTS loan_payment_schedules (
 id INT AUTO_INCREMENT PRIMARY KEY, loan_id INT NOT NULL, installment_no INT NOT NULL,
 due_date DATE NOT NULL, amount_due DECIMAL(12,2) NOT NULL, amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
 status ENUM('Pending','Partial','Paid','Overdue') NOT NULL DEFAULT 'Pending', paid_at DATETIME NULL,
 UNIQUE KEY uq_loan_installment(loan_id,installment_no), FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE
) ENGINE=InnoDB;
ALTER TABLE loan_payments
 ADD COLUMN IF NOT EXISTS schedule_id INT NULL AFTER loan_id,
 ADD COLUMN IF NOT EXISTS recorded_by INT NULL AFTER reference_no,
 ADD COLUMN IF NOT EXISTS remarks TEXT NULL AFTER recorded_by,
 ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER remarks,
 ADD INDEX IF NOT EXISTS idx_loan_payment_loan_date(loan_id,payment_date);

-- ApexHR Enterprise HRMS - Complete Database
-- Import this single file into MySQL/MariaDB or phpMyAdmin.
-- Includes the core schema, sample data, allowances, and reimbursements.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ApexHR Enterprise HRMS MySQL Database Schema
-- Version 2.5.0
-- 3NF Normalized Architecture

CREATE DATABASE IF NOT EXISTS `apex_hrms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `apex_hrms`;

-- 1. Roles & Permissions Table
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `permissions` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Branches Table
CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `contact_number` VARCHAR(50) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Departments Table
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `manager_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Positions Table
CREATE TABLE IF NOT EXISTS `positions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(100) NOT NULL,
  `department_id` INT NOT NULL,
  `salary_grade` VARCHAR(20) DEFAULT 'Grade 1',
  `min_salary` DECIMAL(12,2) DEFAULT 0.00,
  `max_salary` DECIMAL(12,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Employees Table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_code` VARCHAR(30) NOT NULL UNIQUE,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NULL,
  `gender` ENUM('Male', 'Female', 'Other') DEFAULT 'Male',
  `dob` DATE NULL,
  `hire_date` DATE NOT NULL,
  `department_id` INT NOT NULL,
  `position_id` INT NOT NULL,
  `branch_id` INT NOT NULL,
  `status` ENUM('Active', 'Probationary', 'Resigned', 'Terminated', 'Retired') DEFAULT 'Active',
  `basic_salary` DECIMAL(12,2) NOT NULL DEFAULT 25000.00,
  `sss_no` VARCHAR(30) NULL,
  `philhealth_no` VARCHAR(30) NULL,
  `pagibig_no` VARCHAR(30) NULL,
  `tin_no` VARCHAR(30) NULL,
  `photo` VARCHAR(255) DEFAULT 'default.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`),
  FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`),
  FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`)
) ENGINE=InnoDB;

-- 6. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `employee_id` INT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Subsystem 1: Training & Development (LMS)
CREATE TABLE IF NOT EXISTS `training_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `trainers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NULL,
  `specialization` VARCHAR(150) NULL,
  `organization` VARCHAR(150) DEFAULT 'Internal HR'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `training_courses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `course_type` ENUM('Internal', 'External', 'Online', 'Mandatory') DEFAULT 'Internal',
  `duration_hours` INT DEFAULT 8,
  `budget` DECIMAL(10,2) DEFAULT 0.00,
  `venue` VARCHAR(150) DEFAULT 'Main Conference Room',
  `trainer_id` INT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `training_categories`(`id`),
  FOREIGN KEY (`trainer_id`) REFERENCES `trainers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `training_registrations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
  `attendance_percentage` INT DEFAULT 0,
  `quiz_score` INT DEFAULT 0,
  `certificate_file` VARCHAR(255) NULL,
  `evaluation_feedback` TEXT NULL,
  `evaluation_rating` INT DEFAULT 5,
  FOREIGN KEY (`course_id`) REFERENCES `training_courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `quiz_questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT NOT NULL,
  `question` TEXT NOT NULL,
  `option_a` VARCHAR(255) NOT NULL,
  `option_b` VARCHAR(255) NOT NULL,
  `option_c` VARCHAR(255) NOT NULL,
  `option_d` VARCHAR(255) NOT NULL,
  `correct_option` CHAR(1) NOT NULL DEFAULT 'A',
  FOREIGN KEY (`course_id`) REFERENCES `training_courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `skills_matrix` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `skill_name` VARCHAR(100) NOT NULL,
  `proficiency_level` ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Intermediate',
  `target_level` ENUM('Beginner', 'Intermediate', 'Advanced', 'Expert') DEFAULT 'Advanced',
  `verified_by` VARCHAR(100) DEFAULT 'HR Audit',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subsystem 2: Document & Contract Management
CREATE TABLE IF NOT EXISTS `document_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `is_required` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `document_number` VARCHAR(50) NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` VARCHAR(50) NULL,
  `expiry_date` DATE NULL,
  `qr_code` VARCHAR(255) NULL,
  `barcode` VARCHAR(100) NULL,
  `status` ENUM('Pending', 'Verified', 'Expired', 'Rejected') DEFAULT 'Pending',
  `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `document_categories`(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `contracts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `contract_type` ENUM('Employment', 'Probation', 'Regularization', 'Consultancy', 'Internship') DEFAULT 'Employment',
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `status` ENUM('Active', 'Renewed', 'Expired', 'Terminated') DEFAULT 'Active',
  `digital_signature` TEXT NULL,
  `approval_status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
  `document_file` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subsystem 3: Government Contribution & Compliance (PH)
CREATE TABLE IF NOT EXISTS `gov_contributions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `period_month` INT NOT NULL,
  `period_year` INT NOT NULL,
  `gross_salary` DECIMAL(12,2) NOT NULL,
  `sss_employee` DECIMAL(10,2) DEFAULT 0.00,
  `sss_employer` DECIMAL(10,2) DEFAULT 0.00,
  `philhealth_employee` DECIMAL(10,2) DEFAULT 0.00,
  `philhealth_employer` DECIMAL(10,2) DEFAULT 0.00,
  `pagibig_employee` DECIMAL(10,2) DEFAULT 0.00,
  `pagibig_employer` DECIMAL(10,2) DEFAULT 0.00,
  `bir_tax_withheld` DECIMAL(10,2) DEFAULT 0.00,
  `total_statutory` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `gov_deadlines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `agency_name` ENUM('SSS', 'PhilHealth', 'Pag-IBIG', 'BIR') NOT NULL,
  `form_type` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Upcoming', 'Submitted', 'Overdue') DEFAULT 'Upcoming'
) ENGINE=InnoDB;

-- Subsystem 4: Benefits & Loans Management
CREATE TABLE IF NOT EXISTS `benefit_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('Health', 'Allowance', 'Incentive', 'Bonus') DEFAULT 'Health',
  `description` TEXT NULL,
  `coverage_amount` DECIMAL(12,2) DEFAULT 0.00,
  `monthly_cost` DECIMAL(10,2) DEFAULT 0.00,
  `employer_share` DECIMAL(10,2) DEFAULT 0.00
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `employee_benefits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `benefit_id` INT NOT NULL,
  `enrollment_date` DATE NOT NULL,
  `status` ENUM('Active', 'Cancelled', 'Pending') DEFAULT 'Active',
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`benefit_id`) REFERENCES `benefit_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `benefit_claims` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `benefit_id` INT NOT NULL,
  `claim_type` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `receipt_number` VARCHAR(50) NULL,
  `receipt_file` VARCHAR(255) NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Paid') DEFAULT 'Pending',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`benefit_id`) REFERENCES `benefit_plans`(`id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `loans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `loan_type` ENUM('Company', 'Salary', 'Emergency', 'Pag-IBIG', 'SSS') NOT NULL,
  `principal_amount` DECIMAL(12,2) NOT NULL,
  `interest_rate` DECIMAL(5,2) DEFAULT 0.00,
  `term_months` INT NOT NULL DEFAULT 12,
  `monthly_deduction` DECIMAL(10,2) NOT NULL,
  `total_payable` DECIMAL(12,2) NOT NULL,
  `balance_remaining` DECIMAL(12,2) NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Active', 'Fully Paid', 'Rejected') DEFAULT 'Pending',
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `loan_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `loan_id` INT NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Payroll Deduction',
  `reference_no` VARCHAR(100) NULL,
  FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Subsystem 5: Separation & Exit Clearance
CREATE TABLE IF NOT EXISTS `separations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `separation_type` ENUM('Resignation', 'Retirement', 'Termination', 'Contract End') NOT NULL,
  `notice_date` DATE NOT NULL,
  `effective_date` DATE NOT NULL,
  `reason` TEXT NULL,
  `status` ENUM('Initiated', 'Pending Clearance', 'Completed', 'Cancelled') DEFAULT 'Initiated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `exit_interviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `separation_id` INT NOT NULL UNIQUE,
  `reason_category` VARCHAR(100) NOT NULL,
  `feedback` TEXT NULL,
  `rehire_recommendation` ENUM('Yes', 'No', 'Conditional') DEFAULT 'Yes',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`separation_id`) REFERENCES `separations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `clearances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `separation_id` INT NOT NULL,
  `department_name` ENUM('HR', 'Finance', 'IT', 'Security', 'Manager') NOT NULL,
  `status` ENUM('Pending', 'Cleared', 'Rejected') DEFAULT 'Pending',
  `cleared_by` VARCHAR(100) NULL,
  `clearance_date` DATETIME NULL,
  `comments` TEXT NULL,
  FOREIGN KEY (`separation_id`) REFERENCES `separations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `asset_returns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `separation_id` INT NOT NULL,
  `item_name` VARCHAR(100) NOT NULL,
  `serial_no` VARCHAR(100) NULL,
  `condition_status` VARCHAR(50) DEFAULT 'Good',
  `returned` TINYINT(1) DEFAULT 0,
  `verified_by` VARCHAR(100) NULL,
  FOREIGN KEY (`separation_id`) REFERENCES `separations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `final_pays` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `separation_id` INT NOT NULL UNIQUE,
  `basic_pay_due` DECIMAL(10,2) DEFAULT 0.00,
  `unused_leave_encashment` DECIMAL(10,2) DEFAULT 0.00,
  `thirteenth_month_prorated` DECIMAL(10,2) DEFAULT 0.00,
  `loan_deductions` DECIMAL(10,2) DEFAULT 0.00,
  `tax_adjustment` DECIMAL(10,2) DEFAULT 0.00,
  `net_final_pay` DECIMAL(12,2) DEFAULT 0.00,
  `status` ENUM('Draft', 'Approved', 'Paid') DEFAULT 'Draft',
  `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`separation_id`) REFERENCES `separations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Audit Trail & Settings
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `module` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` TEXT NULL,
  `description` VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Moses Group of Companies - Overseas Manpower & Recruitment Agency Seed Data

USE `apex_hrms`;

-- 1. Roles
INSERT INTO `roles` (`id`, `name`, `description`, `permissions`) VALUES
(1, 'Super Admin', 'Full agency management, system settings, and global deployment control', '["all"]'),
(2, 'HR Manager', 'Recruitment oversight, documentation, DMW compliance, and flight scheduling', '["hr_all", "view_reports", "approve_requests"]'),
(3, 'Department Head', 'Departmental sign-offs, trade testing, and principal liaison', '["dept_view", "approve_dept"]'),
(4, 'Employee', 'Candidate self-service portal, view OEC/Visa status, submit claims', '["self_service"]');

-- 2. Branches
INSERT INTO `branches` (`id`, `name`, `location`, `contact_number`) VALUES
(1, 'Headquarters - BGC', 'Taguig City, Metro Manila, Philippines', '+63 2 8888 1000'),
(2, 'Manpower Testing Hub - Pasig', 'Ortigas Center, Pasig City', '+63 2 8888 2000'),
(3, 'Cebu Overseas Branch', 'Cebu IT Park, Cebu City', '+63 32 400 3000');

-- 3. Departments
INSERT INTO `departments` (`id`, `code`, `name`, `description`, `manager_id`) VALUES
(1, 'REC', 'Recruitment & Candidate Sourcing', 'Candidate sourcing, trade testing, and interview screening', 1),
(2, 'DOC', 'Documentation, Visa & OEC', 'DMW/POEA e-reg, passport verification, embassy visa stamping', 2),
(3, 'DEP', 'Deployment & Flight Operations', 'Flight booking, POLO endorsement, and airport assistance', 3),
(4, 'FIN', 'Finance & Overseas Accounts', 'Placement loans, statutory reporting, and principal billing', 4);

-- 4. Overseas Recruitment Positions
INSERT INTO `positions` (`id`, `title`, `department_id`, `salary_grade`, `min_salary`, `max_salary`) VALUES
(1, 'VP of Overseas Recruitment', 1, 'Grade 10', 90000.00, 150000.00),
(2, 'Documentation & Visa Officer', 2, 'Grade 7', 45000.00, 70000.00),
(3, 'Senior Overseas Flight Coordinator', 3, 'Grade 8', 65000.00, 95000.00),
(4, 'Registered Overseas Nurse (KSA/UAE)', 1, 'Grade 6', 45000.00, 80000.00),
(5, 'SMAW/GTAW Certified Pipe Welder', 1, 'Grade 6', 40000.00, 70000.00),
(6, 'Certified Caregiver (Japan/Canada)', 1, 'Grade 7', 42000.00, 75000.00);

-- 5. Overseas Candidates & Employees
INSERT INTO `employees` (`id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `gender`, `dob`, `hire_date`, `department_id`, `position_id`, `branch_id`, `status`, `basic_salary`, `sss_no`, `philhealth_no`, `pagibig_no`, `tin_no`, `photo`) VALUES
(1, 'OFW-2026-001', 'Alexander', 'Pierce', 'alex.pierce@mosesgroup.ph', '09171234567', 'Male', '1988-04-12', '2020-01-15', 1, 1, 1, 'Active', 110000.00, '34-1234567-8', '12-345678901-2', '1210-9876-5432', '234-567-890-000', 'avatar1.png'),
(2, 'OFW-2026-002', 'Sarah', 'Jenkins', 'sarah.jenkins@mosesgroup.ph', '09189876543', 'Female', '1992-08-23', '2021-03-10', 2, 2, 1, 'Active', 55000.00, '34-8765432-1', '12-987654321-0', '1210-1234-5678', '987-654-321-000', 'avatar2.png'),
(3, 'OFW-2026-003', 'Michael', 'Chen', 'michael.chen@mosesgroup.ph', '09193334444', 'Male', '1990-11-05', '2019-06-01', 3, 3, 2, 'Active', 75000.00, '34-5555666-7', '12-555566667-8', '1210-5555-6666', '555-666-777-000', 'avatar3.png'),
(4, 'OFW-2026-004', 'Maria', 'Santos', 'maria.santos@mosesgroup.ph', '09207778888', 'Female', '1995-02-14', '2022-09-15', 1, 4, 2, 'Active', 65000.00, '34-7778889-0', '12-777888990-1', '1210-7777-8888', '777-888-999-000', 'avatar4.png'),
(5, 'OFW-2026-005', 'Juan', 'Dela Cruz', 'juan.delacruz@mosesgroup.ph', '09221112222', 'Male', '1993-07-30', '2023-01-05', 1, 5, 1, 'Active', 48000.00, '34-1112223-4', '12-111222333-4', '1210-1111-2222', '111-222-333-000', 'avatar5.png'),
(6, 'OFW-2026-006', 'Clarissa', 'Reyes', 'clarissa.reyes@mosesgroup.ph', '09259990000', 'Female', '1997-10-18', '2024-02-01', 1, 6, 3, 'Resigned', 45000.00, '34-9990001-2', '12-999000111-2', '1210-9999-0000', '999-000-111-000', 'avatar6.png');

-- 6. Users (Default Logins: admin/admin123, hr_manager/user123, employee/user123)
INSERT INTO `users` (`id`, `username`, `password`, `role_id`, `employee_id`, `is_active`) VALUES
(1, 'admin', '$2y$12$WVb0QukCvnPz9uw7uCZZZ.UPgxzJY.S.yt3IT/SEbZkZBbUmA0sNe', 1, 1, 1),
(2, 'hr_manager', '$2y$12$JmEJ5bNcFcqp5CI.nO1Bue5I/AMrMDo9zitKpGxqmJ87SMdqpEJh6', 2, 2, 1),
(3, 'mchen', '$2y$12$JmEJ5bNcFcqp5CI.nO1Bue5I/AMrMDo9zitKpGxqmJ87SMdqpEJh6', 3, 3, 1),
(4, 'employee', '$2y$12$JmEJ5bNcFcqp5CI.nO1Bue5I/AMrMDo9zitKpGxqmJ87SMdqpEJh6', 4, 4, 1);

-- 7. Overseas Training Categories & Trainers
INSERT INTO `training_categories` (`id`, `name`, `description`) VALUES
(1, 'Pre-Departure Orientation (PDOS)', 'Mandatory DMW/OWWA culture, labor laws, and emergency protocols'),
(2, 'Overseas Healthcare & Patient Care', 'Clinical nursing procedures, hospital safety, and patient ethics'),
(3, 'Foreign Language & Cultural Adaptation', 'Japanese N5/N4, Basic Conversational Arabic, and Western ethics'),
(4, 'Skilled Trade & Safety Certifications', 'SMAW/GTAW welding testing, heavy equipment safety, and construction compliance');

INSERT INTO `trainers` (`id`, `name`, `email`, `specialization`, `organization`) VALUES
(1, 'Dr. Aris Thorne', 'athorne@globalhealth.org', 'International Nursing & Healthcare Standards', 'Global Health Institute'),
(2, 'Engr. Kenneth Sy', 'ksy@tradetest.ph', 'ISO SMAW Welding & Industrial Safety', 'Technical Trade Testing Corp'),
(3, 'Atty. Patricia Luna', 'pluna@dmwlegal.ph', 'DMW/POEA Rules & OWWA Overseas OFW Rights', 'Overseas Legal Advocates');

-- 8. Overseas Training Courses
INSERT INTO `training_courses` (`id`, `category_id`, `title`, `description`, `course_type`, `duration_hours`, `budget`, `venue`, `trainer_id`, `start_date`, `end_date`, `is_active`) VALUES
(1, 1, 'Mandatory Pre-Departure Orientation Seminar (PDOS)', 'Comprehensive DMW orientation on country laws, OWWA benefits, emergency hotlines, and culture.', 'Mandatory', 8, 25000.00, 'Main Auditorium / Overseas Flight Center', 3, '2026-08-15', '2026-08-16', 1),
(2, 3, 'Japanese Language & Cultural Adaptation (N5 Level)', 'Basic Japanese greetings, workplace etiquette, healthcare terms, and cultural integration.', 'Online', 30, 45000.00, 'Language Training Room A', 1, '2026-08-20', '2026-08-25', 1),
(3, 4, 'SMAW 6G Pipe Welding Trade Certification', 'Advanced pipe welding testing, safety standards, and foreign principal qualification audit.', 'Internal', 16, 35000.00, 'Agency Testing Yard', 2, '2026-09-01', '2026-09-03', 1);

-- 9. Training Registrations
INSERT INTO `training_registrations` (`id`, `course_id`, `employee_id`, `status`, `attendance_percentage`, `quiz_score`, `evaluation_rating`, `evaluation_feedback`) VALUES
(1, 1, 4, 'Completed', 100, 95, 5, 'Very informative PDOS session! Prepared me well for my overseas deployment.'),
(2, 2, 4, 'Approved', 90, 88, 5, 'Great language practice for clinical nursing in Tokyo.'),
(3, 1, 3, 'Completed', 100, 90, 4, 'Clear overview of DMW/OWWA overseas laws.'),
(4, 3, 5, 'Pending', 0, 0, 5, NULL);

-- 10. Overseas Candidate Quiz Questions
INSERT INTO `quiz_questions` (`id`, `course_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`) VALUES
(1, 1, 'Which government agency issues the Overseas Employment Certificate (OEC) for departing OFWs?', 'DFA', 'DMW (Department of Migrant Workers)', 'DOLE', 'BIR', 'B'),
(2, 1, 'What is the primary function of OWWA for overseas workers?', 'Tax collection', 'Welfare assistance, repatriation, and insurance', 'Passport renewal', 'Flight booking', 'B'),
(3, 2, 'In Japanese hospital etiquette, what is the polite morning greeting used with patients?', 'Konnichiwa', 'Ohayou Gozaimasu', 'Arigatou', 'Sayonara', 'B');

-- 11. Candidate Skills Matrix
INSERT INTO `skills_matrix` (`id`, `employee_id`, `skill_name`, `proficiency_level`, `target_level`, `verified_by`) VALUES
(1, 4, 'Patient Care & Clinical Nursing', 'Advanced', 'Expert', 'Medical Director'),
(2, 5, 'SMAW 6G Pipe Welding', 'Advanced', 'Expert', 'Engr. Kenneth Sy (Trade Auditor)'),
(3, 4, 'Basic Conversational Japanese', 'Intermediate', 'Advanced', 'Language Center'),
(4, 3, 'Flight Coordination & POLO Audit', 'Expert', 'Expert', 'Agency Operations Lead');

-- 12. Document Categories for Overseas Recruitment
INSERT INTO `document_categories` (`id`, `name`, `is_required`) VALUES
(1, 'Valid Passport (Min. 2 Years Validity)', 1),
(2, 'DMW/POEA E-Registration & PEOS Cert', 1),
(3, 'NBI Clearance (Overseas Deployment)', 1),
(4, 'Medical Fit-to-Work Certificate (DOH/GCC Accredited)', 1),
(5, 'Trade Test Certification / Prometric Cert', 0),
(6, 'Overseas Employment Certificate (OEC)', 1);

-- 13. Documents
INSERT INTO `documents` (`id`, `employee_id`, `category_id`, `title`, `document_number`, `file_path`, `file_size`, `expiry_date`, `qr_code`, `status`) VALUES
(1, 4, 1, 'Philippine Passport', 'P12984920A', 'documents/doc_emp4_passport.pdf', '1.2 MB', '2031-05-12', 'QR-OFW4-PASSPORT-VERIFIED', 'Verified'),
(2, 4, 2, 'DMW E-Registration Certificate', 'DMW-2026-991823', 'documents/doc_emp4_dmw.pdf', '850 KB', '2027-01-20', 'QR-OFW4-DMW-VERIFIED', 'Verified'),
(3, 4, 4, 'GCC Medical Fit-to-Work Clearance', 'MED-2026-0091', 'documents/doc_emp4_medical.pdf', '640 KB', '2026-12-30', 'QR-OFW4-MED-FIT', 'Verified');

-- 14. Employment & Overseas Contracts
INSERT INTO `contracts` (`id`, `employee_id`, `contract_type`, `start_date`, `end_date`, `status`, `approval_status`, `document_file`) VALUES
(1, 4, 'Employment', '2023-03-15', '2027-03-15', 'Active', 'Approved', 'documents/contract_emp4_nurse.pdf'),
(2, 6, 'Employment', '2024-02-01', '2025-02-01', 'Terminated', 'Approved', 'documents/contract_emp6_caregiver.pdf');

-- 15. Government Statutory Contributions (PH sample calculation)
INSERT INTO `gov_contributions` (`id`, `employee_id`, `period_month`, `period_year`, `gross_salary`, `sss_employee`, `sss_employer`, `philhealth_employee`, `philhealth_employer`, `pagibig_employee`, `pagibig_employer`, `bir_tax_withheld`, `total_statutory`) VALUES
(1, 4, 7, 2026, 65000.00, 1350.00, 2850.00, 1625.00, 1625.00, 200.00, 200.00, 8125.00, 15975.00),
(2, 3, 7, 2026, 75000.00, 1350.00, 2850.00, 1875.00, 1875.00, 200.00, 200.00, 10625.00, 19125.00);

-- 16. Statutory & DMW Deadlines
INSERT INTO `gov_deadlines` (`id`, `agency_name`, `form_type`, `description`, `due_date`, `status`) VALUES
(1, 'SSS', 'Form R-5 / R-3', 'Monthly SSS OFW Remittance & Collection List', '2026-08-15', 'Upcoming'),
(2, 'PhilHealth', 'EPRST Monthly', 'PhilHealth Premium OFW Contributions', '2026-08-10', 'Upcoming'),
(3, 'Pag-IBIG', 'M1-1 Monthly', 'HDMF Monthly OFW Remittance List', '2026-08-20', 'Upcoming'),
(4, 'BIR', 'Form 1601-C', 'Monthly Withholding Tax on Compensation', '2026-08-10', 'Upcoming');

-- 17. Overseas Benefit & Insurance Plans
INSERT INTO `benefit_plans` (`id`, `name`, `type`, `description`, `coverage_amount`, `monthly_cost`, `employer_share`) VALUES
(1, 'Compulsory Overseas OFW Insurance (Repatriation & Medical Evacuation)', 'Health', 'Mandatory DMW coverage including medical evacuation, emergency repatriation, and accidental death', 500000.00, 2500.00, 2500.00),
(2, 'OWWA Emergency Welfare Fund', 'Health', 'Welfare assistance, medical aid, and disability benefits', 200000.00, 1000.00, 1000.00),
(3, 'Pre-Deployment Allowance & Meal Support', 'Allowance', 'Allowance provided to candidates during trade testing and visa processing', 5000.00, 5000.00, 5000.00),
(4, 'Overseas Deployment Performance Bonus', 'Bonus', 'Incentive bonus upon successful completion of overseas contract term', 50000.00, 0.00, 50000.00);

-- 18. Candidate Benefits
INSERT INTO `employee_benefits` (`id`, `employee_id`, `benefit_id`, `enrollment_date`, `status`) VALUES
(1, 4, 1, '2022-09-15', 'Active'),
(2, 4, 3, '2022-09-15', 'Active'),
(3, 3, 1, '2019-06-01', 'Active'),
(4, 3, 2, '2019-06-01', 'Active');

-- 19. Benefit Claims
INSERT INTO `benefit_claims` (`id`, `employee_id`, `benefit_id`, `claim_type`, `amount`, `receipt_number`, `status`) VALUES
(1, 4, 1, 'Pre-Departure Medical Examination Reimbursement', 4500.00, 'OR-991823', 'Approved'),
(2, 4, 3, 'Pre-Deployment Transportation Allowance', 1500.00, 'OR-772611', 'Pending');

-- 20. Placement & Deployment Loans
INSERT INTO `loans` (`id`, `employee_id`, `loan_type`, `principal_amount`, `interest_rate`, `term_months`, `monthly_deduction`, `total_payable`, `balance_remaining`, `status`) VALUES
(1, 4, 'Emergency', 30000.00, 2.00, 12, 2550.00, 30600.00, 15300.00, 'Active'),
(2, 3, 'Pag-IBIG', 50000.00, 5.95, 24, 2210.00, 53040.00, 35360.00, 'Active');

-- 21. Loan Payments
INSERT INTO `loan_payments` (`id`, `loan_id`, `payment_date`, `amount`, `payment_method`, `reference_no`) VALUES
(1, 1, '2026-05-30', 2550.00, 'Payroll Deduction', 'PAY-20260530-04'),
(2, 1, '2026-06-30', 2550.00, 'Payroll Deduction', 'PAY-20260630-04'),
(3, 1, '2026-07-30', 2550.00, 'Payroll Deduction', 'PAY-20260730-04');

-- 22. Deployment & Exit Clearances
INSERT INTO `separations` (`id`, `employee_id`, `separation_type`, `notice_date`, `effective_date`, `reason`, `status`) VALUES
(1, 6, 'Resignation', '2026-07-01', '2026-07-31', 'Contract completed in Dubai, returning to Philippines', 'Pending Clearance');

INSERT INTO `clearances` (`id`, `separation_id`, `department_name`, `status`, `cleared_by`, `clearance_date`, `comments`) VALUES
(1, 1, 'HR', 'Cleared', 'Sarah Jenkins', '2026-07-28 14:30:00', 'Exit interview completed. Passport and original documents returned.'),
(2, 1, 'IT', 'Cleared', 'Michael Chen', '2026-07-29 10:15:00', 'Agency ID badge and travel kit returned.'),
(3, 1, 'Finance', 'Pending', NULL, NULL, 'Pending final loan computation check.'),
(4, 1, 'Security', 'Cleared', 'Security Office', '2026-07-30 09:00:00', 'Proximity access card deactivated and returned.'),
(5, 1, 'Manager', 'Cleared', 'Recruitment Operations Lead', '2026-07-25 16:00:00', 'Overseas handover document signed.');

INSERT INTO `asset_returns` (`id`, `separation_id`, `item_name`, `serial_no`, `condition_status`, `returned`, `verified_by`) VALUES
(1, 1, 'Agency Candidate Luggage & Uniform Kit', 'KIT-SN-991823', 'Good', 1, 'Documentation Officer'),
(2, 1, 'Agency Access RFID Card', 'RFID-8812', 'Good', 1, 'Security Officer');

INSERT INTO `final_pays` (`id`, `separation_id`, `basic_pay_due`, `unused_leave_encashment`, `thirteenth_month_prorated`, `loan_deductions`, `tax_adjustment`, `net_final_pay`, `status`) VALUES
(1, 1, 21000.00, 8400.00, 24500.00, 0.00, -1200.00, 52700.00, 'Draft');

-- 23. System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('company_name', 'Moses Group of Companies', 'Registered company business name'),
('tax_year', '2026', 'Active fiscal tax year'),
('currency_symbol', 'â‚±', 'Currency symbol for payroll and reports'),
('theme_mode', 'light', 'Default UI theme mode (light)');

-- ApexHR Enterprise HRMS Allowances & Reimbursements Schema Expansion
USE `apex_hrms`;

-- Allowances Master Table
CREATE TABLE IF NOT EXISTS `allowances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('Monthly', 'Daily', 'One-Time', 'Hazard', 'Housing', 'Transportation', 'Food') DEFAULT 'Monthly',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `is_taxable` TINYINT(1) DEFAULT 0,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Employee Allowances Assignment Table
CREATE TABLE IF NOT EXISTS `employee_allowances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `allowance_id` INT NOT NULL,
  `effective_date` DATE NOT NULL,
  `status` ENUM('Active', 'Suspended', 'Terminated') DEFAULT 'Active',
  `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`allowance_id`) REFERENCES `allowances`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Employee Reimbursements & Expense Claims Table
CREATE TABLE IF NOT EXISTS `reimbursements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL,
  `category` ENUM('Medical', 'Travel', 'Meals', 'Supplies', 'Communication', 'Representation', 'Other') DEFAULT 'Travel',
  `title` VARCHAR(150) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `receipt_number` VARCHAR(50) NULL,
  `receipt_file` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Reimbursed') DEFAULT 'Pending',
  `approved_by` VARCHAR(100) NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Default Allowances if Empty
INSERT IGNORE INTO `allowances` (`id`, `name`, `type`, `amount`, `is_taxable`, `description`) VALUES
(1, 'Overseas Food Allowance', 'Monthly', 5000.00, 0, 'Monthly meal subsidy for deployed overseas personnel'),
(2, 'Housing & Accommodation Subsidy', 'Monthly', 8000.00, 0, 'Accommodation allowance for OFW staff'),
(3, 'Hazard & Trade Allowance', 'Monthly', 3500.00, 1, 'Trade skill hazard duty allowance');

SET FOREIGN_KEY_CHECKS = 1;


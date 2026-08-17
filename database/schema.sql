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

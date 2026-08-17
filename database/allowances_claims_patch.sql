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

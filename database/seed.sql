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
(1, 4, 'Emergency', 30000.00, 2.00, 12, 2550.00, 30600.00, 22950.00, 'Active'),
(2, 3, 'Pag-IBIG', 50000.00, 5.95, 24, 2210.00, 53040.00, 53040.00, 'Active');

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
('currency_symbol', '₱', 'Currency symbol for payroll and reports'),
('theme_mode', 'light', 'Default UI theme mode (light)');

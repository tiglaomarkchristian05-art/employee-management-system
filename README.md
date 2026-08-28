# ApexHR Enterprise HRMS

ApexHR Enterprise is a commercial-grade Human Resource Management System (HRMS) built with PHP 8 (Object-Oriented, MVC architecture), MySQL, Bootstrap 5, Glassmorphism UI, Vanilla JavaScript, AJAX, Chart.js, SweetAlert2, and DataTables.

## 🚀 Key Subsystems & Features

### 1. Training and Development Subsystem (LMS)
- **Employee Training Dashboard:** Overview of completed/pending courses, budget metrics, and average assessment scores.
- **Course Library & Registration:** Filter courses by Mandatory, Technical, Leadership, and Soft Skills.
- **Quiz & Assessment Engine:** Interactive multiple-choice evaluation with instant score calculation.
- **Certificate Generation:** Automated verification and downloadable Certificate of Completion.
- **Skills Matrix & Radar Charts:** Competency mapping comparing current skill levels against enterprise targets.

### 2. Document and Contract Management System
- **Employee Document Repository:** Secure file uploads for IDs, Diplomas, Clearances, and Medical Certificates.
- **Cryptographic QR Verification:** QR security stamp generation for document authenticity.
- **Contract Expiration Monitoring:** Automated 30/60-day alerts for expiring employment contracts.
- **Digital Signatures:** Signature pad integration for digital contract sign-offs.

### 3. Government Contribution & Compliance Subsystem (PH)
- **Statutory Profiles:** Tracking for SSS, PhilHealth, Pag-IBIG HDMF, and BIR TIN.
- **Live TRAIN Tax Calculator:** Real-time computation of statutory deductions and progressive withholding tax.
- **Monthly Remittance Reports:** Detailed employer vs. employee share summaries.
- **BIR 2316 Certificate Generator:** Instant generation of annual withholding tax certificates.

### 4. Benefits and Loans Management System
- **HMO & De Minimis Catalog:** Management of health insurance, rice/meal allowances, and performance incentives.
- **Self-Service Claims:** Online reimbursement request workflow with official receipt tracking.
- **Loan Amortization Schedule:** Automated deduction tracking for SSS, Pag-IBIG, Emergency, and Company loans.

### 5. Separation and Exit Clearance Subsystem
- **Offboarding Tracker:** Management of resignations, retirements, and terminations.
- **5-Department Clearance Routing:** Sequential sign-off workflow across HR, IT, Finance, Security, and Department Management.
- **Asset Return Checklist:** Hardware and RFID badge verification.
- **Final Pay & Certificate of Employment (COE):** Automated final pay computation and printable PDF COE generator.

### 6. Admin Panel & Enterprise Security
- **Role-Based Access Control (RBAC):** Super Admin, HR Manager, Department Head, and Employee roles.
- **Security Protections:** PDO Prepared Statements, Password Hashing (`bcrypt`), CSRF Tokens, and XSS Filters.
- **System Audit Trail:** Detailed log of all user activities, IP addresses, and database mutations.
- **Database Backup & Restore:** Single-click SQL export and factory reset restore.

---

## HostForge Deployment

This repository includes a production `Dockerfile`. In HostForge, rescan the
repository and choose the Dockerfile build strategy. The Dockerfile is the
source of truth, so leave Install, Build, Start, Output directory, and Root
directory blank.

Use these networking values:

- Port: `80`
- Health endpoint: `/health.php`

Attach a managed MySQL database. HostForge injects the database variables used
by the application automatically. Add these application secrets yourself:

- `APP_ENV=production`
- `APP_DEBUG=0`
- `APP_URL=https://your-assigned-hostname`
- `INITIAL_ADMIN_PASSWORD=<private password, at least 12 characters>`
- `INITIAL_USER_PASSWORD=<different private password, at least 12 characters>`

The container creates the schema on the first deployment and safely rechecks it
on later restarts. Do not enable `ALLOW_INSTALL` in production. Uploaded files
inside the container are ephemeral unless a persistent HostForge volume is
mounted for `/var/www/html/public/uploads`.

---

## 🛠️ Quick Installation Guide

1. Ensure your local server environment (XAMPP / WAMP / Laragon) is running **Apache** and **MySQL**.
2. Navigate to `http://localhost/employee/install.php` in your web browser.
3. Click **Install & Initialize Database**. The system will automatically build the `apex_hrms` database schema and seed realistic sample data.
4. Click **Launch HRMS Application** or open `http://localhost/employee/index.php`.

---

## 🔑 Demo Credentials

| Role | Username | Password |
| :--- | :--- | :--- |
| **Super Admin** | `admin` | `admin123` |
| **HR Manager** | `hr_manager` | `user123` |
| **Tech Lead / Dept Head** | `mchen` | `user123` |
| **Employee** | `employee` | `user123` |

<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/csrf.php';
require_once __DIR__ . '/config/security.php';

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Model.php';

require_once APP_PATH . 'Controllers/AuthController.php';
require_once APP_PATH . 'Controllers/DashboardController.php';
require_once APP_PATH . 'Controllers/EmployeeController.php';
require_once APP_PATH . 'Controllers/TrainingController.php';
require_once APP_PATH . 'Controllers/DocumentController.php';
require_once APP_PATH . 'Controllers/ComplianceController.php';
require_once APP_PATH . 'Controllers/BenefitsController.php';
require_once APP_PATH . 'Controllers/SeparationController.php';
require_once APP_PATH . 'Controllers/AdminController.php';

$page = $_GET['page'] ?? (Auth::check() ? 'dashboard' : 'login');

switch ($page) {
    case 'login':
        (new AuthController())->login();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'dashboard':
        (new DashboardController())->index();
        break;

    case 'training':
        (new TrainingController())->dashboard();
        break;
    case 'training_courses':
        (new TrainingController())->courses();
        break;
    case 'training_matrix':
        (new TrainingController())->matrix();
        break;
    case 'training_register':
        (new TrainingController())->register();
        break;
    case 'training_quiz':
        (new TrainingController())->quiz();
        break;
    case 'training_submit_quiz':
        (new TrainingController())->submitQuiz();
        break;

    case 'documents':
        (new DocumentController())->index();
        break;
    case 'documents_contracts':
        (new DocumentController())->contracts();
        break;
    case 'documents_upload':
        (new DocumentController())->upload();
        break;
    case 'documents_delete':
        (new DocumentController())->delete();
        break;

    case 'compliance':
        (new ComplianceController())->index();
        break;
    case 'compliance_calculator':
        (new ComplianceController())->calculator();
        break;
    case 'compliance_bir2316':
        (new ComplianceController())->bir2316();
        break;
    case 'compliance_generate':
        (new ComplianceController())->generateContribution();
        break;

    case 'benefits':
        (new BenefitsController())->index();
        break;
    case 'benefits_loans':
        (new BenefitsController())->loans();
        break;
    case 'benefits_submit_claim':
        (new BenefitsController())->submitClaim();
        break;
    case 'benefits_request_loan':
        (new BenefitsController())->requestLoan();
        break;
    case 'admin_request_loan':
        (new BenefitsController())->adminRequestLoan();
        break;
    case 'admin_request_claim':
        (new BenefitsController())->adminSubmitClaim();
        break;
    case 'admin_grant_allowance':
        (new BenefitsController())->adminGrantAllowance();
        break;
    case 'admin_update_claim_status':
        (new BenefitsController())->updateClaimStatus();
        break;
    case 'admin_update_loan_status':
        (new BenefitsController())->updateLoanStatus();
        break;
    case 'benefits_claim_delete':
        (new BenefitsController())->deleteClaim();
        break;
    case 'benefits_loan_delete':
        (new BenefitsController())->deleteLoan();
        break;
    case 'benefits_loan_payment':
        (new BenefitsController())->addLoanPayment();
        break;
    case 'allowance_create':
        (new BenefitsController())->createAllowance();
        break;
    case 'allowance_assign':
        (new BenefitsController())->assignAllowance();
        break;
    case 'allowance_delete':
        (new BenefitsController())->deleteAllowance();
        break;

    case 'separation':
        (new SeparationController())->index();
        break;
    case 'separation_clearance':
        (new SeparationController())->clearance();
        break;
    case 'separation_coe':
        (new SeparationController())->coe();
        break;
    case 'separation_initiate':
        (new SeparationController())->initiate();
        break;
    case 'separation_update_clearance':
        (new SeparationController())->updateClearance();
        break;

    case 'employees':
        (new EmployeeController())->index();
        break;
    case 'employee_get':
        (new EmployeeController())->get();
        break;
    case 'employee_store':
        (new EmployeeController())->store();
        break;
    case 'employee_update':
        (new EmployeeController())->update();
        break;
    case 'employee_delete':
        (new EmployeeController())->delete();
        break;
    case 'admin_users':
        (new AdminController())->users();
        break;
    case 'admin_create_user':
        (new AdminController())->createUser();
        break;
    case 'admin_update_user':
        (new AdminController())->updateUser();
        break;
    case 'admin_delete_user':
        (new AdminController())->deleteUser();
        break;
    case 'admin_toggle_user_status':
        (new AdminController())->toggleUserStatus();
        break;
    case 'admin_reset_password':
        (new AdminController())->resetPassword();
        break;
    case 'admin_logs':
        (new AdminController())->logs();
        break;
    case 'admin_settings':
        (new AdminController())->settings();
        break;
    case 'admin_save_settings':
        (new AdminController())->saveSettings();
        break;
    case 'admin_backup':
        (new AdminController())->backup();
        break;
    case 'admin_restore':
        (new AdminController())->restore();
        break;

    default:
        (new DashboardController())->index();
        break;
}

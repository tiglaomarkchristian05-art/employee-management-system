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
require_once APP_PATH . 'Controllers/TrainingRecommendationController.php';
require_once APP_PATH . 'Controllers/DocumentController.php';
require_once APP_PATH . 'Controllers/ComplianceController.php';
require_once APP_PATH . 'Controllers/BenefitsController.php';
require_once APP_PATH . 'Controllers/LoansController.php';
require_once APP_PATH . 'Controllers/SeparationController.php';
require_once APP_PATH . 'Controllers/AdminController.php';
require_once APP_PATH . 'Controllers/AccountController.php';
require_once APP_PATH . 'Controllers/ReportController.php';

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
    case 'my_profile':
        (new AccountController())->profile();
        break;
    case 'notifications':
        (new AccountController())->notifications();
        break;
    case 'notification_open':
        (new AccountController())->openNotification();
        break;
    case 'notifications_mark_all':
        (new AccountController())->markAllNotificationsRead();
        break;
    case 'reports':
        (new ReportController())->index();
        break;
    case 'reports_export':
        (new ReportController())->export();
        break;

    case 'training':
        (new TrainingController())->dashboard();
        break;
    case 'training_recommendations':
        (new TrainingRecommendationController())->index();
        break;
    case 'training_recommendations_analyze':
        (new TrainingRecommendationController())->analyze();
        break;
    case 'training_recommendations_review':
        (new TrainingRecommendationController())->review();
        break;
    case 'training_recommendations_assign':
        (new TrainingRecommendationController())->assign();
        break;
    case 'training_recommendations_export':
        (new TrainingRecommendationController())->export();
        break;
    case 'training_history_store':
        (new TrainingRecommendationController())->saveHistorical();
        break;
    case 'training_courses':
        (new TrainingController())->courses();
        break;
    case 'training_details':
        (new TrainingController())->details();
        break;
    case 'training_store':
        (new TrainingController())->store();
        break;
    case 'training_update':
        (new TrainingController())->update();
        break;
    case 'training_cancel':
        (new TrainingController())->cancel();
        break;
    case 'training_remind':
        (new TrainingController())->remind();
        break;
    case 'training_assign':
        (new TrainingController())->assign();
        break;
    case 'training_confirm':
        (new TrainingController())->confirm();
        break;
    case 'training_update_participant':
        (new TrainingController())->updateParticipant();
        break;
    case 'training_upload_requirement':
        (new TrainingController())->uploadRequirement();
        break;
    case 'training_material':
        (new TrainingController())->downloadMaterial();
        break;
    case 'training_requirement':
        (new TrainingController())->downloadRequirement();
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
    case 'training_certificate':
        (new TrainingController())->certificate();
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
    case 'documents_download':
        (new DocumentController())->download();
        break;
    case 'documents_type_store':
        (new DocumentController())->storeType();
        break;
    case 'documents_requirement_assign':
        (new DocumentController())->assignRequirement();
        break;
    case 'documents_review':
        (new DocumentController())->review();
        break;
    case 'documents_acknowledge':
        (new DocumentController())->acknowledge();
        break;
    case 'documents_request_correction':
        (new DocumentController())->requestCorrection();
        break;
    case 'contracts_store':
        (new DocumentController())->storeContract();
        break;
    case 'contracts_renew':
        (new DocumentController())->renewContract();
        break;
    case 'contracts_download':
        (new DocumentController())->downloadContract();
        break;
    case 'contracts_acknowledge':
        (new DocumentController())->acknowledgeContract();
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
    case 'compliance_submit_record':
        (new ComplianceController())->submitRecord();
        break;
    case 'compliance_request_correction':
        (new ComplianceController())->requestCorrection();
        break;
    case 'compliance_review_record':
        (new ComplianceController())->reviewRecord();
        break;
    case 'compliance_decide_correction':
        (new ComplianceController())->decideCorrection();
        break;
    case 'compliance_update_contribution':
        (new ComplianceController())->updateContribution();
        break;
    case 'compliance_download_contribution':
        (new ComplianceController())->downloadContribution();
        break;
    case 'compliance_export_report':
        (new ComplianceController())->exportReport();
        break;
    case 'compliance_supporting_file':
        (new ComplianceController())->downloadSupporting();
        break;

    case 'benefits':
        (new BenefitsController())->index();
        break;
    case 'benefits_loans':
        (new BenefitsController())->loans();
        break;
    case 'benefits_submit_claim':
        (new BenefitsController())->applyBenefit();
        break;
    case 'benefits_request_loan':
        (new LoansController())->apply();
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
        (new BenefitsController())->reviewBenefit();
        break;
    case 'benefits_resubmit':
        (new BenefitsController())->resubmitBenefit();
        break;
    case 'benefits_plan_store':
        (new BenefitsController())->storeBenefit();
        break;
    case 'benefits_plan_update':
        (new BenefitsController())->updateBenefit();
        break;
    case 'benefits_plan_toggle':
        (new BenefitsController())->toggleBenefit();
        break;
    case 'benefits_requirement':
        (new BenefitsController())->downloadBenefitFile();
        break;
    case 'admin_update_loan_status':
        (new LoansController())->review();
        break;
    case 'benefits_claim_delete':
        (new BenefitsController())->deleteClaim();
        break;
    case 'benefits_loan_delete':
        (new BenefitsController())->deleteLoan();
        break;
    case 'benefits_loan_payment':
        (new LoansController())->payment();
        break;
    case 'benefits_loan_resubmit':
        (new LoansController())->resubmit();
        break;
    case 'loan_program_save':
        (new LoansController())->saveProgram();
        break;
    case 'loan_program_toggle':
        (new LoansController())->toggleProgram();
        break;
    case 'benefits_loan_detail':
        (new LoansController())->detail();
        break;
    case 'benefits_loan_requirement':
        (new LoansController())->requirement();
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
    case 'separation_review':
        (new SeparationController())->review();
        break;
    case 'separation_generate_clearance':
        (new SeparationController())->generateClearance();
        break;
    case 'separation_upload_clearance_document':
        (new SeparationController())->uploadClearanceDocument();
        break;
    case 'separation_save_asset':
        (new SeparationController())->saveAsset();
        break;
    case 'separation_save_interview':
        (new SeparationController())->saveInterview();
        break;
    case 'separation_complete':
        (new SeparationController())->complete();
        break;
    case 'separation_archive':
        (new SeparationController())->archive();
        break;
    case 'separation_final_clearance':
        (new SeparationController())->finalClearance();
        break;
    case 'separation_file':
        (new SeparationController())->downloadFile();
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

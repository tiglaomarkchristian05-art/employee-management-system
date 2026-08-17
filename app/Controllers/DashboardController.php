<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Training.php';
require_once APP_PATH . 'Models/Document.php';
require_once APP_PATH . 'Models/Compliance.php';
require_once APP_PATH . 'Models/Benefit.php';
require_once APP_PATH . 'Models/Separation.php';

class DashboardController extends Controller {
    public function index() {
        Auth::requireAuth();

        $employeeModel = new Employee();
        $trainingModel = new Training();
        $documentModel = new Document();
        $complianceModel = new Compliance();
        $benefitModel = new Benefit();
        $separationModel = new Separation();

        $user = Auth::user();
        $empId = $user['employee_id'];

        $data = [
            'user'             => $user,
            'total_employees'  => $employeeModel->getActiveCount(),
            'training_stats'   => $trainingModel->getDashboardStats(),
            'expiring_contracts' => count($documentModel->getExpiringContracts(30)),
            'upcoming_deadlines' => count($complianceModel->getUpcomingDeadlines()),
            'recent_trainings' => array_slice($trainingModel->getCoursesWithDetails(), 0, 5),
            'expiring_docs'    => array_slice($documentModel->getDocumentsWithDetails($empId), 0, 5),
            'pending_claims'   => count($benefitModel->getClaimsWithDetails($empId)),
            'separations'      => count($separationModel->getSeparationsWithDetails())
        ];

        $this->view('dashboard/index', $data);
    }
}

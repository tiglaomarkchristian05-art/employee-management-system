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

        if (Auth::isSelfService()) {
            $employeeId = Auth::employeeId();
            $registrations = $trainingModel->getRegistrationsWithDetails($employeeId);
            $documents = $documentModel->getDocumentsWithDetails($employeeId);
            $claims = $benefitModel->getClaimsWithDetails($employeeId);
            $separations = $separationModel->getSeparationsWithDetails($employeeId);
            $data = [
                'user' => $user,
                'registrations' => $registrations,
                'training_completed' => count(array_filter($registrations, fn($row) => ($row['status'] ?? '') === 'Completed')),
                'documents' => $documents,
                'expiring_documents' => count(array_filter($documents, fn($row) => !empty($row['expiry_date']) && strtotime($row['expiry_date']) <= strtotime('+60 days'))),
                'contributions' => $complianceModel->getContributionsWithDetails(null, null, $employeeId),
                'claims' => $claims,
                'pending_claims' => count(array_filter($claims, fn($row) => ($row['status'] ?? '') === 'Pending')),
                'separations' => $separations
            ];
            $this->view('dashboard/employee', $data);
            return;
        }

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

<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Dashboard.php';

class DashboardController extends Controller {
    public function index() {
        Auth::requireAuth();

        $user = Auth::user();
        $dashboard = new Dashboard();

        try {
            if (Auth::isSelfService()) {
                $employeeId = Auth::employeeId();
                $data = [
                    'user' => $user,
                    'summary' => $dashboard->getEmployeeSummary($employeeId, $user['id']),
                    'details' => $dashboard->getEmployeeDashboardDetails($employeeId, $user['id']),
                    'dashboard_error' => null,
                ];
                $this->view('dashboard/employee', $data);
                return;
            }

            Auth::requirePermission('view_management_dashboard');
            $data = [
                'user' => $user,
                'summary' => $dashboard->getAdminSummary(),
                'recent_activities' => $dashboard->getAdminRecentActivities(),
                'pending_actions' => $dashboard->getAdminPendingActions(),
                'upcoming_deadlines' => $dashboard->getAdminDeadlines(),
                'dashboard_error' => null,
            ];
            $this->view('dashboard/index', $data);
        } catch (Throwable $error) {
            error_log('Dashboard loading failed: ' . $error->getMessage());
            $fallback = [
                'user' => $user,
                'summary' => [],
                'details' => [],
                'recent_activities' => [],
                'pending_actions' => [],
                'upcoming_deadlines' => [],
                'dashboard_error' => 'Dashboard information could not be loaded. Please refresh the page or try again later.',
            ];
            $this->view(Auth::isSelfService() ? 'dashboard/employee' : 'dashboard/index', $fallback);
        }
    }
}

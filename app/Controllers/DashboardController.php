<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Dashboard.php';

class DashboardController extends Controller {
    public function index() {
        Auth::requireAuth();
        if (Auth::can('approve_department_clearance') && empty(Auth::user()['employee_id'])) {
            redirect(BASE_URL . '?page=separation');
        }

        $user = Auth::user();
        $dashboard = new Dashboard();

        try {
            if (Auth::isSystemAdmin()) {
                Auth::requirePermission('view_system_dashboard');
                $db = Database::getInstance();
                $data = [
                    'user' => $user,
                    'system_summary' => [
                        'users' => (int)($db->fetchOne('SELECT COUNT(*) AS total FROM users')['total'] ?? 0),
                        'active_users' => (int)($db->fetchOne('SELECT COUNT(*) AS total FROM users WHERE is_active=1')['total'] ?? 0),
                        'roles' => (int)($db->fetchOne('SELECT COUNT(*) AS total FROM roles')['total'] ?? 0),
                        'audit_events' => (int)($db->fetchOne('SELECT COUNT(*) AS total FROM audit_logs')['total'] ?? 0),
                    ],
                    'recent_system_events' => $db->fetchAll("SELECT a.*,COALESCE(u.username,'System') actor FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE a.module IN ('Account Security','System Admin','Authentication') ORDER BY a.id DESC LIMIT 5"),
                    'dashboard_error' => null,
                ];
                $this->view('dashboard/system', $data);
                return;
            }

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
            $this->view(Auth::isSystemAdmin() ? 'dashboard/system' : (Auth::isSelfService() ? 'dashboard/employee' : 'dashboard/index'), $fallback);
        }
    }
}

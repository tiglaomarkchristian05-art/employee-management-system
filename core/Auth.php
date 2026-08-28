<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLogger.php';

class Auth {
    private static $permissions = [
        'Super Admin' => ['*'],
        'HR Manager' => ['view_management_dashboard', 'manage_employees', 'manage_employee_accounts', 'manage_training', 'manage_documents', 'manage_compliance', 'manage_benefits', 'manage_loans', 'manage_separation', 'view_audit_logs'],
        'Department Head' => ['view_own_dashboard', 'view_own_training', 'enroll_training', 'view_own_documents', 'upload_own_documents', 'view_own_contributions', 'submit_claim', 'submit_loan', 'view_own_benefits', 'view_own_clearance', 'submit_separation', 'view_own_profile', 'view_notifications', 'approve_department_clearance'],
        'Employee' => ['view_own_dashboard', 'view_own_training', 'enroll_training', 'view_own_documents', 'upload_own_documents', 'view_own_contributions', 'submit_claim', 'submit_loan', 'view_own_benefits', 'view_own_clearance', 'submit_separation', 'view_own_profile', 'view_notifications'],
    ];
    public static function check() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function user() {
        if (!self::check()) return null;
        return [
            'id'            => $_SESSION['user_id'],
            'username'      => $_SESSION['username'] ?? '',
            'full_name'     => $_SESSION['full_name'] ?? 'User',
            'role'          => $_SESSION['role_name'] ?? 'Employee',
            'role_id'       => $_SESSION['role_id'] ?? 4,
            'employee_id'   => $_SESSION['employee_id'] ?? null,
            'email'         => $_SESSION['email'] ?? '',
            'avatar'        => $_SESSION['avatar'] ?? 'default.png',
            'department'    => $_SESSION['department_name'] ?? 'General'
            ,'is_active'    => (int)($_SESSION['is_active'] ?? 1)
            ,'permissions'  => self::$permissions[$_SESSION['role_name'] ?? 'Employee'] ?? []
        ];
    }

    public static function hasRole($roles) {
        if (!self::check()) return false;
        $userRole = strtolower($_SESSION['role_name'] ?? '');
        if (is_string($roles)) {
            $roles = [strtolower($roles)];
        } else {
            $roles = array_map('strtolower', $roles);
        }
        return in_array($userRole, $roles) || $userRole === 'super admin' || $userRole === 'admin';
    }

    public static function isAdmin() {
        return self::hasRole(['Super Admin', 'HR Manager']);
    }

    public static function isEmployee() {
        return self::hasRole(['Employee']);
    }

    public static function isSelfService() {
        return self::hasRole(['Employee', 'Department Head']) && !self::isAdmin();
    }

    public static function can($permission) {
        if (!self::check()) return false;
        $role = $_SESSION['role_name'] ?? 'Employee';
        $allowed = self::$permissions[$role] ?? [];
        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }

    public static function employeeId() {
        self::requireAuth();
        $employeeId = intval($_SESSION['employee_id'] ?? 0);
        if (self::isSelfService() && $employeeId <= 0) self::deny('Your account is not linked to an employee profile.');
        return $employeeId ?: null;
    }

    public static function requirePermission($permission) {
        self::requireAuth();
        if (!self::can($permission)) self::deny();
    }

    public static function deny($message = 'You do not have permission to access this page.') {
        http_response_code(403);
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) json_response('error', $message, [], 403);
        $errorMessage = $message;
        $dashboardUrl = self::check() ? BASE_URL . '?page=dashboard' : BASE_URL . '?page=login';
        require APP_PATH . 'Views/errors/403.php';
        exit;
    }

    public static function requireAuth() {
        if (!self::check()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                json_response('error', 'Session expired. Please log in again.', [], 401);
            }
            redirect(BASE_URL . '?page=login');
        }

        $db = Database::getInstance();
        $account = $db->fetchOne(
            "SELECT u.id, u.username, u.role_id, u.employee_id, u.is_active,
                    r.name AS role_name, r.permissions,
                    e.first_name, e.last_name, e.email, e.photo,
                    d.name AS department_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE u.id = ?",
            [intval($_SESSION['user_id'])]
        );

        if (!$account || !(int)$account['is_active']) {
            self::clearSession();
            self::deny('This account is inactive or no longer available.');
        }

        $_SESSION['username'] = $account['username'];
        $_SESSION['role_name'] = $account['role_name'];
        $_SESSION['role_id'] = $account['role_id'];
        $_SESSION['employee_id'] = $account['employee_id'];
        $_SESSION['is_active'] = (int)$account['is_active'];
        $_SESSION['email'] = $account['email'] ?? '';
        $_SESSION['full_name'] = !empty($account['first_name']) ? trim($account['first_name'] . ' ' . $account['last_name']) : $account['username'];
        $_SESSION['department_name'] = $account['department_name'] ?? 'General';
        $_SESSION['avatar'] = $account['photo'] ?? 'default.png';
    }

    public static function requireRole($roles) {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            self::deny();
        }
    }

    public static function requireAdmin() {
        self::requireAuth();
        if (!self::isAdmin()) self::deny('Administrator or HR access is required.');
    }

    public static function requireEmployee() {
        self::requireAuth();
        if (!self::isEmployee()) self::deny('Employee access is required.');
    }

    public static function requireSelfService() {
        self::requireAuth();
        if (!self::isSelfService()) self::deny('Employee self-service access is required.');
        self::employeeId();
    }

    public static function requireOwnership($recordEmployeeId) {
        self::requireAuth();
        if (!self::isAdmin() && intval($recordEmployeeId) !== intval(self::employeeId())) {
            self::deny('You may only access records linked to your employee account.');
        }
    }

    public static function requireMethod($method) {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== strtoupper($method)) {
            http_response_code(405);
            header('Allow: ' . strtoupper($method));
            exit('Method Not Allowed');
        }
    }

    private static function clearSession() {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

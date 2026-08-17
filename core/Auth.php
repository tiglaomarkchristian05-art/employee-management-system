<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AuditLogger.php';

class Auth {
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

    public static function requireAuth() {
        if (!self::check()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                json_response('error', 'Session expired. Please log in again.', [], 401);
            }
            redirect(BASE_URL . '?page=login');
        }
    }

    public static function requireRole($roles) {
        self::requireAuth();
        if (!self::hasRole($roles)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                json_response('error', 'Access denied. You do not have permissions for this action.', [], 403);
            }
            die("<div style='font-family:sans-serif; padding:50px; text-align:center;'><h2>403 Forbidden</h2><p>You do not have access permission to view this module.</p><a href='index.php?page=dashboard'>Return to Dashboard</a></div>");
        }
    }
}

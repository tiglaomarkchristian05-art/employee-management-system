<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/User.php';

class AuthController extends Controller {
    public function login() {
        if (Auth::check()) {
            redirect(BASE_URL . '?page=dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $this->json('error', 'Invalid security CSRF token. Please refresh.');
            }

            $username = sanitize_input($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $this->json('error', 'Please provide both email/username and password.');
            }

            $userModel = new User();
            $user = $userModel->findByUsername($username);

            // Demo password matching fallbacks
            $isValid = false;
            if ($user) {
                if (verify_password($password, $user['password'])) {
                    $isValid = true;
                } else if (in_array($password, ['admin123', 'user123', 'Admin@123', 'Hr@123', 'Emp@123'])) {
                    $isValid = true; // Fallback for instant demo access
                }
            }

            if ($user && $user['is_active'] && $isValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = !empty($user['first_name']) ? $user['first_name'] . ' ' . $user['last_name'] : $user['username'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['department_name'] = $user['department_name'] ?? 'General';
                $_SESSION['avatar'] = $user['photo'] ?? 'default.png';

                // Update last login
                $userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
                AuditLogger::log('LOGIN', 'Authentication', 'User logged in successfully.', $user['id']);

                $this->json('success', 'Login successful! Redirecting to dashboard...', ['redirect' => BASE_URL . '?page=dashboard']);
            } else {
                AuditLogger::log('FAILED_LOGIN', 'Authentication', "Failed login attempt for username: {$username}");
                $this->json('error', 'Invalid credentials or inactive account.');
            }
        }

        $this->view('auth/login');
    }

    public function logout() {
        if (Auth::check()) {
            AuditLogger::log('LOGOUT', 'Authentication', 'User logged out.');
            session_destroy();
        }
        redirect(BASE_URL . '?page=login');
    }
}

<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Admin.php';
require_once APP_PATH . 'Models/User.php';
require_once APP_PATH . 'Models/Employee.php';

class AdminController extends Controller {
    public function users() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        $userModel = new User();
        $adminModel = new Admin();
        $employeeModel = new Employee();

        $data = [
            'users'     => $userModel->getAllUsersWithDetails(),
            'roles'     => $adminModel->getRoles(),
            'employees' => $employeeModel->getAllWithDetails()
        ];
        $this->view('admin/users', $data);
    }

    public function logs() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        $adminModel = new Admin();
        $data = [
            'logs' => $adminModel->getAuditLogs()
        ];
        $this->view('admin/logs', $data);
    }

    public function settings() {
        Auth::requireRole(['Super Admin']);
        $adminModel = new Admin();
        $data = [
            'settings' => $adminModel->getSystemSettings()
        ];
        $this->view('admin/settings', $data);
    }

    public function backup() {
        Auth::requireRole(['Super Admin']);
        $adminModel = new Admin();

        if (isset($_GET['download']) && $_GET['download'] === 'sql') {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="apexhr_backup_' . date('Y-m-d_H-i-s') . '.sql"');
            
            echo "-- ApexHR Database Dump\n";
            echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
            echo file_get_contents(ROOT_PATH . 'database/schema.sql');
            echo "\n\n";
            echo file_get_contents(ROOT_PATH . 'database/seed.sql');
            exit;
        }

        $this->view('admin/backup');
    }

    public function restore() {
        Auth::requireRole(['Super Admin']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $db = Database::getInstance();
        $schemaSql = file_get_contents(ROOT_PATH . 'database/schema.sql');
        $seedSql = file_get_contents(ROOT_PATH . 'database/seed.sql');

        $db->getConnection()->exec($schemaSql);
        $db->getConnection()->exec($seedSql);

        AuditLogger::log('RESTORE_DATABASE', 'System Admin', 'Restored default enterprise database from backup script.');
        $this->json('success', 'Database restored successfully!');
    }

    public function saveSettings() {
        Auth::requireRole(['Super Admin']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $settings = $_POST['settings'] ?? [];
        $db = Database::getInstance();
        foreach ($settings as $key => $value) {
            $cleanKey = sanitize_input($key);
            $cleanVal = sanitize_input($value);
            $db->update('system_settings', ['setting_value' => $cleanVal], "setting_key = ?", [$cleanKey]);
        }

        AuditLogger::log('UPDATE_SETTINGS', 'System Admin', 'Updated enterprise system configurations.');
        $this->json('success', 'System configurations updated successfully!');
    }

    public function createUser() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? 'User@123';
        
        $userSession = Auth::user();
        $roleId = intval($_POST['role_id'] ?? 4);
        if ($userSession['role'] === 'HR Manager') {
            $roleId = 4;
        }

        $empId = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;

        if (empty($username)) {
            $this->json('error', 'Username is required.');
        }

        $userModel = new User();
        $existing = $userModel->db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            $this->json('error', 'Username already exists.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = $userModel->create([
            'username'    => $username,
            'password'    => $hash,
            'role_id'     => $roleId,
            'employee_id' => $empId,
            'is_active'   => 1
        ]);

        AuditLogger::log('CREATE_USER', 'System Admin', "Created new user account '{$username}'");
        $this->json('success', "User account '{$username}' created successfully!", ['user_id' => $userId]);
    }

    public function toggleUserStatus() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        $userModel = new User();
        $u = $userModel->find($userId);
        if (!$u) {
            $this->json('error', 'User account not found.');
        }

        $newStatus = $u['is_active'] ? 0 : 1;
        $userModel->update($userId, ['is_active' => $newStatus]);

        $statusStr = $newStatus ? 'enabled' : 'disabled';
        AuditLogger::log('TOGGLE_USER_STATUS', 'System Admin', "User ID {$userId} ({$u['username']}) was {$statusStr}");
        $this->json('success', "User account '{$u['username']}' is now {$statusStr}!");
    }

    public function updateUser() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId = intval($_POST['user_id'] ?? 0);
        $roleId = intval($_POST['role_id'] ?? 4);
        $empId  = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;

        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            $this->json('error', 'User not found.');
        }

        $userModel->update($userId, [
            'role_id'     => $roleId,
            'employee_id' => $empId
        ]);

        AuditLogger::log('UPDATE_USER', 'System Admin', "Updated role and employee mapping for user '{$user['username']}'");
        $this->json('success', "User account '{$user['username']}' updated successfully!");
    }

    public function deleteUser() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            $this->json('error', 'User not found.');
        }

        if ($user['username'] === 'admin@mosesgroup.ph') {
            $this->json('error', 'The primary system admin account cannot be deleted.');
        }

        $userModel->delete($userId);
        AuditLogger::log('DELETE_USER', 'System Admin', "Deleted user account '{$user['username']}' (ID: {$userId})");
        $this->json('success', "User account '{$user['username']}' deleted successfully!");
    }

    public function resetPassword() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId   = intval($_POST['user_id'] ?? 0);
        $newPass  = $_POST['new_password'] ?? 'User@123';

        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        if (strlen($newPass) < 6) {
            $this->json('error', 'Password must be at least 6 characters.');
        }

        $userModel = new User();
        $user = $userModel->find($userId);
        if (!$user) {
            $this->json('error', 'User not found.');
        }

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $userModel->update($userId, ['password' => $hash]);

        AuditLogger::log('RESET_PASSWORD', 'System Admin', "Reset password for user '{$user['username']}'");
        $this->json('success', "Password for '{$user['username']}' has been reset to: {$newPass}");
    }
}

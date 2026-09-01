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

        $roles = $adminModel->getRoles();
        if (Auth::hasRole(['HR Manager']) && !Auth::hasRole(['Super Admin'])) {
            $roles = array_values(array_filter($roles, fn($role) => $role['name'] === 'Employee'));
        }
        $data = [
            'users'     => $userModel->getAllUsersWithDetails(),
            'roles'     => $roles,
            'employees' => $employeeModel->getAllWithDetails()
        ];
        $this->view('admin/users', $data);
    }

    public function logs() {
        Auth::requirePermission('view_audit_logs');
        $adminModel = new Admin();
        $filters = [
            'q' => sanitize_input($_GET['q'] ?? ''),
            'date_from' => sanitize_input($_GET['date_from'] ?? ''),
            'date_to' => sanitize_input($_GET['date_to'] ?? ''),
            'user_id' => (int)($_GET['user_id'] ?? 0),
            'role' => sanitize_input($_GET['role'] ?? ''),
            'module' => sanitize_input($_GET['module'] ?? ''),
            'action' => sanitize_input($_GET['action'] ?? '')
        ];
        $pagination = $adminModel->getAuditLogs($filters, (int)($_GET['p'] ?? 1), 20);
        $this->view('admin/logs', [
            'logs' => $pagination['items'],
            'pagination' => $pagination,
            'filters' => $filters,
            'filter_options' => $adminModel->getAuditFilterOptions()
        ]);
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
            AuditLogger::log('EXPORT_DATABASE_BACKUP', 'System Admin', 'Exported a live SQL backup of the application database.');
            header('Content-Type: application/sql; charset=UTF-8');
            header('Content-Disposition: attachment; filename="apexhr_backup_' . date('Y-m-d_H-i-s') . '.sql"');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            $this->streamLiveDatabaseBackup();
            exit;
        }

        $this->view('admin/backup');
    }

    public function restore() {
        Auth::requireRole(['Super Admin']);
        Auth::requireMethod('POST');
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
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $settings = $_POST['settings'] ?? [];
        if (!is_array($settings)) $this->json('error', 'Invalid settings payload.', [], 422);
        $allowed = ['company_name', 'tax_year', 'currency_symbol', 'theme_mode'];
        $settings = array_intersect_key($settings, array_flip($allowed));
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $settings) || trim((string)$settings[$key]) === '') {
                $this->json('error', ucwords(str_replace('_', ' ', $key)).' is required.', [], 422);
            }
        }
        $companyName = trim((string)$settings['company_name']);
        $taxYear = trim((string)$settings['tax_year']);
        $currency = trim((string)$settings['currency_symbol']);
        $theme = strtolower(trim((string)$settings['theme_mode']));
        if (mb_strlen($companyName) > 150) $this->json('error', 'Company Name must not exceed 150 characters.', [], 422);
        if (!preg_match('/^\d{4}$/', $taxYear) || (int)$taxYear < 2000 || (int)$taxYear > 2100) $this->json('error', 'Tax Year must be between 2000 and 2100.', [], 422);
        if (mb_strlen($currency) > 5) $this->json('error', 'Currency Symbol must not exceed 5 characters.', [], 422);
        if (!in_array($theme, ['light', 'dark'], true)) $this->json('error', 'Theme Mode must be light or dark.', [], 422);
        $settings = ['company_name'=>$companyName, 'tax_year'=>$taxYear, 'currency_symbol'=>$currency, 'theme_mode'=>$theme];
        $db = Database::getInstance();
        foreach ($settings as $key => $value) {
            $cleanKey = sanitize_input($key);
            $cleanVal = sanitize_input($value);
            $db->update('system_settings', ['setting_value' => $cleanVal], "setting_key = ?", [$cleanKey]);
        }

        AuditLogger::log('UPDATE_SETTINGS', 'System Admin', 'Updated enterprise system configurations.');
        $this->json('success', 'System configurations updated successfully!');
    }

    private function streamLiveDatabaseBackup() {
        $pdo = Database::getInstance()->getConnection();
        $identifier = static fn($name) => '`'.str_replace('`', '``', (string)$name).'`';
        echo "-- Core 3 live database backup\n";
        echo "-- Database: ".DB_NAME."\n";
        echo "-- Generated: ".date('Y-m-d H:i:s P')."\n\n";
        echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $quotedTable = $identifier($table);
            $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_NUM);
            echo "-- --------------------------------------------------------\n";
            echo "-- Table {$quotedTable}\n\n";
            echo "DROP TABLE IF EXISTS {$quotedTable};\n".$create[1].";\n\n";
            $statement = $pdo->query("SELECT * FROM {$quotedTable}");
            $columns = null;$rows = [];
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if ($columns === null) $columns = array_keys($row);
                $values = array_map(static fn($value) => $value === null ? 'NULL' : $pdo->quote((string)$value), array_values($row));
                $rows[] = '('.implode(',', $values).')';
                if (count($rows) === 100) {
                    echo 'INSERT INTO '.$quotedTable.' ('.implode(',', array_map($identifier, $columns)).") VALUES\n".implode(",\n", $rows).";\n";
                    $rows = [];
                }
            }
            if ($rows) echo 'INSERT INTO '.$quotedTable.' ('.implode(',', array_map($identifier, $columns)).") VALUES\n".implode(",\n", $rows).";\n";
            echo "\n";
        }
        echo "SET FOREIGN_KEY_CHECKS=1;\n";
    }

    public function createUser() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $username = sanitize_input($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        
        $userSession = Auth::user();
        $roleId = intval($_POST['role_id'] ?? 4);
        if ($userSession['role'] === 'HR Manager') {
            $roleId = 4;
        }

        $empId = !empty($_POST['employee_id']) ? intval($_POST['employee_id']) : null;

        if (empty($username)) {
            $this->json('error', 'Username is required.');
        }

        if (strlen($password) < 8) {
            $this->json('error', 'Password must be at least 8 characters.');
        }

        $userModel = new User();
        if (!$userModel->db->fetchOne("SELECT id FROM roles WHERE id = ?", [$roleId])) {
            $this->json('error', 'Invalid account role.');
        }
        if ($empId && $userModel->db->fetchOne("SELECT id FROM users WHERE employee_id = ?", [$empId])) {
            $this->json('error', 'This employee already has a linked user account.');
        }
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
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        $userModel = new User();
        $u = $this->loadManagedUser($userId);
        $this->assertCanManageUser($u, 'change the status of');
        if ($userId === intval(Auth::user()['id'])) $this->json('error', 'You cannot disable your own active session.');

        $newStatus = $u['is_active'] ? 0 : 1;
        $userModel->update($userId, ['is_active' => $newStatus]);

        $statusStr = $newStatus ? 'enabled' : 'disabled';
        AuditLogger::log('TOGGLE_USER_STATUS', 'System Admin', "User ID {$userId} ({$u['username']}) was {$statusStr}");
        $this->json('success', "User account '{$u['username']}' is now {$statusStr}!");
    }

    public function updateUser() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
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
        $user = $this->loadManagedUser($userId);
        $this->assertCanManageUser($user, 'edit');
        if (Auth::hasRole(['HR Manager']) && !Auth::hasRole(['Super Admin'])) $roleId = 4;
        if (!$userModel->db->fetchOne("SELECT id FROM roles WHERE id = ?", [$roleId])) $this->json('error', 'Invalid account role.');
        if ($empId && $userModel->db->fetchOne("SELECT id FROM users WHERE employee_id = ? AND id <> ?", [$empId, $userId])) {
            $this->json('error', 'This employee already has a linked user account.');
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
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId = intval($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        $userModel = new User();
        $user = $this->loadManagedUser($userId);
        $this->assertCanManageUser($user, 'delete');
        if ($userId === intval(Auth::user()['id'])) $this->json('error', 'You cannot delete your own active account.');
        if ($user['username'] === 'admin' || intval($user['id']) === 1) {
            $this->json('error', 'The primary system admin account cannot be deleted.');
        }

        $userModel->delete($userId);
        AuditLogger::log('DELETE_USER', 'System Admin', "Deleted user account '{$user['username']}' (ID: {$userId})");
        $this->json('success', "User account '{$user['username']}' deleted successfully!");
    }

    public function resetPassword() {
        Auth::requireRole(['Super Admin', 'HR Manager']);
        Auth::requireMethod('POST');
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $userId   = intval($_POST['user_id'] ?? 0);
        $newPass  = (string)($_POST['new_password'] ?? '');

        if ($userId <= 0) {
            $this->json('error', 'Invalid user ID.');
        }

        if (strlen($newPass) < 8) {
            $this->json('error', 'Password must be at least 8 characters.');
        }

        $userModel = new User();
        $user = $this->loadManagedUser($userId);
        $this->assertCanManageUser($user, 'reset the password of');

        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $userModel->update($userId, ['password' => $hash]);

        AuditLogger::log('RESET_PASSWORD', 'System Admin', "Reset password for user '{$user['username']}'");
        $this->json('success', "Password for '{$user['username']}' was reset successfully.");
    }

    private function loadManagedUser($userId) {
        $userModel = new User();
        $user = $userModel->db->fetchOne("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?", [intval($userId)]);
        if (!$user) $this->json('error', 'User account not found.', [], 404);
        return $user;
    }

    private function assertCanManageUser($target, $action) {
        if (Auth::hasRole(['HR Manager']) && !Auth::hasRole(['Super Admin']) && $target['role_name'] !== 'Employee') {
            Auth::deny("HR Managers cannot {$action} privileged or management accounts.");
        }
    }
}

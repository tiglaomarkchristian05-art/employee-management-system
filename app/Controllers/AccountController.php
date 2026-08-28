<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Employee.php';

class AccountController extends Controller {
    public function profile() {
        Auth::requirePermission('view_own_profile');
        $employeeModel = new Employee();
        $profile = $employeeModel->getDetailsById(Auth::employeeId());
        if (!$profile) Auth::deny('Your employee profile could not be found.');
        $this->view('account/profile', ['profile' => $profile, 'user' => Auth::user()]);
    }

    public function notifications() {
        Auth::requirePermission('view_notifications');
        $db = Database::getInstance();
        $notifications = $db->fetchAll("SELECT action, module, description, created_at FROM audit_logs WHERE user_id = ? ORDER BY id DESC LIMIT 50", [Auth::user()['id']]);
        $this->view('account/notifications', ['notifications' => $notifications]);
    }
}

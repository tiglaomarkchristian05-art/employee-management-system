<?php
require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Employee.php';
require_once APP_PATH . 'Models/Notification.php';

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
        $notificationModel = new Notification();
        $pagination = $notificationModel->paginateForUser(Auth::user()['id'], (int)($_GET['p'] ?? 1), 15);
        $this->view('account/notifications', ['notifications' => $pagination['items'], 'pagination' => $pagination, 'unread_count' => $notificationModel->unreadCount(Auth::user()['id'])]);
    }

    public function openNotification() {
        Auth::requirePermission('view_notifications');
        $model=new Notification();$note=$model->getOwned((int)($_GET['id']??0),Auth::user()['id']);
        if(!$note){http_response_code(404);exit('Notification not found.');}
        $model->markOneRead($note['id'],Auth::user()['id']);
        $link=(string)($note['link']??'');
        if($link===''||!preg_match('/^index\.php\?page=[A-Za-z0-9_]+(?:&[A-Za-z0-9_]+=[A-Za-z0-9_%.-]+)*$/',$link))$link='index.php?page=notifications';
        header('Location: '.$link);exit;
    }

    public function markAllNotificationsRead() {
        Auth::requirePermission('view_notifications');Auth::requireMethod('POST');
        if(!verify_csrf_token($_POST['csrf_token']??''))Auth::deny('Invalid CSRF token.');
        (new Notification())->markAllRead(Auth::user()['id']);
        header('Location: index.php?page=notifications');exit;
    }
}

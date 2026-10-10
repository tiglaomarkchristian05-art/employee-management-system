<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/config/csrf.php';
require dirname(__DIR__) . '/config/security.php';
require dirname(__DIR__) . '/core/Database.php';
require dirname(__DIR__) . '/core/Model.php';
require dirname(__DIR__) . '/core/AuditLogger.php';
require dirname(__DIR__) . '/core/Auth.php';
require dirname(__DIR__) . '/core/Controller.php';
require dirname(__DIR__) . '/app/Controllers/AuthController.php';
require dirname(__DIR__) . '/app/Controllers/AdminController.php';

class CapturedResponse extends RuntimeException {
    public function __construct(public string $result, string $message, public int $httpCode) {
        parent::__construct($message);
    }
}
class TestAuthController extends AuthController {
    protected function json($status, $message = '', $data = [], $httpCode = 200) {
        throw new CapturedResponse($status, $message, $httpCode);
    }
}
class TestAdminController extends AdminController {
    protected function json($status, $message = '', $data = [], $httpCode = 200) {
        throw new CapturedResponse($status, $message, $httpCode);
    }
}
function checkMode(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
}

$db = Database::getInstance();
$pdo = $db->getConnection();
$pdo->beginTransaction();
try {
    $role = $db->fetchOne("SELECT id FROM roles WHERE name='Super Admin'");
    $username = 'mfa_toggle_test_' . bin2hex(random_bytes(4));
    $password = 'LocalTestPass!123';
    $id = (int)$db->insert('users', [
        'username'=>$username, 'password'=>password_hash($password, PASSWORD_BCRYPT),
        'role_id'=>(int)$role['id'], 'employee_id'=>null, 'is_active'=>1,
        'mfa_email'=>'mfa-test@example.invalid'
    ]);
    checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$id])['mfa_mode']==='email', 'new accounts default to email verification');

    $_SESSION = ['user_id'=>$id, 'role_name'=>'Super Admin', 'auth_version'=>1, 'last_activity_at'=>time(), 'username'=>$username];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $token = generate_csrf_token();
    $_POST = ['csrf_token'=>$token, 'mfa_mode'=>'off', 'current_password'=>'incorrect'];
    try { (new TestAdminController())->saveMfaMode(); throw new RuntimeException('Wrong password was accepted.'); }
    catch (CapturedResponse $response) { checkMode($response->result==='error', 'wrong password cannot disable MFA'); }
    checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$id])['mfa_mode']==='email', 'failed change leaves MFA enabled');

    $_POST['current_password'] = $password;
    try { (new TestAdminController())->saveMfaMode(); throw new RuntimeException('MFA disable did not return.'); }
    catch (CapturedResponse $response) { checkMode($response->result==='success', 'correct password can disable account MFA'); }
    checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$id])['mfa_mode']==='off', 'off mode is persisted');

    $_SESSION = [];
    $_POST = ['csrf_token'=>generate_csrf_token(), 'username'=>$username, 'password'=>$password];
    try { (new TestAuthController())->login(); throw new RuntimeException('Password-only login did not return.'); }
    catch (CapturedResponse $response) { checkMode($response->result==='success', 'off mode logs in without an email challenge'); }
    checkMode((int)($_SESSION['user_id']??0)===$id && empty($_SESSION['pending_mfa_challenge_id']), 'off login establishes a session without an OTP');

    $_POST = ['csrf_token'=>generate_csrf_token(), 'mfa_mode'=>'email'];
    try { (new TestAdminController())->saveMfaMode(); throw new RuntimeException('MFA enable did not return.'); }
    catch (CapturedResponse $response) { checkMode($response->result==='success', 'email verification can be turned back on'); }
    checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$id])['mfa_mode']==='email', 'email mode is persisted again');
    foreach (['HR Manager', 'Employee'] as $roleName) {
        $roleRow = $db->fetchOne('SELECT id FROM roles WHERE name=?', [$roleName]);
        $otherUsername = 'mfa_' . strtolower(str_replace(' ', '_', $roleName)) . '_' . bin2hex(random_bytes(3));
        $otherId = (int)$db->insert('users', [
            'username'=>$otherUsername, 'password'=>password_hash($password, PASSWORD_BCRYPT),
            'role_id'=>(int)$roleRow['id'], 'employee_id'=>null, 'is_active'=>1,
            'mfa_email'=>'mfa-test@example.invalid'
        ]);
        $_SESSION = ['user_id'=>$otherId, 'role_name'=>$roleName, 'auth_version'=>1, 'last_activity_at'=>time(), 'username'=>$otherUsername];
        $_POST = ['csrf_token'=>generate_csrf_token(), 'mfa_mode'=>'off', 'current_password'=>$password];
        try { (new TestAdminController())->saveMfaMode(); throw new RuntimeException($roleName . ' could not disable MFA.'); }
        catch (CapturedResponse $response) { checkMode($response->result==='success', $roleName . ' can disable own MFA'); }
        checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$otherId])['mfa_mode']==='off', $roleName . ' Off mode persisted');
        checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$id])['mfa_mode']==='email', 'another account remains unchanged');
        $_POST = ['csrf_token'=>generate_csrf_token(), 'mfa_mode'=>'email'];
        try { (new TestAdminController())->saveMfaMode(); throw new RuntimeException($roleName . ' could not enable MFA.'); }
        catch (CapturedResponse $response) { checkMode($response->result==='success', $roleName . ' can enable own MFA'); }
        checkMode($db->fetchOne('SELECT mfa_mode FROM users WHERE id=?', [$otherId])['mfa_mode']==='email', $roleName . ' Email mode persisted');
    }
    $pdo->rollBack();
    $_SESSION = [];
    echo "PASS: Admin, HR, and Employee can change only their own MFA mode; password confirmation and password-only login work.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION = [];
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}

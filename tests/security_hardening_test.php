<?php
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/config/security.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/Model.php';
require dirname(__DIR__).'/app/Models/User.php';

$db=Database::getInstance();$pdo=$db->getConnection();$pdo->beginTransaction();
try{
    if(SESSION_ADMIN_IDLE_SECONDS!==300||SESSION_EMPLOYEE_IDLE_SECONDS!==600)throw new RuntimeException('Role-based inactivity timeout configuration is incorrect.');
    foreach(['failed_login_attempts','locked_until','last_failed_login_at','auth_version','mfa_email'] as $column){
        if(!$db->fetchOne("SHOW COLUMNS FROM users LIKE '{$column}'"))throw new RuntimeException("Missing users.{$column}");
    }
    if(!$db->fetchOne("SHOW TABLES LIKE 'mfa_login_challenges'"))throw new RuntimeException('Missing MFA challenge table.');
    $role=$db->fetchOne("SELECT id FROM roles WHERE name='Employee' LIMIT 1");
    $id=$db->insert('users',['username'=>'security_test_'.bin2hex(random_bytes(4)),'password'=>password_hash('ValidPass!123',PASSWORD_BCRYPT),'role_id'=>$role['id'],'employee_id'=>null,'is_active'=>1]);
    $users=new User();
    for($i=1;$i<=LOGIN_MAX_ATTEMPTS;$i++)$state=$users->recordFailedLogin($id);
    if((int)$state['failed_login_attempts']!==LOGIN_MAX_ATTEMPTS||strtotime($state['locked_until'])<=time())throw new RuntimeException('Five-attempt lockout failed.');
    $db->query("UPDATE users SET locked_until=DATE_SUB(NOW(),INTERVAL 1 SECOND) WHERE id=?",[$id]);
    if(!$users->clearExpiredLock($id))throw new RuntimeException('Automatic lockout expiry failed.');
    $state=$users->find($id);if((int)$state['failed_login_attempts']!==0||$state['locked_until']!==null)throw new RuntimeException('Expired lock was not reset.');
    $version=(int)$state['auth_version'];if($users->invalidateSessions($id)!==$version+1)throw new RuntimeException('Session version invalidation failed.');
    $otp=(string)random_int(100000,999999);$challenge=$db->insert('mfa_login_challenges',['user_id'=>$id,'otp_hash'=>password_hash($otp,PASSWORD_DEFAULT),'expires_at'=>date('Y-m-d H:i:s',time()+300),'attempts'=>0,'max_attempts'=>5,'resend_count'=>0,'last_sent_at'=>date('Y-m-d H:i:s')]);
    $stored=$db->fetchOne('SELECT * FROM mfa_login_challenges WHERE id=?',[$challenge]);
    if($stored['otp_hash']===$otp||!password_verify($otp,$stored['otp_hash']))throw new RuntimeException('OTP hashing failed.');
    if($db->query('UPDATE mfa_login_challenges SET consumed_at=NOW() WHERE id=? AND consumed_at IS NULL',[$challenge])->rowCount()!==1)throw new RuntimeException('OTP consumption failed.');
    if($db->query('UPDATE mfa_login_challenges SET consumed_at=NOW() WHERE id=? AND consumed_at IS NULL',[$challenge])->rowCount()!==0)throw new RuntimeException('OTP reuse protection failed.');
    echo "PASS: lockout, automatic expiry/reset, auth-version invalidation, hashed OTP, single-use OTP, and role-based inactivity limits.\n";
    $pdo->rollBack();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'FAIL: '.$e->getMessage()."\n");exit(1);}

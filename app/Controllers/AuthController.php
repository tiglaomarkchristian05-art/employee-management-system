<?php
require_once ROOT_PATH.'core/Controller.php';
require_once ROOT_PATH.'core/Mailer.php';
require_once APP_PATH.'Models/User.php';

class AuthController extends Controller {

    public function login(){
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        if(Auth::check())redirect(BASE_URL.'?page=dashboard');
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            unset($_SESSION['login_after_lockout_expiration']);
            if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid security request. Please refresh.',[],403);
            $identifier=sanitize_input($_POST['username']??'');$password=(string)($_POST['password']??'');
            if($identifier===''||$password==='')$this->json('error','Invalid credentials.',[],422);
            if((int)($_SESSION['anonymous_login_locked_until']??0)>time()){
                AuditLogger::log('LOGIN_ATTEMPT_DURING_LOCKOUT','Authentication','A login was rejected during a temporary request-session lockout.');
                $this->json('error','Too many failed login attempts. Please try again later.',[],429);
            }
            if(isset($_SESSION['anonymous_login_locked_until'])&&(int)$_SESSION['anonymous_login_locked_until']<=time())unset($_SESSION['anonymous_login_locked_until'],$_SESSION['anonymous_login_failures']);
            $users=new User();$user=$users->findByUsername($identifier);$expired=false;
            if($user){
                $expired=$users->clearExpiredLock($user['id']);
                if($expired)$user=$users->findByUsername($identifier);
                if(!empty($user['locked_until'])&&strtotime($user['locked_until'])>time()){
                    AuditLogger::log('LOGIN_ATTEMPT_DURING_LOCKOUT','Authentication','A login was rejected during a temporary account lockout.',$user['id']);
                    $this->json('error','Too many failed login attempts. Please try again later.',[],429);
                }
            }
            $valid=$user&&(int)$user['is_active']===1&&verify_password($password,$user['password']);
            if(!$valid){
                $locked=false;$attempt=1;
                if($user){$state=$users->recordFailedLogin($user['id']);$attempt=min(LOGIN_MAX_ATTEMPTS,(int)$state['failed_login_attempts']);$locked=!empty($state['locked_until'])&&strtotime($state['locked_until'])>time();AuditLogger::log('FAILED_LOGIN_ATTEMPT','Authentication','Invalid credentials were submitted.',$user['id']);if($locked)AuditLogger::log('ACCOUNT_TEMPORARILY_LOCKED','Authentication','Account temporarily locked after repeated failed login attempts.',$user['id']);}
                else {$_SESSION['anonymous_login_failures']=min(LOGIN_MAX_ATTEMPTS,(int)($_SESSION['anonymous_login_failures']??0)+1);$attempt=(int)$_SESSION['anonymous_login_failures'];$locked=$attempt>=LOGIN_MAX_ATTEMPTS;if($locked)$_SESSION['anonymous_login_locked_until']=time()+LOGIN_LOCKOUT_MINUTES*60;AuditLogger::log('FAILED_LOGIN_ATTEMPT','Authentication','Invalid credentials were submitted.');if($locked)AuditLogger::log('ACCOUNT_TEMPORARILY_LOCKED','Authentication','Request session temporarily locked after repeated failed login attempts.');}
                if($locked)$message='Too many failed login attempts. Account temporarily locked for '.LOGIN_LOCKOUT_MINUTES.' minutes.';
                else{$remaining=max(0,LOGIN_MAX_ATTEMPTS-$attempt);$message='Invalid credentials. Failed attempt '.$attempt.' of '.LOGIN_MAX_ATTEMPTS.'. '.$remaining.' attempt'.($remaining===1?'':'s').' remaining before temporary lockout.';}
                $this->json('error',$message,['failed_attempt'=>$attempt,'max_attempts'=>LOGIN_MAX_ATTEMPTS],$locked?429:401);
            }
            unset($_SESSION['anonymous_login_failures'],$_SESSION['anonymous_login_locked_until']);
            $users->resetLoginFailures($user['id']);
            if($expired)$_SESSION['login_after_lockout_expiration']=1;
            $this->startMfa($user);
        }
        $this->view('auth/login');
    }

    public function verifyMfa(){
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        if(Auth::check())redirect(BASE_URL.'?page=dashboard');
        $challenge=$this->pendingChallenge();if(!$challenge)redirect(BASE_URL.'?page=login');
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid security request. Please refresh.',[],403);
            if($challenge['consumed_at']!==null||(int)$challenge['attempts']>=(int)$challenge['max_attempts']){unset($_SESSION['pending_mfa_challenge_id']);$this->json('error','Verification limit reached. Please sign in again.',['redirect'=>BASE_URL.'?page=login'],429);}
            if(strtotime($challenge['expires_at'])<=time()){AuditLogger::log('MFA_OTP_EXPIRED','Authentication','An expired MFA challenge was rejected.',$challenge['user_id']);$this->json('error','Verification code expired. Request a new code.',[],410);}
            $otp=preg_replace('/\D/','',(string)($_POST['otp']??''));$db=Database::getInstance();
            $db->query('UPDATE mfa_login_challenges SET attempts=attempts+1 WHERE id=? AND consumed_at IS NULL',[$challenge['id']]);
            if(strlen($otp)!==6||!password_verify($otp,$challenge['otp_hash'])){
                AuditLogger::log('MFA_VERIFICATION_FAILURE','Authentication','An invalid MFA verification code was submitted.',$challenge['user_id']);$remaining=max(0,(int)$challenge['max_attempts']-((int)$challenge['attempts']+1));
                if(!$remaining)unset($_SESSION['pending_mfa_challenge_id']);
                $this->json('error',$remaining?'Invalid verification code.':'Verification limit reached. Please sign in again.',['remaining_attempts'=>$remaining,'redirect'=>$remaining?null:BASE_URL.'?page=login'],$remaining?401:429);
            }
            if($db->query('UPDATE mfa_login_challenges SET consumed_at=NOW() WHERE id=? AND consumed_at IS NULL AND expires_at>NOW()',[$challenge['id']])->rowCount()!==1)$this->json('error','Verification code is no longer valid. Please sign in again.',[],401);
            $user=(new User())->findByUsername($challenge['username']);
            if(!$user||!(int)$user['is_active']){unset($_SESSION['pending_mfa_challenge_id']);$this->json('error','Unable to complete sign in.',[],401);}
            AuditLogger::log('MFA_VERIFICATION_SUCCESS','Authentication','MFA challenge completed successfully.',$user['id']);$this->completeLogin($user);
        }
        $this->view('auth/mfa',['masked_email'=>$this->maskEmail($challenge['email'])]);
    }

    public function resendMfa(){
        Auth::requireMethod('POST');if(Auth::check())$this->json('error','Already authenticated.',[],409);
        if(!verify_csrf_token($_POST['csrf_token']??''))$this->json('error','Invalid security request. Please refresh.',[],403);
        $c=$this->pendingChallenge();if(!$c)$this->json('error','Sign-in verification is no longer available. Please sign in again.',['redirect'=>BASE_URL.'?page=login'],401);
        if((int)$c['resend_count']>=MFA_MAX_RESENDS)$this->json('error','Resend limit reached. Please sign in again.',[],429);
        $elapsed=time()-strtotime($c['last_sent_at']);if($elapsed<MFA_RESEND_COOLDOWN_SECONDS)$this->json('error','Please wait before requesting another code.',['retry_after'=>MFA_RESEND_COOLDOWN_SECONDS-$elapsed],429);
        $code=(string)random_int(100000,999999);
        if(!Mailer::sendSecurityCode($c['email'],$code)){AuditLogger::log('MFA_DELIVERY_FAILURE','Authentication','MFA resend could not be delivered.',$c['user_id']);$this->json('error','Unable to send a verification code. Please try again later.',[],503);}
        Database::getInstance()->query('UPDATE mfa_login_challenges SET otp_hash=?,expires_at=DATE_ADD(NOW(),INTERVAL ? MINUTE),attempts=0,resend_count=resend_count+1,last_sent_at=NOW() WHERE id=? AND consumed_at IS NULL',[password_hash($code,PASSWORD_DEFAULT),MFA_OTP_TTL_MINUTES,$c['id']]);
        AuditLogger::log('MFA_RESEND','Authentication','A replacement MFA challenge was requested.',$c['user_id']);$this->json('success','A new verification code was sent.');
    }

    public function logout(){Auth::requireMethod('POST');if(!verify_csrf_token($_POST['csrf_token']??''))Auth::deny('Invalid logout request.');if(Auth::check())AuditLogger::log('LOGOUT','Authentication','User logged out.');Auth::endSession();redirect(BASE_URL.'?page=login');}

    private function startMfa(array $user){
        $email=trim((string)($user['email']??''));if(!filter_var($email,FILTER_VALIDATE_EMAIL)){AuditLogger::log('MFA_DELIVERY_FAILURE','Authentication','Account has no deliverable registered email.',$user['id']);$this->json('error','Unable to send a verification code. Contact the system administrator.',[],503);}
        $code=(string)random_int(100000,999999);$db=Database::getInstance();$db->query('UPDATE mfa_login_challenges SET consumed_at=NOW() WHERE user_id=? AND consumed_at IS NULL',[$user['id']]);
        $id=$db->insert('mfa_login_challenges',['user_id'=>$user['id'],'otp_hash'=>password_hash($code,PASSWORD_DEFAULT),'expires_at'=>date('Y-m-d H:i:s',time()+MFA_OTP_TTL_MINUTES*60),'attempts'=>0,'max_attempts'=>MFA_MAX_ATTEMPTS,'resend_count'=>0,'last_sent_at'=>date('Y-m-d H:i:s')]);
        if(!Mailer::sendSecurityCode($email,$code)){$db->query('UPDATE mfa_login_challenges SET consumed_at=NOW() WHERE id=?',[$id]);AuditLogger::log('MFA_DELIVERY_FAILURE','Authentication','MFA challenge could not be delivered.',$user['id']);$this->json('error','Unable to send a verification code. Please try again later.',[],503);}
        session_regenerate_id(true);foreach(['user_id','role_name','role_id','employee_id','auth_version'] as $key)unset($_SESSION[$key]);$_SESSION['pending_mfa_challenge_id']=(int)$id;
        AuditLogger::log('MFA_CHALLENGE_GENERATED','Authentication','A sign-in MFA challenge was generated.',$user['id']);$this->json('success','Verification required.',['redirect'=>BASE_URL.'?page=mfa_verify']);
    }

    private function completeLogin(array $u){
        session_regenerate_id(true);unset($_SESSION['pending_mfa_challenge_id']);$_SESSION['user_id']=(int)$u['id'];$_SESSION['username']=$u['username'];$_SESSION['full_name']=!empty($u['first_name'])?trim($u['first_name'].' '.$u['last_name']):$u['username'];$_SESSION['role_name']=$u['role_name'];$_SESSION['role_id']=(int)$u['role_id'];$_SESSION['employee_id']=$u['employee_id'];$_SESSION['email']=$u['email']??'';$_SESSION['department_name']=$u['department_name']??'General';$_SESSION['avatar']=$u['photo']??'default.png';$_SESSION['is_active']=(int)$u['is_active'];$_SESSION['auth_version']=(int)$u['auth_version'];$_SESSION['last_activity_at']=time();
        (new User())->update($u['id'],['last_login'=>date('Y-m-d H:i:s')]);AuditLogger::log('SUCCESSFUL_LOGIN','Authentication','User logged in successfully.',$u['id']);
        if(!empty($_SESSION['login_after_lockout_expiration'])){unset($_SESSION['login_after_lockout_expiration']);AuditLogger::log('LOGIN_AFTER_LOCKOUT_EXPIRATION','Authentication','Login completed after the temporary lockout expired.',$u['id']);}
        $this->json('success','Login successful! Redirecting to dashboard...',['redirect'=>BASE_URL.'?page=dashboard']);
    }
    private function pendingChallenge(){if(!$id=(int)($_SESSION['pending_mfa_challenge_id']??0))return null;return Database::getInstance()->fetchOne("SELECT c.*,u.username,u.is_active,COALESCE(NULLIF(u.mfa_email,''),e.email) AS email FROM mfa_login_challenges c JOIN users u ON u.id=c.user_id LEFT JOIN employees e ON e.id=u.employee_id WHERE c.id=?",[$id]);}
    private function maskEmail($email){[$local,$domain]=array_pad(explode('@',(string)$email,2),2,'');return substr($local,0,1).str_repeat('*',max(2,strlen($local)-1)).'@'.$domain;}
}

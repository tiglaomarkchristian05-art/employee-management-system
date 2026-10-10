<?php
ini_set('session.save_path',sys_get_temp_dir());
require dirname(__DIR__).'/config/config.php';
require dirname(__DIR__).'/config/database.php';
require dirname(__DIR__).'/core/Database.php';
require dirname(__DIR__).'/core/AuditLogger.php';
require dirname(__DIR__).'/core/Auth.php';

function rbacCheck($condition,$message){if(!$condition)throw new RuntimeException('FAIL: '.$message);echo "PASS: {$message}\n";}
function assumeRole($name,$employeeId=null){$_SESSION=['user_id'=>999,'username'=>'rbac-test','role_name'=>$name,'role_id'=>999,'employee_id'=>$employeeId,'auth_version'=>1];}

$db=Database::getInstance();
$roles=$db->fetchAll('SELECT name FROM roles ORDER BY id');
$roleNames=array_column($roles,'name');
foreach(['Super Admin','HR Manager','Department Head','Employee'] as $role)rbacCheck(in_array($role,$roleNames,true),"{$role} role exists");
$hr=$db->fetchOne("SELECT u.id,u.username,u.is_active FROM users u JOIN roles r ON r.id=u.role_id WHERE r.name='HR Manager' ORDER BY u.id LIMIT 1");
rbacCheck((bool)$hr&&$hr['is_active'], 'an active HR Manager test account exists');

assumeRole('Super Admin');
rbacCheck(Auth::can('manage_users')&&Auth::can('manage_settings')&&Auth::can('view_audit_logs'),'System Admin has user, settings, and audit access');
foreach(['manage_training','manage_documents','manage_compliance','manage_benefits','manage_loans','manage_separation','view_reports'] as $permission)rbacCheck(!Auth::can($permission),"System Admin does not inherit {$permission}");
rbacCheck(!Auth::hasRole('HR Manager'),'role matching does not treat System Admin as HR');

assumeRole('HR Manager',2);
foreach(['manage_employees','manage_training','manage_documents','manage_compliance','manage_benefits','manage_loans','manage_separation','view_reports'] as $permission)rbacCheck(Auth::can($permission),"HR has {$permission}");
foreach(['manage_users','manage_roles','manage_settings','view_security'] as $permission)rbacCheck(!Auth::can($permission),"HR is denied {$permission}");

assumeRole('Employee',4);
foreach(['view_own_training','upload_own_documents','submit_claim','submit_loan','submit_separation','view_own_clearance'] as $permission)rbacCheck(Auth::can($permission),"Employee has self-service permission {$permission}");
foreach(['manage_training','manage_documents','manage_benefits','manage_loans','manage_separation','manage_users'] as $permission)rbacCheck(!Auth::can($permission),"Employee is denied {$permission}");

assumeRole('Department Head',3);
rbacCheck(Auth::can('approve_department_clearance'),'Department Head retains assigned-clearance permission');
rbacCheck(!Auth::can('manage_separation')&&!Auth::can('manage_users'),'Department Head does not inherit HR or System Admin privileges');

echo "All RBAC authorization scenarios passed. Existing HR test user: {$hr['username']} (ID {$hr['id']}).\n";

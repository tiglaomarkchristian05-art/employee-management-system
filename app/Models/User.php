<?php

require_once ROOT_PATH . 'core/Model.php';

class User extends Model {
    protected $table = 'users';

    public function findByUsername($usernameOrEmail) {
        // Map Moses Group demo email credentials seamlessly
        if (in_array($usernameOrEmail, ['admin@ismers.local', 'admin@mosesgroup.ph', 'admin@apexhr.com'])) $usernameOrEmail = 'admin';
        if (in_array($usernameOrEmail, ['hr@mosesgroup.ph', 'hr@apexhr.com'])) $usernameOrEmail = 'hr_manager';
        if (in_array($usernameOrEmail, ['employee@mosesgroup.ph', 'employee@apexhr.com'])) $usernameOrEmail = 'employee';

        $sql = "SELECT u.*, r.name as role_name, e.first_name, e.last_name, COALESCE(NULLIF(u.mfa_email,''),e.email) AS email, e.department_id, d.name as department_name, e.photo
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN employees e ON u.employee_id = e.id
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE u.username = ? OR e.email = ?";
        return $this->db->fetchOne($sql, [$usernameOrEmail, $usernameOrEmail]);
    }

    public function getAllUsersWithDetails() {
        $sql = "SELECT u.*, r.name as role_name, e.employee_code, e.first_name, e.last_name, e.email as emp_email, COALESCE(NULLIF(u.mfa_email,''),e.email) AS effective_mfa_email
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN employees e ON u.employee_id = e.id
                ORDER BY u.id DESC";
        return $this->db->fetchAll($sql);
    }

    public function clearExpiredLock($userId) {
        return $this->db->query(
            "UPDATE users SET failed_login_attempts=0, locked_until=NULL, last_failed_login_at=NULL WHERE id=? AND locked_until IS NOT NULL AND locked_until<=NOW()",
            [(int)$userId]
        )->rowCount() > 0;
    }

    public function recordFailedLogin($userId) {
        $this->db->query(
            "UPDATE users
             SET failed_login_attempts=failed_login_attempts+1,
                 last_failed_login_at=NOW(),
                 locked_until=CASE WHEN failed_login_attempts+1>=? THEN DATE_ADD(NOW(), INTERVAL ? MINUTE) ELSE locked_until END
             WHERE id=?",
            [LOGIN_MAX_ATTEMPTS, LOGIN_LOCKOUT_MINUTES, (int)$userId]
        );
        return $this->find((int)$userId);
    }

    public function resetLoginFailures($userId) {
        $this->db->query("UPDATE users SET failed_login_attempts=0, locked_until=NULL, last_failed_login_at=NULL WHERE id=?", [(int)$userId]);
    }

    public function invalidateSessions($userId) {
        $this->db->query("UPDATE users SET auth_version=auth_version+1 WHERE id=?", [(int)$userId]);
        return (int)$this->db->fetchOne("SELECT auth_version FROM users WHERE id=?", [(int)$userId])['auth_version'];
    }
}

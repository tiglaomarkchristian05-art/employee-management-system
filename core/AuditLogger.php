<?php

require_once __DIR__ . '/Database.php';

class AuditLogger {
    public static function log($action, $module, $description = '', $user_id = null) {
        $db = Database::getInstance();
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        try {
            $db->insert('audit_logs', [
                'user_id'     => $user_id,
                'action'      => $action,
                'module'      => $module,
                'description' => $description,
                'ip_address'  => $ip_address,
                'user_agent'  => substr($user_agent, 0, 255),
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}

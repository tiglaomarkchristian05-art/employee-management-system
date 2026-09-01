<?php

require_once __DIR__ . '/Database.php';

class AuditLogger {
    public static function log($action, $module, $description = '', $user_id = null, array $context = []) {
        $db = Database::getInstance();
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $role = $_SESSION['role_name'] ?? null;
        $employeeId = isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : null;
        $recordType = $context['record_type'] ?? self::inferRecordType($action, $module);
        $recordId = isset($context['record_id']) ? (int)$context['record_id'] : self::inferRecordId($description);
        $oldValue = self::safeJson($context['old_value'] ?? null);
        $newValue = self::safeJson($context['new_value'] ?? null);

        try {
            $db->insert('audit_logs', [
                'user_id'     => $user_id,
                'role_name'   => $role,
                'employee_id' => $employeeId ?: null,
                'action'      => $action,
                'module'      => $module,
                'record_type' => $recordType,
                'record_id'   => $recordId ?: null,
                'description' => $description,
                'old_value'   => $oldValue,
                'new_value'   => $newValue,
                'ip_address'  => $ip_address,
                'user_agent'  => substr($user_agent, 0, 255),
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage());
        }
    }

    private static function inferRecordId($description) {
        if (preg_match('/(?:ID|record|registration)\s*[:#]?\s*(\d+)/i', (string)$description, $match)) return (int)$match[1];
        return null;
    }

    private static function inferRecordType($action, $module) {
        $value=strtolower($action.' '.$module);
        foreach(['training','document','contract','government','contribution','benefit','loan','clearance','separation','employee','user'] as $type)if(strpos($value,$type)!==false)return $type;
        return 'system';
    }

    private static function safeJson($value) {
        if($value===null||$value==='')return null;
        if(!is_array($value))$value=['value'=>$value];
        $blocked=['password','token','secret','authorization','cookie','file_path','document_file','supporting_file'];
        $clean=[];foreach($value as $key=>$item){if(in_array(strtolower((string)$key),$blocked,true))continue;$clean[$key]=is_scalar($item)||$item===null?$item:'[complex value]';}
        return $clean?json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null;
    }
}

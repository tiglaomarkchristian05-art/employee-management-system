<?php

if (!function_exists('env_value')) {
    function env_value($key, $default = null) {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

define('APP_ENV', strtolower((string) env_value('APP_ENV', 'local')));
define('APP_DEBUG', filter_var(env_value('APP_DEBUG', APP_ENV === 'local' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN));
define('APP_NAME', 'Moses HRMS');
define('APP_VERSION', '2.6.0');
define('APP_COMPANY', 'Moses Group of Companies');
$configuredUrl = trim((string) env_value('APP_URL', ''));
if ($configuredUrl === '') {
    $forwardedProto = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]);
    $scheme = $forwardedProto !== '' ? $forwardedProto : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDirectory = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])) : '';
    $scriptDirectory = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
    $configuredUrl = $scheme . '://' . $host . $scriptDirectory;
}
define('BASE_URL', rtrim($configuredUrl, '/') . '/');

define('ROOT_PATH', dirname(__DIR__) . '/');
define('APP_PATH', ROOT_PATH . 'app/');
define('UPLOAD_PATH', ROOT_PATH . 'assets/uploads/');
define('DOC_UPLOAD_PATH', UPLOAD_PATH . 'documents/');
define('AVATAR_UPLOAD_PATH', UPLOAD_PATH . 'avatars/');
define('CERT_UPLOAD_PATH', UPLOAD_PATH . 'certificates/');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (APP_ENV === 'production' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

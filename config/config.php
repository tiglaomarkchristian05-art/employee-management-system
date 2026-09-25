<?php

// Load local development values from the ignored project .env file. Real
// server/HostForge variables always take precedence and are never overwritten.
$localEnvFile = dirname(__DIR__) . '/.env';
if (is_file($localEnvFile) && is_readable($localEnvFile)) {
    foreach (file($localEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

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
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 5);
define('MFA_OTP_TTL_MINUTES', 5);
define('MFA_MAX_ATTEMPTS', 5);
define('MFA_RESEND_COOLDOWN_SECONDS', 60);
define('MFA_MAX_RESENDS', 3);
define('SESSION_ADMIN_IDLE_SECONDS', 300);
define('SESSION_EMPLOYEE_IDLE_SECONDS', 600);
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

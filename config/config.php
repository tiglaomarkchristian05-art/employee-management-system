<?php

define('APP_NAME', 'Moses HRMS');
define('APP_VERSION', '2.6.0');
define('APP_COMPANY', 'Moses Group of Companies');
define('BASE_URL', 'http://localhost/employee/');

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
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', 1);

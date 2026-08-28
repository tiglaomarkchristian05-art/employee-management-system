<?php

$databaseUrl = getenv('DATABASE_URL') ?: '';
$databaseParts = $databaseUrl !== '' ? parse_url($databaseUrl) : [];

define('DB_HOST', getenv('DB_HOST') ?: ($databaseParts['host'] ?? '127.0.0.1'));
define('DB_USER', getenv('DB_USERNAME') ?: getenv('DB_USER') ?: (isset($databaseParts['user']) ? rawurldecode($databaseParts['user']) : 'root'));
define('DB_PASS', getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : (isset($databaseParts['pass']) ? rawurldecode($databaseParts['pass']) : '')));
define('DB_NAME', getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: (isset($databaseParts['path']) ? ltrim($databaseParts['path'], '/') : 'apex_hrms'));
define('DB_PORT', getenv('DB_PORT') ?: ($databaseParts['port'] ?? '3306'));
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

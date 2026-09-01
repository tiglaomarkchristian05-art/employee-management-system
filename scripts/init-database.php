<?php

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

function deploymentSql($path) {
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('Unable to read database file: ' . basename($path));
    }

    // HostForge provisions the database; use its assigned name rather than
    // attempting to create or switch to the local apex_hrms database.
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;\s*$/mi', '', $sql);
    return preg_replace('/^\s*USE\s+`?[^`;]+`?\s*;\s*$/mi', '', $sql);
}

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$pdo = null;
$lastError = null;
$initialAdminPassword = getenv('INITIAL_ADMIN_PASSWORD') ?: '';
$initialUserPassword = getenv('INITIAL_USER_PASSWORD') ?: '';

for ($attempt = 1; $attempt <= 12; $attempt++) {
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        break;
    } catch (PDOException $exception) {
        $lastError = $exception;
        fwrite(STDOUT, "Database is not ready (attempt {$attempt}/12).\n");
        sleep(5);
    }
}

if (!$pdo) {
    fwrite(STDERR, 'Unable to connect to the deployment database: ' . ($lastError ? $lastError->getMessage() : 'unknown error') . PHP_EOL);
    exit(1);
}

$root = dirname(__DIR__);
$pdo->exec(deploymentSql($root . '/database/schema.sql'));

$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$isNewInstallation = $userCount === 0;
if ($isNewInstallation && APP_ENV === 'production' && (strlen($initialAdminPassword) < 12 || strlen($initialUserPassword) < 12)) {
    fwrite(STDERR, "Set INITIAL_ADMIN_PASSWORD and INITIAL_USER_PASSWORD to private values of at least 12 characters for the initial deployment.\n");
    exit(1);
}
if ($isNewInstallation) {
    $pdo->exec(deploymentSql($root . '/database/seed.sql'));
    fwrite(STDOUT, "Database seed data installed.\n");
    if ($initialAdminPassword !== '') {
        $statement = $pdo->prepare('UPDATE users SET password = ? WHERE username = ?');
        $statement->execute([password_hash($initialAdminPassword, PASSWORD_BCRYPT, ['cost' => 12]), 'admin']);
    }
    if ($initialUserPassword !== '') {
        $passwordHash = password_hash($initialUserPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $statement = $pdo->prepare("UPDATE users SET password = ? WHERE username IN ('hr_manager', 'mchen', 'employee')");
        $statement->execute([$passwordHash]);
    }
}

$pdo->exec(deploymentSql($root . '/database/allowances_claims_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/training_workflow_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/document_contract_workflow_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/government_compliance_workflow_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/benefits_workflow_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/phase8_loans.sql'));
$pdo->exec(deploymentSql($root . '/database/separation_clearance_workflow_patch.sql'));
$pdo->exec(deploymentSql($root . '/database/phase11_notifications.sql'));
$pdo->exec(deploymentSql($root . '/database/phase12_audit_trail.sql'));
$pdo->exec(deploymentSql($root . '/database/phase15_ai_training.sql'));
fwrite(STDOUT, "Database schema is ready.\n");

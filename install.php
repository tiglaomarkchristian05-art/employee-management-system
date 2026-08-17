<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$message = '';
$status = '';

if (isset($_POST['install'])) {
    try {
        // Connect to MySQL server without database selected first
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create Database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `" . DB_NAME . "`;");

        // Run Schema
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schemaSql);

        // Run Seed
        $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
        $pdo->exec($seedSql);

        // Run allowances and reimbursements expansion
        $patchSql = file_get_contents(__DIR__ . '/database/allowances_claims_patch.sql');
        $pdo->exec($patchSql);

        $status = 'success';
        $message = 'Database and seed data successfully initialized! You can now log into ApexHR Enterprise.';
    } catch (Exception $e) {
        $status = 'danger';
        $message = 'Installation Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexHR Installation & Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #F5FAFC 0%, #E3F2F9 100%);
            color: #223344;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-card {
            background: #ffffff;
            border: 1px solid #D7E8EF;
            border-radius: 16px;
            box-shadow: 0 10px 35px rgba(34, 51, 68, 0.08);
            max-width: 600px;
            width: 100%;
            padding: 2.5rem;
        }
        .brand-logo {
            font-size: 2rem;
            font-weight: 700;
            color: #2B7A9E;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="install-card text-center">
        <div class="brand-logo justify-content-center">
            <i class="fa-solid fa-layer-group"></i> ApexHR Enterprise
        </div>
        <h4 class="fw-bold mb-3">Database Setup & Initializer</h4>
        <p class="text-secondary mb-4">Click below to automatically create the MySQL database schema and seed standard enterprise sample data.</p>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $status; ?> text-start mb-4">
                <i class="fa-solid fa-circle-info me-2"></i> <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($status === 'success'): ?>
            <div class="card bg-light text-start border p-3 mb-4">
                <h6 class="fw-bold mb-2" style="color: #2B7A9E;"><i class="fa-solid fa-key me-1"></i> Default Demo Credentials:</h6>
                <ul class="mb-0 ps-3 text-secondary">
                    <li><strong>Super Admin:</strong> Username: <code>admin</code> | Password: <code>admin123</code></li>
                    <li><strong>HR Manager:</strong> Username: <code>hr_manager</code> | Password: <code>user123</code></li>
                    <li><strong>Employee:</strong> Username: <code>employee</code> | Password: <code>user123</code></li>
                </ul>
            </div>
            <a href="index.php" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" style="background-color:#2B7A9E; border-color:#2B7A9E;"><i class="fa-solid fa-right-to-bracket me-2"></i> Launch HRMS Application</a>
        <?php else: ?>
            <form method="POST">
                <button type="submit" name="install" value="1" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm" style="background-color:#2B7A9E; border-color:#2B7A9E;">
                    <i class="fa-solid fa-database me-2"></i> Install & Initialize Database
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

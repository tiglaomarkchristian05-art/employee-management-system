<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-receipt text-warning me-2"></i> Enterprise Audit Trail & Security Logs</h4>
        <p class="text-secondary">Immutable log of all user actions, CRUD events, authentication attempts, and IP signatures</p>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="small text-secondary"><?= $log['created_at']; ?></td>
                        <td class="fw-bold text-light"><?= htmlspecialchars($log['username'] ?? 'System'); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['module']); ?></span></td>
                        <td><span class="badge badge-soft-info"><?= htmlspecialchars($log['action']); ?></span></td>
                        <td class="small text-light"><?= htmlspecialchars($log['description']); ?></td>
                        <td><code><?= htmlspecialchars($log['ip_address']); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <a href="index.php?page=documents" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Documents</a>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-file-contract text-warning me-2"></i> Employment Contracts & Expirations</h4>
            <p class="text-secondary mb-0">Employment, Probationary, Regularization, and Consultancy Contracts</p>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Contract Type</th>
                        <th>Start Date</th>
                        <th>Expiration Date</th>
                        <th>Approval Status</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contracts as $c): ?>
                    <tr>
                        <td class="fw-bold text-light"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($c['department_name']); ?></span></td>
                        <td><span class="badge badge-soft-info"><?= $c['contract_type']; ?></span></td>
                        <td><?= $c['start_date']; ?></td>
                        <td><span class="text-warning fw-bold"><?= $c['end_date'] ?? 'Indefinite'; ?></span></td>
                        <td>
                            <?php if ($c['approval_status'] === 'Approved'): ?>
                                <span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Approved</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning">Pending Sign-off</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($c['status'] === 'Active'): ?>
                                <span class="badge badge-soft-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-soft-danger"><?= $c['status']; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

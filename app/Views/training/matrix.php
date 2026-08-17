<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <a href="index.php?page=training" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to LMS Dashboard</a>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-chart-pie text-primary me-2"></i> Employee Skills Matrix & Competency Mapping</h4>
            <p class="text-secondary mb-0">Track employee technical proficiency vs target benchmarks</p>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Skill Name</th>
                        <th>Current Proficiency</th>
                        <th>Target Level</th>
                        <th>Verified By</th>
                        <th>Last Audit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($skills as $s): ?>
                    <tr>
                        <td class="fw-bold text-light"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($s['department_name']); ?></span></td>
                        <td class="fw-bold text-info"><?= htmlspecialchars($s['skill_name']); ?></td>
                        <td>
                            <?php if ($s['proficiency_level'] === 'Expert'): ?>
                                <span class="badge badge-soft-success">Expert</span>
                            <?php elseif ($s['proficiency_level'] === 'Advanced'): ?>
                                <span class="badge badge-soft-info">Advanced</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning"><?= $s['proficiency_level']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-soft-primary"><?= $s['target_level']; ?></span></td>
                        <td><small class="text-secondary"><?= htmlspecialchars($s['verified_by']); ?></small></td>
                        <td><?= $s['updated_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

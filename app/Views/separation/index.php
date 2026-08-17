<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-plane-departure text-danger me-2"></i> Overseas Deployment & Exit Clearance System</h4>
            <p class="text-secondary mb-0">Multi-department clearance routing (HR, IT, Finance, Security, Mgr), asset returns, final pay, and Certificate of Employment</p>
        </div>
        <button class="btn btn-danger btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#initiateExitModal">
            <i class="fa-solid fa-user-minus me-1"></i> Initiate Exit Clearance
        </button>
    </div>

    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-clipboard-list me-2" style="color: var(--warning);"></i> Offboarding & Separation Tracker</h5>
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Candidate / OFW Name</th>
                        <th>Department / Position</th>
                        <th>Separation Type</th>
                        <th>Notice Date</th>
                        <th>Effective Date</th>
                        <th>Status</th>
                        <th>Clearance Routing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($separations as $sep): ?>
                    <tr>
                        <td class="fw-bold" style="color: var(--text);"><?= htmlspecialchars($sep['first_name'] . ' ' . $sep['last_name']); ?></td>
                        <td><?= htmlspecialchars($sep['department_name']); ?> <small class="text-secondary">(<?= htmlspecialchars($sep['position_title']); ?>)</small></td>
                        <td><span class="badge bg-light text-dark border"><?= $sep['separation_type']; ?></span></td>
                        <td><?= $sep['notice_date']; ?></td>
                        <td><span class="text-warning fw-bold"><?= $sep['effective_date']; ?></span></td>
                        <td>
                            <?php if ($sep['status'] === 'Completed'): ?>
                                <span class="badge badge-soft-success">Fully Cleared</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning"><i class="fa-solid fa-spinner fa-spin me-1"></i> In Routing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <span class="badge bg-success" title="HR Cleared">HR</span>
                                <span class="badge bg-success" title="IT Cleared">IT</span>
                                <span class="badge bg-warning text-white" title="Finance Pending">FIN</span>
                                <span class="badge bg-success" title="Security Cleared">SEC</span>
                            </div>
                        </td>
                        <td>
                            <a href="index.php?page=separation_clearance&id=<?= $sep['id']; ?>" class="btn btn-sm btn-outline-info me-1"><i class="fa-solid fa-route me-1"></i> Clearance Form</a>
                            <a href="index.php?page=separation_coe&id=<?= $sep['id']; ?>" class="btn btn-sm btn-outline-success" target="_blank"><i class="fa-solid fa-file-contract me-1"></i> COE PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="initiateExitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-user-minus text-danger me-2"></i> Initiate Offboarding Workflow</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="initiateExitForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Employee / Candidate</label>
                        <select class="form-select" name="employee_id" required>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id']; ?>"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Separation Type</label>
                        <select class="form-select" name="separation_type" required>
                            <option value="Resignation">Resignation</option>
                            <option value="Retirement">Retirement</option>
                            <option value="Termination">Termination</option>
                            <option value="Contract End">Contract Expiration</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Notice Filing Date</label>
                        <input type="date" class="form-control" name="notice_date" value="<?= date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Effective Separation Date</label>
                        <input type="date" class="form-control" name="effective_date" value="<?= date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Reason / Offboarding Notes</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Provide detailed resignation or separation context..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold"><i class="fa-solid fa-play me-1"></i> Start Routing & Clearance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#initiateExitForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'index.php?page=separation_initiate',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                showToast('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

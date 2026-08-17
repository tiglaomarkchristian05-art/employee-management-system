<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <a href="index.php?page=separation" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Deployments & Separations</a>
        <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-route me-2" style="color: var(--info);"></i> Department Exit Clearance Routing</h4>
        <p class="text-secondary">Offboarding record for: <strong style="color: var(--text);"><?= htmlspecialchars($separation['first_name'] . ' ' . $separation['last_name']); ?></strong> (<?= htmlspecialchars($separation['employee_code']); ?>)</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="glass-card p-4 mb-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-list-check me-2" style="color: var(--success);"></i> 5-Department Sign-off Status</h5>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($clearances as $c): ?>
                    <div class="p-3 rounded bg-light border d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-secondary mb-1"><?= $c['department_name']; ?> Department</span>
                            <div class="fw-bold" style="font-size:0.95rem; color: var(--text);">Clearance Sign-off</div>
                            <small class="text-secondary">Signed by: <?= htmlspecialchars($c['cleared_by'] ?? 'Pending Sign-off'); ?> <?= $c['clearance_date'] ? 'on ' . $c['clearance_date'] : ''; ?></small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($c['status'] === 'Cleared'): ?>
                                <span class="badge badge-soft-success fs-6"><i class="fa-solid fa-check me-1"></i> Cleared</span>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success fw-bold btn-approve-clearance text-white" data-id="<?= $c['id']; ?>"><i class="fa-solid fa-signature me-1"></i> Sign-off</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-laptop text-warning me-2"></i> Company Asset Return Checklist</h5>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Item Description</th>
                                <th>Serial No.</th>
                                <th>Condition</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assets as $asset): ?>
                            <tr>
                                <td class="fw-bold" style="color: var(--text);"><?= htmlspecialchars($asset['item_name']); ?></td>
                                <td><code><?= htmlspecialchars($asset['serial_no'] ?? 'N/A'); ?></code></td>
                                <td><span class="badge badge-soft-info"><?= $asset['condition_status']; ?></span></td>
                                <td>
                                    <?php if ($asset['returned']): ?>
                                        <span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Returned & Verified</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-danger">Pending Return</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-calculator me-2" style="color: var(--primary);"></i> Final Pay Breakdown</h5>
                
                <?php if ($final_pay): ?>
                <div class="d-flex flex-column gap-2 mb-3" style="font-size:0.9rem;">
                    <div class="d-flex justify-content-between p-2 rounded bg-light border">
                        <span class="text-secondary">Pro-rated Basic Salary Due</span>
                        <span class="fw-bold" style="color: var(--text);">₱<?= number_format($final_pay['basic_pay_due'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-light border">
                        <span class="text-secondary">Unused Leave Encashment</span>
                        <span class="fw-bold text-success">₱<?= number_format($final_pay['unused_leave_encashment'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-light border">
                        <span class="text-secondary">Pro-rated 13th Month Pay</span>
                        <span class="fw-bold text-info">₱<?= number_format($final_pay['thirteenth_month_prorated'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between p-2 rounded bg-light border">
                        <span class="text-secondary">Outstanding Loan Deductions</span>
                        <span class="fw-bold text-danger">-₱<?= number_format($final_pay['loan_deductions'], 2); ?></span>
                    </div>
                </div>

                <div class="p-3 rounded bg-success bg-opacity-10 border border-success d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Net Final Pay Package</h6>
                        <small class="text-success">Ready for Finance Disbursement</small>
                    </div>
                    <h3 class="fw-bold text-success mb-0">₱<?= number_format($final_pay['net_final_pay'], 2); ?></h3>
                </div>

                <a href="index.php?page=separation_coe&id=<?= $separation['id']; ?>" class="btn btn-outline-success btn-lg w-100 fw-bold shadow-sm" target="_blank">
                    <i class="fa-solid fa-file-pdf me-2"></i> Issue Certificate of Employment (COE)
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('.btn-approve-clearance').on('click', function() {
    const clearanceId = $(this).data('id');
    confirmAction('Sign-off Clearance', 'Do you certify that all departmental assets and liabilities are settled?', 'Approve Sign-off', function() {
        $.post('index.php?page=separation_update_clearance', {
            clearance_id: clearanceId,
            status: 'Cleared',
            comments: 'Fully settled & cleared',
            csrf_token: '<?= generate_csrf_token(); ?>'
        }, function(res) {
            if (res.status === 'success') {
                showToast('success', res.message);
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

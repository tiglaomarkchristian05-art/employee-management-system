<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-landmark text-success me-2"></i> Government Contribution & Compliance (PH)</h4>
            <p class="text-secondary mb-0">SSS, PhilHealth, Pag-IBIG HDMF remittance calculation, statutory schedules, and BIR 2316 Certificate generator</p>
        </div>
        <?php if (Auth::isAdmin()): ?><div class="d-flex gap-2">
            <a href="index.php?page=compliance_calculator" class="btn btn-outline-success btn-sm"><i class="fa-solid fa-calculator me-1"></i> Live Tax Calculator</a>
            <a href="index.php?page=compliance_bir2316" class="btn btn-success btn-sm fw-bold shadow"><i class="fa-solid fa-file-invoice-dollar me-1"></i> BIR 2316 Generator</a>
        </div><?php else: ?><a href="index.php?page=compliance_bir2316" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-file-arrow-down me-1"></i> View My BIR 2316</a><?php endif; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold text-light mb-3"><i class="fa-solid fa-calendar-check text-info me-2"></i> Statutory Deadlines</h5>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($deadlines as $dl): ?>
                    <div class="p-3 rounded bg-white text-dark border border-light shadow-sm d-flex align-items-center justify-content-between">
                        <div>
                            <span class="badge bg-primary text-white mb-1"><?= $dl['agency_name']; ?></span>
                            <div class="fw-bold text-dark" style="font-size:0.88rem;"><?= htmlspecialchars($dl['form_type']); ?></div>
                            <small class="text-muted"><?= htmlspecialchars($dl['description']); ?></small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-warning text-dark fw-bold mb-1"><i class="fa-solid fa-clock me-1"></i> <?= $dl['due_date']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-card p-4">
                <h5 class="fw-bold text-light mb-3"><i class="fa-solid fa-receipt text-success me-2"></i> Monthly Remittance & Tax Withholding Summary</h5>
                <div class="table-responsive">
                    <table class="table align-middle datatable-init">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Gross Salary</th>
                                <th>SSS (EE/ER)</th>
                                <th>PhilHealth (EE/ER)</th>
                                <th>Pag-IBIG (EE/ER)</th>
                                <th>BIR Tax Withheld</th>
                                <th>Total Statutory</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contributions as $c): ?>
                            <tr>
                                <td class="fw-bold text-light">
                                    <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    <div class="small text-secondary">TIN: <?= htmlspecialchars($c['tin_no'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="fw-bold">₱<?= number_format($c['gross_salary'], 2); ?></td>
                                <td><small class="text-info">₱<?= number_format($c['sss_employee'], 2); ?></small> / <small class="text-secondary">₱<?= number_format($c['sss_employer'], 2); ?></small></td>
                                <td><small class="text-success">₱<?= number_format($c['philhealth_employee'], 2); ?></small> / <small class="text-secondary">₱<?= number_format($c['philhealth_employer'], 2); ?></small></td>
                                <td><small class="text-warning">₱<?= number_format($c['pagibig_employee'], 2); ?></small> / <small class="text-secondary">₱<?= number_format($c['pagibig_employer'], 2); ?></small></td>
                                <td class="fw-bold text-danger">₱<?= number_format($c['bir_tax_withheld'], 2); ?></td>
                                <td class="fw-bold text-light">₱<?= number_format($c['total_statutory'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

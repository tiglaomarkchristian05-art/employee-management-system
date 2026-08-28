<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <a href="index.php?page=compliance" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Compliance</a>
        <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-file-invoice-dollar text-success me-2"></i> BIR Form 2316 Certificate Exporter</h4>
        <p class="text-secondary">Certificate of Compensation Payment / Tax Withheld</p>
    </div>

    <div class="glass-card p-3 mb-4">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="page" value="compliance_bir2316">
            <div class="col-md-6">
                <?php if (Auth::isAdmin()): ?>
                <select class="form-select" name="employee_id" onchange="this.form.submit()">
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id']; ?>" <?= ($bir_data && $bir_data['id'] == $e['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name'] . ' (' . $e['employee_code'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <div class="form-control bg-light d-flex align-items-center"><i class="fa-solid fa-user-shield text-primary me-2"></i><?= htmlspecialchars(($bir_data['first_name'] ?? '').' '.($bir_data['last_name'] ?? '')); ?> — My tax certificate</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success fw-bold w-100" onclick="window.print()"><i class="fa-solid fa-print me-1"></i> Print / Export BIR 2316 PDF</button>
            </div>
        </form>
    </div>

    <?php if ($bir_data): ?>
    <div class="card p-5 max-w-800 mx-auto text-dark bg-white border border-secondary shadow-sm" style="background-color: #ffffff !important;">
        <div class="text-center border-bottom border-secondary border-opacity-25 pb-3 mb-4">
            <h5 class="fw-bold text-success">REPUBLIC OF THE PHILIPPINES - BUREAU OF INTERNAL REVENUE</h5>
            <h4 class="fw-bold text-dark">BIR FORM NO. 2316</h4>
            <div class="small text-muted">Certificate of Compensation Payment / Tax Withheld</div>
            <div class="fw-bold text-primary mt-1">Calendar Year: <?= date('Y'); ?></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="p-3 bg-white rounded border border-light shadow-sm" style="background-color: #ffffff !important;">
                    <small class="text-secondary fw-bold">EMPLOYEE INFORMATION</small>
                    <div class="fw-bold text-dark mt-1"><?= htmlspecialchars($bir_data['first_name'] . ' ' . $bir_data['last_name']); ?></div>
                    <div class="small text-secondary">TIN: <code><?= htmlspecialchars($bir_data['tin_no'] ?? '234-567-890-000'); ?></code></div>
                    <div class="small text-secondary">Position: <?= htmlspecialchars($bir_data['position_title']); ?></div>
                </div>
            </div>
            <div class="col-6">
                <div class="p-3 bg-white rounded border border-light shadow-sm" style="background-color: #ffffff !important;">
                    <small class="text-secondary fw-bold">EMPLOYER INFORMATION</small>
                    <div class="fw-bold text-dark mt-1"><?= APP_COMPANY; ?></div>
                    <div class="small text-secondary">TIN: <code>000-123-456-000</code></div>
                    <div class="small text-secondary">Address: Taguig City, Metro Manila</div>
                </div>
            </div>
        </div>

        <div class="p-3 rounded bg-white border border-light shadow-sm mb-3" style="background-color: #ffffff !important;">
            <h6 class="fw-bold text-primary mb-3">Compensation & Tax Summary Breakdown</h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Total Gross Compensation Income</span>
                <span class="fw-bold text-dark">₱<?= number_format($bir_data['total_gross'] ?? 48000.00, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Non-Taxable Mandatory Contributions (SSS/PH/HDMF)</span>
                <span class="fw-bold text-success">-₱<?= number_format($bir_data['total_non_taxable_statutory'] ?? 2750.00, 2); ?></span>
            </div>
            <div class="d-flex justify-content-between border-top border-secondary border-opacity-25 pt-2 mb-2">
                <span class="fw-bold text-dark">Taxable Compensation Income</span>
                <span class="fw-bold text-dark">₱<?= number_format(($bir_data['total_gross'] ?? 48000.00) - ($bir_data['total_non_taxable_statutory'] ?? 2750.00), 2); ?></span>
            </div>
            <div class="d-flex justify-content-between border-top border-secondary border-opacity-25 pt-2">
                <span class="fw-bold text-danger">Total Amount of Tax Withheld</span>
                <span class="fw-bold text-danger">₱<?= number_format($bir_data['total_tax_withheld'] ?? 4850.25, 2); ?></span>
            </div>
        </div>

        <div class="text-center text-muted small mt-4">
            Certified True and Correct by Employer Authorized Representative
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

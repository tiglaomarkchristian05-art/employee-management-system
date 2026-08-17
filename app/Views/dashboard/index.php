<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="glass-card p-4 mb-4 mt-3 border-start border-4" style="border-start-color: var(--primary) !important;">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="color: var(--text);">
                    Welcome to <?= APP_COMPANY; ?>! <span class="fs-5">✈️</span>
                </h4>
                <p class="text-secondary mb-0">
                    <?= htmlspecialchars($user['role']); ?> | Overseeing Global Manpower Deployment, Visa Stamping, OEC Clearance & Overseas Job Orders
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="index.php?page=training" class="btn btn-outline-primary btn-sm rounded-pill"><i class="fa-solid fa-graduation-cap me-1"></i> PDOS & Trade LMS</a>
                <a href="index.php?page=compliance" class="btn btn-primary btn-sm rounded-pill shadow-sm"><i class="fa-solid fa-plane-departure me-1"></i> DMW/OWWA Compliance</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="glass-card stat-card">
                <div class="text-secondary small fw-bold text-uppercase">Deployed OFWs</div>
                <h3 class="fw-bold mt-2 mb-1" style="color: var(--text);">1,420</h3>
                <span class="badge badge-soft-success"><i class="fa-solid fa-globe me-1"></i> UAE, KSA, Japan, Canada</span>
                <i class="fa-solid fa-plane-departure stat-icon-bg"></i>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="glass-card stat-card">
                <div class="text-secondary small fw-bold text-uppercase">Active Job Orders</div>
                <h3 class="fw-bold mt-2 mb-1" style="color: var(--primary);">48 Job Orders</h3>
                <span class="text-secondary small">12 Foreign Principal Employers</span>
                <i class="fa-solid fa-briefcase stat-icon-bg"></i>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="glass-card stat-card">
                <div class="text-secondary small fw-bold text-uppercase">OEC & Visa Processing</div>
                <h3 class="fw-bold mt-2 mb-1 text-warning">32 Candidates</h3>
                <span class="badge badge-soft-warning"><i class="fa-solid fa-passport me-1"></i> Visa Stamped & OEC Released</span>
                <i class="fa-solid fa-id-card stat-icon-bg"></i>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="glass-card stat-card">
                <div class="text-secondary small fw-bold text-uppercase">PDOS & Trade Testing</div>
                <h3 class="fw-bold mt-2 mb-1 text-success">98.5% Pass Rate</h3>
                <span class="badge badge-soft-info"><i class="fa-solid fa-award me-1"></i> Certified Trade Pass</span>
                <i class="fa-solid fa-certificate stat-icon-bg"></i>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="glass-card p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--text);"><i class="fa-solid fa-chart-line me-2" style="color: var(--primary);"></i> Overseas Deployment Trend & PDOS Completion</h5>
                    <span class="badge badge-soft-primary">Global Deployment Analytics</span>
                </div>
                <canvas id="trainingTrendChart" height="260"></canvas>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-pie-chart me-2" style="color: var(--success);"></i> Overseas Country Destinations</h5>
                <canvas id="govContribChart" height="260"></canvas>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--text);"><i class="fa-solid fa-layer-group me-2" style="color: var(--primary);"></i> Agency Subsystems Quick Access</h5>
            <small class="text-secondary">DMW / OWWA Aligned Overseas Recruitment Workflow</small>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="p-3 rounded bg-light border h-100">
                    <h6 class="fw-bold" style="color: var(--primary);"><i class="fa-solid fa-graduation-cap me-2"></i> PDOS & Trade Skills LMS</h6>
                    <p class="text-secondary small mb-2">Pre-departure orientation, Arabic/Japanese language courses, trade testing & certificates.</p>
                    <a href="index.php?page=training" class="btn btn-sm btn-outline-primary">Open PDOS LMS <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded bg-light border h-100">
                    <h6 class="fw-bold text-warning"><i class="fa-solid fa-passport me-2"></i> Candidate Docs & OEC/Visa</h6>
                    <p class="text-secondary small mb-2">Passport verification, DMW E-Reg, Medical fit clearance, OEC QR verification stamps.</p>
                    <a href="index.php?page=documents" class="btn btn-sm btn-outline-warning">Open Candidate Docs <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="p-3 rounded bg-light border h-100">
                    <h6 class="fw-bold text-success"><i class="fa-solid fa-landmark me-2"></i> DMW & Gov Compliance</h6>
                    <p class="text-secondary small mb-2">DMW/POEA compliance, OWWA memberships, agency bond, SSS/PhilHealth/Pag-IBIG remittance.</p>
                    <a href="index.php?page=compliance" class="btn btn-sm btn-outline-success">Open DMW Compliance <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 mt-3">
                <div class="p-3 rounded bg-light border h-100">
                    <h6 class="fw-bold" style="color: var(--secondary);"><i class="fa-solid fa-hand-holding-dollar me-2"></i> OFW Benefits & Placement Loans</h6>
                    <p class="text-secondary small mb-2">Mandatory OFW insurance (Repatriation, Medical Evacuation), deployment loans & allowances.</p>
                    <a href="index.php?page=benefits" class="btn btn-sm btn-outline-info">Open OFW Benefits <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>

            <div class="col-md-6 mt-3">
                <div class="p-3 rounded bg-light border h-100">
                    <h6 class="fw-bold text-danger"><i class="fa-solid fa-plane-departure me-2"></i> Deployment & Flight Clearance</h6>
                    <p class="text-secondary small mb-2">Flight scheduling, POLO endorsement, airport assistance checklist, repatriation clearance.</p>
                    <a href="index.php?page=separation" class="btn btn-sm btn-outline-danger">Open Deployment Clearance <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

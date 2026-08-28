<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="glass-card dashboard-toolbar mb-4 mt-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="dashboard-tab"><i class="fa-solid fa-table-cells-large"></i><span>Dashboard</span></div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm dashboard-filter" data-bs-toggle="modal" data-bs-target="#dashboardPreferencesModal"><i class="fa-solid fa-sliders me-2"></i>Customize Filters &amp; Layout</button>
            </div>
        </div>
    </div>

    <div class="reporting-scope mb-4">
        <div class="scope-icon"><i class="fa-regular fa-calendar-check"></i></div>
        <div><span>CURRENT REPORTING SCOPE</span><strong>Jan 1 to Dec 31, 2026 | All departments | All branches</strong><small>Operational and workforce records within the selected period.</small></div>
    </div>

    <div class="section-kicker">KEY METRICS &amp; HEALTH INDICATORS</div>
    <h5 class="section-heading">Executive Summary</h5>

    <div class="row g-3 mb-4 executive-cards core-kpi-grid" id="dashboardKpis">
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

    <div id="dashboardAnalytics"><div class="section-kicker mt-4">INTERACTIVE ANALYTICS</div>
    <h5 class="section-heading">Performance &amp; Operational Charts</h5>
    <div class="row g-3 mb-4 analytics-grid">
        <div class="col-lg-8">
            <div class="glass-card p-4 chart-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--text);"><i class="fa-solid fa-chart-line me-2" style="color: var(--primary);"></i> Overseas Deployment Trend & PDOS Completion</h5>
                    <span class="badge badge-soft-primary">Global Deployment Analytics</span>
                </div>
                <canvas id="trainingTrendChart" height="260"></canvas>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="glass-card p-4 chart-card">
                <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-pie-chart me-2" style="color: var(--success);"></i> Overseas Country Destinations</h5>
                <canvas id="govContribChart" height="260"></canvas>
            </div>
        </div>
    </div></div>

    <div class="glass-card p-4 mb-4" id="dashboardQuickAccess">
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

<div class="modal fade" id="dashboardPreferencesModal" tabindex="-1" aria-labelledby="dashboardPreferencesTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="dashboardPreferencesTitle"><i class="fa-solid fa-sliders me-2 text-primary"></i>Dashboard Preferences</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="text-secondary small">Choose which dashboard sections are visible. Preferences are saved on this device.</p>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prefKpis" checked><label class="form-check-label" for="prefKpis">Executive summary cards</label></div>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prefAnalytics" checked><label class="form-check-label" for="prefAnalytics">Analytics charts</label></div>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prefQuickAccess" checked><label class="form-check-label" for="prefQuickAccess">Subsystem quick access</label></div>
                <label class="form-label" for="prefDensity">Layout density</label><select class="form-select" id="prefDensity"><option value="comfortable">Comfortable</option><option value="compact">Compact</option></select>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="resetDashboardPreferences">Reset</button><button type="button" class="btn btn-primary" id="saveDashboardPreferences">Apply &amp; Save</button></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const defaults = { kpis: true, analytics: true, quickAccess: true, density: 'comfortable' };
    const readPreferences = () => {
        try { return Object.assign({}, defaults, JSON.parse(localStorage.getItem('core3_dashboard_preferences') || '{}')); }
        catch (error) { return defaults; }
    };
    const applyPreferences = (preferences) => {
        document.getElementById('dashboardKpis').hidden = !preferences.kpis;
        document.getElementById('dashboardAnalytics').hidden = !preferences.analytics;
        document.getElementById('dashboardQuickAccess').hidden = !preferences.quickAccess;
        document.getElementById('main-content').classList.toggle('dashboard-compact', preferences.density === 'compact');
        document.getElementById('prefKpis').checked = preferences.kpis;
        document.getElementById('prefAnalytics').checked = preferences.analytics;
        document.getElementById('prefQuickAccess').checked = preferences.quickAccess;
        document.getElementById('prefDensity').value = preferences.density;
        window.dispatchEvent(new Event('resize'));
    };
    applyPreferences(readPreferences());
    document.getElementById('saveDashboardPreferences').addEventListener('click', function () {
        const preferences = { kpis: document.getElementById('prefKpis').checked, analytics: document.getElementById('prefAnalytics').checked, quickAccess: document.getElementById('prefQuickAccess').checked, density: document.getElementById('prefDensity').value };
        localStorage.setItem('core3_dashboard_preferences', JSON.stringify(preferences));
        applyPreferences(preferences);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardPreferencesModal')).hide();
        showToast('success', 'Dashboard preferences saved.');
    });
    document.getElementById('resetDashboardPreferences').addEventListener('click', function () {
        localStorage.removeItem('core3_dashboard_preferences'); applyPreferences(defaults);
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

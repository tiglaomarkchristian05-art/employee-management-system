<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$user = Auth::user();
?>
<aside id="sidebar" class="d-flex flex-column justify-content-between">
    <div>
        <div class="p-3 d-flex align-items-center gap-2 border-bottom border-light">
            <div class="sidebar-brand-icon">
                <i class="fa-solid fa-plane-departure"></i>
            </div>
            <div class="brand-text overflow-hidden">
                <h6 class="sidebar-brand-title"><?= APP_NAME; ?></h6>
                <small class="text-muted" style="font-size:0.72rem; white-space:nowrap;">Overseas Manpower HRMS</small>
            </div>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard" title="Agency Executive Dashboard">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span class="nav-text">Agency Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <div class="sidebar-section-title">Recruitment Subsystems</div>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($currentPage, 'training') !== false ? 'active' : ''; ?>" href="index.php?page=training" title="PDOS & Trade LMS">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span class="nav-text">PDOS & Trade LMS</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($currentPage, 'documents') !== false ? 'active' : ''; ?>" href="index.php?page=documents" title="Docs, OEC & Visa Management">
                    <i class="fa-solid fa-passport"></i>
                    <span class="nav-text">Docs, OEC & Visa</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($currentPage, 'compliance') !== false ? 'active' : ''; ?>" href="index.php?page=compliance" title="DMW & Gov Compliance">
                    <i class="fa-solid fa-landmark"></i>
                    <span class="nav-text">DMW Compliance</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($currentPage, 'benefits') !== false ? 'active' : ''; ?>" href="index.php?page=benefits" title="OFW Benefits & Loans">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span class="nav-text">OFW Benefits & Loans</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= strpos($currentPage, 'separation') !== false ? 'active' : ''; ?>" href="index.php?page=separation" title="Deployment & Exit Clearance">
                    <i class="fa-solid fa-plane-departure"></i>
                    <span class="nav-text">Deployment & Exit</span>
                </a>
            </li>

            <li class="nav-item">
                <div class="sidebar-section-title">Candidates & Agency</div>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'employees' ? 'active' : ''; ?>" href="index.php?page=employees" title="OFW Candidate Directory">
                    <i class="fa-solid fa-users"></i>
                    <span class="nav-text">OFW Candidate Directory</span>
                </a>
            </li>

            <?php if (Auth::hasRole(['Super Admin', 'HR Manager'])): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_users' ? 'active' : ''; ?>" href="index.php?page=admin_users" title="Agency Users & Roles">
                    <i class="fa-solid fa-user-shield"></i>
                    <span class="nav-text">Agency Users & Roles</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_logs' ? 'active' : ''; ?>" href="index.php?page=admin_logs" title="System Audit Trail">
                    <i class="fa-solid fa-receipt"></i>
                    <span class="nav-text">System Audit Trail</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'admin_backup' ? 'active' : ''; ?>" href="index.php?page=admin_backup" title="Backup & Restore Utility">
                    <i class="fa-solid fa-database"></i>
                    <span class="nav-text">Backup & Restore</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="p-3 border-top border-light d-flex align-items-center gap-2 mt-auto">
        <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px; height:36px; background-color: #7C3AED; font-size:0.88rem;">
            <?= strtoupper(substr($user['full_name'] ?? 'A', 0, 1)); ?>
        </div>
        <div class="user-info-text overflow-hidden text-truncate" style="line-height:1.2;">
            <div class="fw-bold text-dark text-truncate" style="font-size:0.82rem; color:#0F172A;"><?= htmlspecialchars($user['full_name'] ?? 'Admin User'); ?></div>
            <small class="text-muted text-truncate d-block" style="font-size:0.72rem; color:#64748B;"><?= htmlspecialchars($user['email'] ?? strtolower(str_replace(' ', '', $user['username'] ?? 'admin')).'@agency.com'); ?></small>
        </div>
    </div>
</aside>
<?php
$user = Auth::user();
?>
<nav id="navbar" class="dashboard-topbar">
    <div class="header-leading topbar-start">
        <a class="navbar-crown topbar-brand" href="index.php?page=dashboard" aria-label="Core 3 dashboard"><span class="material-symbols-outlined">groups</span></a>
        <span class="header-separator" aria-hidden="true"></span>
        <button class="icon-button" id="sidebar-toggle-btn" type="button" aria-controls="sidebar" aria-expanded="true" aria-label="Toggle sidebar">
            <span class="material-symbols-outlined">menu_open</span>
        </button>
    </div>

    <div class="topbar-actions">
        <div class="navbar-search-box d-none">
            <i class="fa-solid fa-magnifying-glass me-2" style="color:#94A3B8; font-size:0.85rem;"></i>
            <input type="text" class="navbar-search-input" placeholder="Search...">
        </div>

        <div class="dropdown d-none">
            <button class="btn btn-sm border-0 rounded-circle position-relative" style="width:38px; height:38px; background-color: #F1F0F7;" data-bs-toggle="dropdown">
                <i class="fa-solid fa-bell text-secondary"></i>
                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
            </button>
            <div class="dropdown-menu dropdown-menu-end glass-card p-3 shadow-lg" style="width: 320px;">
                <h6 class="fw-bold mb-3" style="color: var(--primary);"><i class="fa-solid fa-bell me-2"></i> System Alerts</h6>
                <div class="d-flex flex-column gap-2" style="font-size:0.85rem;">
                    <div class="p-2 rounded bg-light border">
                        <i class="fa-solid fa-file-contract text-warning me-1"></i> Contract expiry notice: 2 contracts due in 30 days.
                    </div>
                    <div class="p-2 rounded bg-light border">
                        <i class="fa-solid fa-graduation-cap text-info me-1"></i> New LMS course available: Cybersecurity 2026.
                    </div>
                </div>
            </div>
        </div>

        <div class="dropdown profile-menu profile-dropdown-wrap">
            <button type="button" class="profile-menu-trigger profile-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Account Menu">
                <div class="avatar-circle profile-avatar">
                    <?= strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-copy profile-meta d-none d-md-grid">
                    <div class="fw-bold" style="font-size:0.88rem; color: var(--text);"><?= htmlspecialchars($user['full_name']); ?></div>
                    <small style="font-size:0.75rem; color: var(--primary); font-weight:600;"><?= htmlspecialchars($user['role']); ?></small>
                </div>
                <span class="material-symbols-outlined profile-chevron d-none d-sm-inline-block">expand_more</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end profile-dropdown shadow-lg mt-2">
                <li><span class="dropdown-header">Logged in as <strong><?= htmlspecialchars($user['username']); ?></strong></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="index.php?page=<?= Auth::isSelfService() ? 'my_profile' : 'employees'; ?>"><span class="material-symbols-outlined">person</span><?= Auth::isSelfService() ? 'My Profile' : 'Employee Directory'; ?></a></li>
                <li><a class="dropdown-item" href="index.php?page=<?= Auth::isSelfService() ? 'notifications' : (Auth::hasRole(['Super Admin']) ? 'admin_settings' : 'admin_users'); ?>"><span class="material-symbols-outlined"><?= Auth::isSelfService() ? 'notifications' : 'manage_accounts'; ?></span><?= Auth::isSelfService() ? 'Notifications' : 'Account Settings'; ?></a></li>
                <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal"><span class="material-symbols-outlined">logout</span>Log Out</a></li>
            </ul>
        </div>
    </div>
</nav>

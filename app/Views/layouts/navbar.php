<?php
$user = Auth::user();
?>
<nav id="navbar" class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary border-0 text-secondary" id="sidebar-toggle-btn">
            <i class="fa-solid fa-bars fs-5"></i>
        </button>
        <span class="fw-bold" style="font-size: 1.05rem; color: #0F172A;">
            <?= APP_COMPANY; ?>
        </span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="navbar-search-box d-none d-md-flex">
            <i class="fa-solid fa-magnifying-glass me-2" style="color:#94A3B8; font-size:0.85rem;"></i>
            <input type="text" class="navbar-search-input" placeholder="Search...">
        </div>

        <div class="dropdown">
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

        <div class="dropdown">
            <div class="d-flex align-items-center gap-2 cursor-pointer" data-bs-toggle="dropdown">
                <div class="avatar-circle text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width:36px; height:36px; background-color: var(--primary);">
                    <?= strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="d-none d-md-block text-start" style="line-height:1.2;">
                    <div class="fw-bold" style="font-size:0.88rem; color: var(--text);"><?= htmlspecialchars($user['full_name']); ?></div>
                    <small style="font-size:0.75rem; color: var(--primary); font-weight:600;"><?= htmlspecialchars($user['role']); ?></small>
                </div>
            </div>
            <ul class="dropdown-menu dropdown-menu-end glass-card shadow-lg mt-2">
                <li><span class="dropdown-header">Logged in as <strong><?= htmlspecialchars($user['username']); ?></strong></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="index.php?page=employees"><i class="fa-solid fa-user me-2"></i> My Profile</a></li>
                <li><a class="dropdown-item text-danger" href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

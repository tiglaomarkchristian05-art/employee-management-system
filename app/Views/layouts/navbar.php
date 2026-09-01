<?php
$user = Auth::user();
require_once APP_PATH . 'Models/Notification.php';
$navbarNotificationModel = new Notification();
$navbarUnreadCount = $navbarNotificationModel->unreadCount($user['id']);
$navbarNotifications = $navbarNotificationModel->getForUser($user['id'], 5);
?>
<nav id="navbar" class="dashboard-topbar">
    <div class="header-leading topbar-start">
        <a class="navbar-crown topbar-brand" href="index.php?page=dashboard" aria-label="Core 3 dashboard"><img src="assets/images/logo-icon.svg" alt="Great Solomon Manpower Services Inc."></a>
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

        <div class="dropdown">
            <button class="btn btn-sm border-0 rounded-circle position-relative" style="width:38px; height:38px; background-color: #F1F0F7;" data-bs-toggle="dropdown">
                <i class="fa-solid fa-bell text-secondary"></i>
                <?php if($navbarUnreadCount>0): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $navbarUnreadCount>99?'99+':$navbarUnreadCount; ?></span><?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-popover shadow-lg">
                <div class="notification-popover-header">
                    <div><span class="notification-heading-icon"><i class="fa-solid fa-bell"></i></span><div><h6>Notifications</h6><small><?= $navbarUnreadCount ? $navbarUnreadCount.' unread update'.($navbarUnreadCount===1?'':'s') : 'You are all caught up'; ?></small></div></div>
                    <a href="index.php?page=notifications">View all</a>
                </div>
                <div class="notification-popover-list">
                    <?php if(!$navbarNotifications): ?><div class="notification-empty"><span><i class="fa-regular fa-bell"></i></span><strong>No notifications yet</strong><small>Workflow updates will appear here.</small></div><?php else: foreach($navbarNotifications as $note):
                        $noteModule=strtolower((string)($note['module']??'system'));
                        $noteIcons=['training'=>'fa-graduation-cap','documents'=>'fa-folder-open','compliance'=>'fa-landmark','benefits'=>'fa-hand-holding-heart','loans'=>'fa-wallet','separation'=>'fa-user-check','system'=>'fa-bell'];
                        $noteIcon=$noteIcons[$noteModule]??'fa-bell';
                        $noteTone=in_array(($note['type']??'info'),['success','warning','error','info'],true)?$note['type']:'info';
                    ?>
                    <a class="notification-popover-item tone-<?= htmlspecialchars($noteTone); ?> <?= empty($note['is_read'])?'is-unread':'is-read'; ?>" href="index.php?page=notification_open&amp;id=<?= (int)$note['id']; ?>">
                        <span class="notification-item-icon"><i class="fa-solid <?= $noteIcon; ?>"></i></span>
                        <span class="notification-item-copy"><span class="notification-item-top"><strong><?= htmlspecialchars($note['title']); ?></strong><?php if(empty($note['is_read'])): ?><i class="notification-unread-dot" aria-label="Unread"></i><?php endif; ?></span><small><?= htmlspecialchars($note['message']); ?></small><span class="notification-item-meta"><span><?= htmlspecialchars(ucfirst($noteModule)); ?></span><time><?= !empty($note['created_at'])?date('M j · g:i A',strtotime($note['created_at'])):''; ?></time></span></span>
                    </a>
                    <?php endforeach; endif; ?>
                </div>
                <?php if($navbarNotifications): ?><a class="notification-popover-footer" href="index.php?page=notifications"><span>Open notification center</span><i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
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

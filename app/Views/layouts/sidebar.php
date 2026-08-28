<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$user = Auth::user();
$isEmployee = Auth::isSelfService();
$items = $isEmployee ? [
    ['section' => 'My Employee Services'],
    ['pages' => ['training','training_courses','training_matrix','training_quiz'], 'page' => 'training', 'icon' => 'fa-graduation-cap', 'label' => 'My Development', 'subtitle' => 'My Training, Courses & Skills'],
    ['pages' => ['documents','documents_contracts'], 'page' => 'documents', 'icon' => 'fa-folder-open', 'label' => 'My Documents', 'subtitle' => 'Contracts & Personal Files'],
    ['pages' => ['compliance','compliance_bir2316'], 'page' => 'compliance', 'icon' => 'fa-landmark', 'label' => 'My Contributions', 'subtitle' => 'Government Contribution Records'],
    ['pages' => ['benefits','benefits_loans'], 'page' => 'benefits', 'icon' => 'fa-hand-holding-dollar', 'label' => 'My Benefits & Loans', 'subtitle' => 'Claims, Benefits & Loans'],
    ['pages' => ['separation','separation_clearance','separation_coe'], 'page' => 'separation', 'icon' => 'fa-plane-departure', 'label' => 'My Clearance', 'subtitle' => 'Offboarding & Exit Status'],
    ['section' => 'Account'],
    ['pages' => ['my_profile'], 'page' => 'my_profile', 'icon' => 'fa-user', 'label' => 'My Profile', 'subtitle' => 'Personal & Employment Information'],
    ['pages' => ['notifications'], 'page' => 'notifications', 'icon' => 'fa-bell', 'label' => 'Notifications', 'subtitle' => 'Updates & Reminders'],
] : [
    ['section' => 'Core 3 Modules'],
    ['pages' => ['training','training_courses','training_matrix','training_quiz'], 'page' => 'training', 'icon' => 'fa-graduation-cap', 'label' => 'Employee Development', 'subtitle' => 'Training, Courses & Skills'],
    ['pages' => ['documents','documents_contracts'], 'page' => 'documents', 'icon' => 'fa-folder-open', 'label' => 'Document Management', 'subtitle' => 'Contracts & Employee Files'],
    ['pages' => ['compliance','compliance_calculator','compliance_bir2316'], 'page' => 'compliance', 'icon' => 'fa-landmark', 'label' => 'Compliance', 'subtitle' => 'Government Contributions'],
    ['pages' => ['benefits','benefits_loans'], 'page' => 'benefits', 'icon' => 'fa-hand-holding-dollar', 'label' => 'Benefits & Loans', 'subtitle' => 'Claims, Allowances & Loans'],
    ['pages' => ['separation','separation_clearance','separation_coe'], 'page' => 'separation', 'icon' => 'fa-plane-departure', 'label' => 'Separation & Clearance', 'subtitle' => 'Offboarding & Final Pay'],
    ['section' => 'System Management'],
    ['pages' => ['employees'], 'page' => 'employees', 'icon' => 'fa-users', 'label' => 'Employee Directory', 'subtitle' => 'Employee Records & Profiles'],
    ['pages' => ['admin_users'], 'page' => 'admin_users', 'icon' => 'fa-user-shield', 'label' => 'Users and Roles', 'subtitle' => 'Account Access & Permissions'],
    ['pages' => ['admin_logs'], 'page' => 'admin_logs', 'icon' => 'fa-receipt', 'label' => 'Audit Trail', 'subtitle' => 'Security & Activity History'],
];
if (!$isEmployee && Auth::hasRole(['Super Admin'])) {
    $items[] = ['pages' => ['admin_settings','admin_backup'], 'page' => 'admin_backup', 'icon' => 'fa-database', 'label' => 'Settings & Backup', 'subtitle' => 'System Configuration'];
}
?>
<aside id="sidebar" class="dashboard-sidebar" aria-label="Primary navigation">
 <div>
  <div class="sidebar-brand"><div class="bi-brand-card sidebar-brand-card"><div class="sidebar-brand-icon"><span class="material-symbols-outlined">groups</span></div><div class="brand-text sidebar-brand-copy"><h6 class="sidebar-brand-title">Core 3</h6><small><?= $isEmployee ? 'Employee Self-Service' : 'Employee Development,<br>Compliance &amp; Benefits'; ?></small></div></div></div>
  <ul class="nav flex-column sidebar-nav">
   <li class="nav-item"><a class="nav-link sidebar-main-link <?= $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard"><span class="sidebar-main-link-icon material-symbols-outlined">dashboard</span><span class="nav-copy sidebar-label"><strong>Dashboard</strong><small><?= $isEmployee ? 'My Employee Overview' : 'Core 3 Overview &amp; Insights'; ?></small></span></a></li>
   <?php foreach ($items as $item): ?>
    <?php if (isset($item['section'])): ?><li class="nav-item"><div class="sidebar-section-title sidebar-section-label"><?= htmlspecialchars($item['section']); ?></div></li>
    <?php else: ?><li class="nav-item"><a class="nav-link sidebar-subsystem-link <?= in_array($currentPage, $item['pages'], true) ? 'active' : ''; ?>" href="index.php?page=<?= $item['page']; ?>" title="<?= htmlspecialchars($item['label']); ?>"><span class="sidebar-subsystem-link-icon"><i class="fa-solid <?= $item['icon']; ?>"></i></span><span class="nav-copy sidebar-label"><strong><?= htmlspecialchars($item['label']); ?></strong><small><?= htmlspecialchars($item['subtitle']); ?></small></span></a></li><?php endif; ?>
   <?php endforeach; ?>
  </ul>
 </div>
</aside>

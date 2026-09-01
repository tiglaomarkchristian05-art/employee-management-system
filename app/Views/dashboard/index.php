<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$value = static fn($key, $default = 0) => $summary[$key] ?? $default;
$metrics = [
 ['Total Active Employees',$value('total_active_employees'),'employees','fa-users','primary'],
 ['Upcoming Trainings',$value('upcoming_trainings'),'training','fa-calendar-days','info'],
 ['Pending Training Requests',$value('pending_training_requests'),'training','fa-user-clock','warning'],
 ['Training Completion Rate',number_format((float)$value('training_completion_rate'),1).'%','training','fa-circle-check','success'],
 ['Documents Pending Review',$value('documents_pending_review'),'documents','fa-file-circle-question','warning'],
 ['Documents Expiring Soon',$value('documents_expiring_soon'),'documents','fa-file-circle-exclamation','danger'],
 ['Contracts Expiring Soon',$value('contracts_expiring_soon'),'documents_contracts','fa-file-contract','danger'],
 ['Missing Government Records',$value('missing_government_records'),'compliance','fa-id-card','warning'],
 ['Compliance Issues',$value('compliance_issues'),'compliance','fa-shield-halved','danger'],
 ['Pending Benefit Applications',$value('pending_benefit_applications'),'benefits','fa-hand-holding-heart','warning'],
 ['Active Loans',$value('active_loans'),'benefits_loans','fa-money-check-dollar','info'],
 ['Pending Loan Applications',$value('pending_loan_applications'),'benefits_loans','fa-hourglass-half','warning'],
 ['Pending Separation Requests',$value('pending_separation_requests'),'separation','fa-person-walking-arrow-right','danger'],
 ['Pending Exit Clearances',$value('pending_exit_clearances'),'separation','fa-list-check','warning'],
];
?>
<div id="main-content">
 <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>
 <div class="glass-card dashboard-toolbar mb-4 mt-3"><div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"><div class="dashboard-tab"><i class="fa-solid fa-table-cells-large"></i><span>Admin / HR Dashboard</span></div><div class="d-flex gap-2 flex-wrap"><a href="index.php?page=dashboard" class="btn btn-outline-primary btn-sm dashboard-refresh"><i class="fa-solid fa-rotate"></i>Refresh</a><button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#dashboardPreferencesModal"><i class="fa-solid fa-sliders"></i>Customize Filters &amp; Layout</button></div></div></div>
 <div class="dashboard-loading-state" hidden><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Refreshing live dashboard data...</span></div>
 <?php if (!empty($dashboard_error)): ?><div class="alert alert-danger mb-4"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Dashboard unavailable</strong><div><?= htmlspecialchars($dashboard_error); ?></div></div></div><?php endif; ?>
 <div class="reporting-scope mb-4"><div class="scope-icon"><i class="fa-solid fa-building-shield"></i></div><div><span>ORGANIZATION-WIDE REPORTING SCOPE</span><strong><?= date('M j, Y'); ?> | All departments | All branches</strong><small>Live summaries are calculated from current Core 3 records whenever this page loads.</small></div></div>
 <section id="dashboardKpis"><div class="section-kicker">KEY METRICS &amp; HEALTH INDICATORS</div><h5 class="section-heading">Organization Overview</h5><div class="dashboard-metric-grid mb-4">
 <?php foreach ($metrics as [$label,$metricValue,$route,$icon,$tone]): ?><a href="index.php?page=<?= htmlspecialchars($route); ?>" class="dashboard-metric-card text-decoration-none"><div class="dashboard-metric-copy"><span><?= htmlspecialchars($label); ?></span><strong><?= htmlspecialchars((string)$metricValue); ?></strong><small>View records <i class="fa-solid fa-arrow-right"></i></small></div><div class="dashboard-metric-icon tone-<?= htmlspecialchars($tone); ?>"><i class="fa-solid <?= htmlspecialchars($icon); ?>"></i></div></a><?php endforeach; ?>
 </div></section>
 <section id="dashboardOperations">
  <div class="section-kicker">WORKFLOW MONITORING</div><h5 class="section-heading">Actions, Activity &amp; Deadlines</h5>
  <div class="row g-3 mb-4 dashboard-operations-grid">
   <div class="col-xl-4"><div class="glass-card dashboard-list-card h-100">
    <div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-list-check"></i></span><div><h6>Pending Actions</h6><small>Records requiring Admin or HR attention</small></div></div><span class="badge badge-soft-warning"><?= count($pending_actions); ?></span></div>
    <div class="dashboard-list-body">
     <?php if (empty($pending_actions)): ?><div class="dashboard-empty"><i class="fa-regular fa-circle-check"></i><strong>All caught up</strong><span>No pending actions require attention.</span></div>
     <?php else: foreach ($pending_actions as $item): ?><a href="index.php?page=<?= htmlspecialchars($item['route']); ?>" class="dashboard-list-item"><span class="dashboard-list-dot tone-warning"></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($item['title']); ?></strong><small><?= htmlspecialchars($item['detail']); ?></small></span><span class="badge badge-soft-primary"><?= htmlspecialchars($item['item_type']); ?></span></a><?php endforeach; endif; ?>
    </div>
   </div></div>
   <div class="col-xl-4"><div class="glass-card dashboard-list-card h-100">
    <div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-clock-rotate-left"></i></span><div><h6>Recent Activities</h6><small>Latest submissions, reviews and updates</small></div></div><a href="index.php?page=admin_logs" class="btn btn-outline-primary btn-sm">Audit Trail</a></div>
    <div class="dashboard-list-body">
     <?php if (empty($recent_activities)): ?><div class="dashboard-empty"><i class="fa-solid fa-clock-rotate-left"></i><strong>No recent activity</strong><span>New workflow activity will appear here.</span></div>
     <?php else: foreach ($recent_activities as $activity): ?><div class="dashboard-list-item"><span class="dashboard-list-dot tone-info"></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($activity['actor']); ?> - <?= htmlspecialchars(ucwords(str_replace('_',' ',$activity['action']))); ?></strong><small><?= htmlspecialchars($activity['description'] ?: $activity['module']); ?></small><time><?= date('M j, g:i A',strtotime($activity['created_at'])); ?></time></span><span class="badge badge-soft-info"><?= htmlspecialchars($activity['module']); ?></span></div><?php endforeach; endif; ?>
    </div>
   </div></div>
   <div class="col-xl-4"><div class="glass-card dashboard-list-card h-100">
    <div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-regular fa-calendar-days"></i></span><div><h6>Upcoming Deadlines</h6><small>Training, contracts, documents and compliance</small></div></div><span class="badge badge-soft-danger"><?= count($upcoming_deadlines); ?></span></div>
    <div class="dashboard-list-body">
     <?php if (empty($upcoming_deadlines)): ?><div class="dashboard-empty"><i class="fa-regular fa-calendar-check"></i><strong>No upcoming deadlines</strong><span>No tracked deadlines fall within the next 90 days.</span></div>
     <?php else: foreach ($upcoming_deadlines as $deadline): ?><a href="index.php?page=<?= htmlspecialchars($deadline['route']); ?>" class="dashboard-list-item"><span class="dashboard-date-tile"><b><?= date('d',strtotime($deadline['due_date'])); ?></b><small><?= date('M',strtotime($deadline['due_date'])); ?></small></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($deadline['title']); ?></strong><small><?= htmlspecialchars($deadline['detail']); ?></small></span><span class="badge badge-soft-danger"><?= htmlspecialchars($deadline['item_type']); ?></span></a><?php endforeach; endif; ?>
    </div>
   </div></div>
  </div>
 </section>
 <section class="glass-card p-4 mb-4" id="dashboardQuickAccess">
  <div class="d-flex align-items-center justify-content-between mb-3"><div><div class="section-kicker">CORE 3 MODULES</div><h5 class="mb-0">Management Quick Access</h5></div><small class="text-secondary">Open a complete module workspace</small></div>
  <div class="row g-3">
  <?php $links=[['training','Employee Development','Training, courses and skills','fa-graduation-cap'],['documents','Document Management','Contracts and employee files','fa-folder-open'],['compliance','Compliance','Government contributions','fa-landmark'],['benefits','Benefits & Loans','Claims, allowances and loans','fa-hand-holding-dollar'],['separation','Separation & Clearance','Offboarding and final pay','fa-plane-departure'],['employees','Employee Directory','Employee records and profiles','fa-users']]; foreach($links as $link): ?>
   <div class="col-lg-4 col-md-6"><a href="index.php?page=<?= $link[0]; ?>" class="dashboard-quick-link"><span class="scope-icon"><i class="fa-solid <?= $link[3]; ?>"></i></span><span><strong><?= $link[1]; ?></strong><small><?= $link[2]; ?></small></span><i class="fa-solid fa-chevron-right"></i></a></div>
  <?php endforeach; ?>
  </div>
 </section>
 <div class="modal fade" id="dashboardPreferencesModal" tabindex="-1" aria-labelledby="dashboardPreferencesTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="dashboardPreferencesTitle"><i class="fa-solid fa-sliders me-2 text-primary"></i>Dashboard Preferences</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><p class="text-secondary small">Choose which dashboard sections are visible. Preferences are saved on this device.</p><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prefKpis" checked><label class="form-check-label" for="prefKpis">Organization metrics</label></div><div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="prefOperations" checked><label class="form-check-label" for="prefOperations">Actions, activity and deadlines</label></div><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="prefQuickAccess" checked><label class="form-check-label" for="prefQuickAccess">Module quick access</label></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" id="resetDashboardPreferences">Reset</button><button type="button" class="btn btn-primary" id="saveDashboardPreferences">Apply &amp; Save</button></div></div></div></div>
 <script>document.addEventListener('DOMContentLoaded',function(){const d={kpis:true,operations:true,quickAccess:true};const read=()=>{try{return Object.assign({},d,JSON.parse(localStorage.getItem('core3_dashboard_preferences')||'{}'))}catch(e){return d}};const apply=p=>{dashboardKpis.hidden=!p.kpis;dashboardOperations.hidden=!p.operations;dashboardQuickAccess.hidden=!p.quickAccess;prefKpis.checked=p.kpis;prefOperations.checked=p.operations;prefQuickAccess.checked=p.quickAccess};apply(read());document.querySelectorAll('.dashboard-refresh').forEach(a=>a.addEventListener('click',()=>document.querySelector('.dashboard-loading-state').hidden=false));saveDashboardPreferences.addEventListener('click',function(){const p={kpis:prefKpis.checked,operations:prefOperations.checked,quickAccess:prefQuickAccess.checked};localStorage.setItem('core3_dashboard_preferences',JSON.stringify(p));apply(p);bootstrap.Modal.getOrCreateInstance(document.getElementById('dashboardPreferencesModal')).hide();showToast('success','Dashboard preferences saved.')});resetDashboardPreferences.addEventListener('click',function(){localStorage.removeItem('core3_dashboard_preferences');apply(d)})});</script>
</div>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$value = static fn($key, $default = 0) => $summary[$key] ?? $default;
$contract = $details['contract'] ?? null;
$government = $details['government'] ?? [];
$contributions = $details['contributions'] ?? [];
$separation = $details['separation'] ?? null;
$clearances = $details['clearances'] ?? [];
$cleared = count(array_filter($clearances, static fn($row) => ($row['status'] ?? '') === 'Cleared'));
$clearanceTotal = count($clearances);
$clearancePercent = $clearanceTotal ? (int)round(($cleared / $clearanceTotal) * 100) : 0;
$metrics = [
 ['My Upcoming Trainings',$value('upcoming_trainings'),'training','fa-calendar-days','primary'],
 ['My Completed Trainings',$value('completed_trainings'),'training','fa-circle-check','success'],
 ['My Certificates',$value('certificates'),'training','fa-certificate','info'],
 ['My Pending Documents',$value('pending_documents'),'documents','fa-file-circle-question','warning'],
 ['My Expiring Documents',$value('expiring_documents'),'documents','fa-file-circle-exclamation','danger'],
 ['Available Benefits',$value('available_benefits'),'benefits','fa-heart-circle-plus','success'],
 ['My Benefit Applications',$value('benefit_applications'),'benefits','fa-hand-holding-heart','primary'],
 ['My Active Loans',$value('active_loans'),'benefits_loans','fa-money-check-dollar','info'],
 ['Remaining Loan Balance','PHP '.number_format((float)$value('remaining_loan_balance'),2),'benefits_loans','fa-wallet','warning'],
 ['My Notifications',$value('notifications'),'notifications','fa-bell','primary'],
];
?>
<div id="main-content">
 <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>
 <div class="glass-card dashboard-toolbar mb-4 mt-3"><div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"><div><div class="section-kicker">EMPLOYEE</div><h5 class="section-heading mb-0">Welcome, <?= htmlspecialchars($user['full_name']); ?></h5></div><div class="d-flex gap-2"><a href="index.php?page=dashboard" class="btn btn-outline-primary btn-sm dashboard-refresh"><i class="fa-solid fa-rotate"></i>Refresh</a><a href="index.php?page=notifications" class="btn btn-primary btn-sm"><i class="fa-regular fa-bell"></i>Notifications</a></div></div></div>
 <div class="dashboard-loading-state" hidden><span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Refreshing your dashboard data...</span></div>
 <?php if (!empty($dashboard_error)): ?><div class="alert alert-danger mb-4"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Dashboard unavailable</strong><div><?= htmlspecialchars($dashboard_error); ?></div></div></div><?php endif; ?>
 <div class="reporting-scope mb-4"><div class="scope-icon"><i class="fa-regular fa-user"></i></div><div><span>MY EMPLOYEE RECORD</span><strong>Personal dashboard and self-service activity</strong><small>Only records linked to your authenticated employee account are included.</small></div></div>

 <div class="section-kicker">MY STATUS</div><h5 class="section-heading">Employee Overview</h5>
 <div class="dashboard-metric-grid employee-dashboard-metrics mb-4"><?php foreach($metrics as [$label,$metricValue,$route,$icon,$tone]): ?><a href="index.php?page=<?= htmlspecialchars($route); ?>" class="dashboard-metric-card text-decoration-none"><div class="dashboard-metric-copy"><span><?= htmlspecialchars($label); ?></span><strong><?= htmlspecialchars((string)$metricValue); ?></strong><small>View my records <i class="fa-solid fa-arrow-right"></i></small></div><div class="dashboard-metric-icon tone-<?= $tone; ?>"><i class="fa-solid <?= $icon; ?>"></i></div></a><?php endforeach; ?></div>

 <div class="section-kicker">MY EMPLOYMENT &amp; COMPLIANCE</div><h5 class="section-heading">Record Status</h5>
 <div class="row g-3 mb-4">
  <div class="col-xl-4 col-md-6"><div class="glass-card p-4 h-100 employee-status-card"><div class="dashboard-list-heading px-0 pt-0"><div><span class="scope-icon"><i class="fa-solid fa-file-contract"></i></span><div><h6>My Contract Status</h6><small>Latest employment contract</small></div></div></div>
   <?php if (!$contract): ?><div class="dashboard-empty compact"><i class="fa-regular fa-file"></i><strong>No contract record</strong><span>Contact HR if a contract should be available.</span></div>
   <?php else: ?><div class="status-summary-value"><?= htmlspecialchars($contract['status']); ?></div><span class="badge badge-soft-<?= $contract['approval_status']==='Approved'?'success':'warning'; ?>"><?= htmlspecialchars($contract['approval_status']); ?></span><dl class="dashboard-definition-list"><div><dt>Type</dt><dd><?= htmlspecialchars($contract['contract_type']); ?></dd></div><div><dt>Effective</dt><dd><?= date('M j, Y',strtotime($contract['start_date'])); ?></dd></div><div><dt>Expires</dt><dd><?= $contract['end_date'] ? date('M j, Y',strtotime($contract['end_date'])) : 'No expiry'; ?></dd></div></dl><?php endif; ?>
  </div></div>
  <div class="col-xl-4 col-md-6"><div class="glass-card p-4 h-100 employee-status-card"><div class="dashboard-list-heading px-0 pt-0"><div><span class="scope-icon"><i class="fa-solid fa-id-card"></i></span><div><h6>Government Record Status</h6><small>Required statutory identifiers</small></div></div><span class="badge badge-soft-<?= $value('missing_government_records')?'warning':'success'; ?>"><?= $value('missing_government_records'); ?> missing</span></div>
   <div class="government-status-list"><?php foreach(['sss_no'=>'SSS','philhealth_no'=>'PhilHealth','pagibig_no'=>'Pag-IBIG','tin_no'=>'TIN'] as $field=>$label): $present=!empty(trim((string)($government[$field]??''))); ?><div><span><?= $label; ?></span><span class="badge badge-soft-<?= $present?'success':'warning'; ?>"><i class="fa-solid fa-<?= $present?'check':'exclamation'; ?>"></i><?= $present?'Recorded':'Missing'; ?></span></div><?php endforeach; ?></div>
   <a href="index.php?page=my_profile" class="btn btn-outline-primary btn-sm mt-3">View My Profile</a>
  </div></div>
  <div class="col-xl-4"><div class="glass-card p-4 h-100 employee-status-card"><div class="dashboard-list-heading px-0 pt-0"><div><span class="scope-icon"><i class="fa-solid fa-landmark"></i></span><div><h6>My Contribution Summary</h6><small><?= date('Y'); ?> employee contributions</small></div></div><span class="badge badge-soft-info"><?= (int)($contributions['periods']??0); ?> periods</span></div>
   <div class="status-summary-value">PHP <?= number_format((float)($contributions['total']??0),2); ?></div><dl class="dashboard-definition-list contribution-list"><div><dt>SSS</dt><dd>PHP <?= number_format((float)($contributions['sss']??0),2); ?></dd></div><div><dt>PhilHealth</dt><dd>PHP <?= number_format((float)($contributions['philhealth']??0),2); ?></dd></div><div><dt>Pag-IBIG</dt><dd>PHP <?= number_format((float)($contributions['pagibig']??0),2); ?></dd></div><div><dt>BIR Tax</dt><dd>PHP <?= number_format((float)($contributions['bir']??0),2); ?></dd></div></dl>
  </div></div>
 </div>

 <div class="section-kicker">MY WORKFLOWS</div><h5 class="section-heading">Current Activity</h5>
 <div class="row g-3 mb-4">
  <div class="col-xl-6"><div class="glass-card dashboard-list-card h-100"><div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-graduation-cap"></i></span><div><h6>My Upcoming Trainings</h6><small>Approved and pending registrations</small></div></div><a href="index.php?page=training" class="btn btn-outline-primary btn-sm">Open Training</a></div><div class="dashboard-list-body">
   <?php if(empty($details['upcoming_trainings'])): ?><div class="dashboard-empty"><i class="fa-solid fa-calendar-check"></i><strong>No upcoming training</strong><span>New assignments and registrations will appear here.</span></div>
   <?php else: foreach($details['upcoming_trainings'] as $training): ?><a href="index.php?page=training" class="dashboard-list-item"><span class="dashboard-date-tile"><b><?= date('d',strtotime($training['start_date'])); ?></b><small><?= date('M',strtotime($training['start_date'])); ?></small></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($training['title']); ?></strong><small><?= htmlspecialchars($training['venue'] ?: 'Venue TBA'); ?></small></span><span class="badge badge-soft-<?= $training['status']==='Approved'?'success':'warning'; ?>"><?= htmlspecialchars($training['status']); ?></span></a><?php endforeach; endif; ?>
  </div></div></div>

  <div class="col-xl-6"><div class="glass-card dashboard-list-card h-100"><div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-heart-pulse"></i></span><div><h6>My Benefits &amp; Loans</h6><small>Active enrollments and current financing</small></div></div><a href="index.php?page=benefits" class="btn btn-outline-primary btn-sm">Open Benefits</a></div><div class="dashboard-list-body">
   <?php if(empty($details['benefits']) && empty($details['loans'])): ?><div class="dashboard-empty"><i class="fa-solid fa-hand-holding-heart"></i><strong>No active records</strong><span>Your benefit enrollments and loan applications will appear here.</span></div><?php else: ?>
    <?php foreach($details['benefits'] as $benefit): ?><a href="index.php?page=benefits" class="dashboard-list-item"><span class="dashboard-list-dot tone-success"></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($benefit['name']); ?></strong><small><?= htmlspecialchars($benefit['type']); ?> benefit</small></span><span class="badge badge-soft-success"><?= htmlspecialchars($benefit['status']); ?></span></a><?php endforeach; ?>
    <?php foreach($details['loans'] as $loan): ?><a href="index.php?page=benefits_loans" class="dashboard-list-item"><span class="dashboard-list-dot tone-info"></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($loan['loan_type']); ?> Loan</strong><small>Balance: PHP <?= number_format((float)$loan['balance_remaining'],2); ?> | Monthly: PHP <?= number_format((float)$loan['monthly_deduction'],2); ?></small></span><span class="badge badge-soft-info"><?= htmlspecialchars($loan['status']); ?></span></a><?php endforeach; ?>
   <?php endif; ?>
  </div></div></div>

  <div class="col-xl-6"><div class="glass-card dashboard-list-card h-100"><div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-route"></i></span><div><h6>My Separation &amp; Exit Clearance</h6><small>Latest request and department progress</small></div></div><a href="index.php?page=separation" class="btn btn-outline-primary btn-sm">View Clearance</a></div><div class="p-4">
   <?php if(!$separation): ?><div class="dashboard-empty compact"><i class="fa-solid fa-user-check"></i><strong>No separation request</strong><span>You have no active offboarding workflow.</span></div>
   <?php else: ?><div class="d-flex align-items-center justify-content-between gap-3 mb-3"><div><div class="status-summary-value"><?= htmlspecialchars($separation['status']); ?></div><small class="text-secondary"><?= htmlspecialchars($separation['separation_type']); ?> | Effective <?= date('M j, Y',strtotime($separation['effective_date'])); ?></small></div><strong class="text-primary"><?= $clearancePercent; ?>%</strong></div><div class="progress dashboard-progress mb-3"><div class="progress-bar" style="width:<?= $clearancePercent; ?>%"></div></div><div class="government-status-list"><?php foreach($clearances as $clearance): ?><div><span><?= htmlspecialchars($clearance['department_name']); ?></span><span class="badge badge-soft-<?= $clearance['status']==='Cleared'?'success':($clearance['status']==='Rejected'?'danger':'warning'); ?>"><?= htmlspecialchars($clearance['status']); ?></span></div><?php endforeach; ?></div><?php endif; ?>
  </div></div></div>

  <div class="col-xl-6"><div class="glass-card dashboard-list-card h-100"><div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-regular fa-bell"></i></span><div><h6>My Notifications</h6><small>Recent account and self-service updates</small></div></div><a href="index.php?page=notifications" class="btn btn-outline-primary btn-sm">View All</a></div><div class="dashboard-list-body">
   <?php if(empty($details['notifications'])): ?><div class="dashboard-empty"><i class="fa-regular fa-bell-slash"></i><strong>No notifications yet</strong><span>Updates linked to your account will appear here.</span></div>
   <?php else: foreach($details['notifications'] as $note): ?><a href="index.php?page=notification_open&amp;id=<?= (int)$note['id']; ?>" class="dashboard-list-item"><span class="dashboard-list-dot tone-primary"></span><span class="dashboard-list-copy"><strong><?= htmlspecialchars($note['title']); ?></strong><small><?= htmlspecialchars($note['message']); ?></small><time><?= date('M j, g:i A',strtotime($note['created_at'])); ?></time></span><span class="badge badge-soft-primary"><?= empty($note['is_read'])?'Unread':htmlspecialchars(ucfirst($note['type'])); ?></span></a><?php endforeach; endif; ?>
  </div></div></div>
 </div>
 <script>document.querySelectorAll('.dashboard-refresh').forEach(a=>a.addEventListener('click',()=>document.querySelector('.dashboard-loading-state').hidden=false));</script>
</div>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

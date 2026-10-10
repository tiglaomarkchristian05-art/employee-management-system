<?php
require APP_PATH.'Views/layouts/header.php';
require APP_PATH.'Views/layouts/sidebar.php';
$summary=$system_summary??[];
$events=$recent_system_events??[];
$total=(int)($summary['users']??0);
$active=(int)($summary['active_users']??0);
$activeRate=$total?(int)round(($active/$total)*100):0;
$metrics=[
 ['Total User Accounts',$total,'admin_users','fa-users','primary','Manage identities and access'],
 ['Active Accounts',$active,'admin_users','fa-user-check','success',$activeRate.'% of all accounts'],
 ['Configured Roles',(int)($summary['roles']??0),'admin_users','fa-user-shield','info','Permission groups in use'],
 ['Recorded Audit Events',(int)($summary['audit_events']??0),'admin_logs','fa-receipt','warning','Review system activity'],
];
$tools=[
 ['admin_users','fa-users-gear','User Accounts','Create accounts, assign roles, reset passwords and control account status.'],
 ['admin_settings','fa-sliders','System Settings','Maintain organization settings and security-sensitive configuration.'],
 ['admin_logs','fa-clipboard-list','Audit Trail','Inspect account, authentication and system activity records.'],
 ['admin_backup','fa-database','Database Backup','Export a protected copy of the current application database.'],
];
?>
<div id="main-content">
 <?php require APP_PATH.'Views/layouts/navbar.php'; ?>

 <div class="glass-card dashboard-toolbar mb-4 mt-3">
  <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
   <div><div class="section-kicker">System Administration</div><h5 class="section-heading mb-0">Welcome, <?=htmlspecialchars($user['full_name']??'System Administrator')?></h5><small class="text-secondary">Secure access, configuration, auditing and technical maintenance</small></div>
   <div class="d-flex flex-wrap gap-2"><a href="index.php?page=admin_users" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i>Manage Accounts</a><a href="index.php?page=dashboard" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-rotate"></i>Refresh</a></div>
  </div>
 </div>

 <?php if(!empty($dashboard_error)): ?><div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i><?=htmlspecialchars($dashboard_error)?></div><?php endif; ?>

 <div class="reporting-scope mb-4">
  <div class="scope-icon"><i class="fa-solid fa-shield-halved"></i></div>
  <div><span>ROLE RESPONSIBILITY</span><strong>System health, security and access governance</strong><small>Operational HR decisions remain assigned to HR Manager users.</small></div>
  <span class="badge badge-soft-success ms-auto">System Online</span>
 </div>

 <section class="mb-4">
  <div class="section-kicker">SYSTEM OVERVIEW</div><h5 class="section-heading">Access &amp; Security Summary</h5>
  <div class="dashboard-metric-grid">
   <?php foreach($metrics as [$label,$value,$route,$icon,$tone,$hint]): ?>
    <a href="index.php?page=<?=htmlspecialchars($route)?>" class="dashboard-metric-card text-decoration-none">
     <div class="dashboard-metric-copy"><span><?=htmlspecialchars($label)?></span><strong><?=number_format($value)?></strong><small><?=htmlspecialchars($hint)?> <i class="fa-solid fa-arrow-right"></i></small></div>
     <div class="dashboard-metric-icon tone-<?=htmlspecialchars($tone)?>"><i class="fa-solid <?=htmlspecialchars($icon)?>"></i></div>
    </a>
   <?php endforeach; ?>
  </div>
 </section>

 <div class="row g-3 mb-4">
  <div class="col-xl-8">
   <section class="glass-card p-4 h-100">
    <div class="d-flex align-items-center justify-content-between mb-3"><div><div class="section-kicker">ADMINISTRATION</div><h5 class="section-heading mb-0">System Management Tools</h5></div><span class="badge badge-soft-primary">Admin only</span></div>
    <div class="row g-3">
     <?php foreach($tools as [$route,$icon,$title,$description]): ?>
      <div class="col-md-6"><a href="index.php?page=<?=$route?>" class="dashboard-quick-link h-100"><span class="scope-icon"><i class="fa-solid <?=$icon?>"></i></span><span><strong><?=htmlspecialchars($title)?></strong><small><?=htmlspecialchars($description)?></small></span><i class="fa-solid fa-chevron-right"></i></a></div>
     <?php endforeach; ?>
    </div>
   </section>
  </div>
  <div class="col-xl-4">
   <section class="glass-card dashboard-list-card h-100">
    <div class="dashboard-list-heading"><div><span class="scope-icon"><i class="fa-solid fa-clock-rotate-left"></i></span><div><h6>Recent Security Activity</h6><small>Latest system administration events</small></div></div><a href="index.php?page=admin_logs" class="btn btn-outline-primary btn-sm">View All</a></div>
    <div class="dashboard-list-body">
     <?php if(!$events): ?><div class="dashboard-empty compact"><i class="fa-solid fa-shield"></i><strong>No recent security events</strong><span>New activity will appear here.</span></div>
     <?php else: foreach($events as $event): ?>
      <div class="dashboard-list-item"><span class="dashboard-list-dot tone-info"></span><span class="dashboard-list-copy"><strong><?=htmlspecialchars(ucwords(strtolower(str_replace('_',' ',$event['action']))))?></strong><small><?=htmlspecialchars($event['actor'].' · '.$event['module'])?></small><time><?=date('M j, Y · g:i A',strtotime($event['created_at']))?></time></span></div>
     <?php endforeach; endif; ?>
    </div>
   </section>
  </div>
 </div>
</div>
<?php require APP_PATH.'Views/layouts/footer.php'; ?>

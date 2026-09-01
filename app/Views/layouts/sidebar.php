<?php
$currentPage=$_GET['page']??'dashboard';$currentNav=$_GET['nav']??'';$user=Auth::user();$isEmployee=Auth::isSelfService();$isSuperAdmin=Auth::hasRole(['Super Admin']);
$link=static function($key,$page,$icon,$label,$subtitle,$pages=[],$default=false){return compact('key','page','icon','label','subtitle','pages','default');};
if($isEmployee){$items=[
 ['section'=>'My Development'],
 $link('my_trainings','training','fa-graduation-cap','My Trainings','Assigned, available and completed training',['training','training_courses','training_details','training_quiz','training_matrix'],true),
 $link('recommended_training','training_recommendations','fa-wand-magic-sparkles','Recommended for You','Explainable training recommendations',['training_recommendations'],true),
 $link('my_certificates','training','fa-certificate','My Certificates','Completed training certificates'),
 ['section'=>'My Documents'],
 $link('my_documents','documents','fa-folder-open','My Documents','Requirements and personal files',['documents'],true),
 $link('my_contract','documents_contracts','fa-file-contract','My Contract','Contract details and downloads',['documents_contracts'],true),
 ['section'=>'My Compliance'],
 $link('government_information','compliance','fa-id-card','Government Information','SSS, PhilHealth, Pag-IBIG and TIN',['compliance','compliance_bir2316'],true),
 $link('contribution_history','compliance','fa-receipt','Contribution History','Monthly statutory contribution records'),
 $link('my_corrections','compliance','fa-pen-to-square','Correction Requests','Track government record corrections'),
 ['section'=>'My Benefits'],
 $link('available_benefits','benefits','fa-hand-holding-heart','Available Benefits','Eligibility and benefit programs',['benefits'],true),
 $link('my_benefit_applications','benefits','fa-file-circle-check','My Applications','Benefit application status'),
 $link('my_loans','benefits_loans','fa-money-check-dollar','My Loans','Applications, schedules and balances',['benefits_loans'],true),
 ['section'=>'My Employment'],
 $link('my_separation','separation','fa-person-walking-arrow-right','Resignation / Separation','Submit and track separation requests',['separation'],true),
 $link('my_exit_clearance','separation','fa-list-check','My Exit Clearance','Department checklist and progress',['separation_clearance','separation_coe'],true),
 ['section'=>'Account'],
 $link('notifications','notifications','fa-bell','Notifications','Workflow updates and reminders',['notifications'],true),
 $link('profile','my_profile','fa-user','Profile','Personal and employment information',['my_profile'],true),
];}else{$items=[
 ['section'=>'Employee Development'],
 $link('training_management','training','fa-graduation-cap','Training Management','Create, assign and manage training',['training','training_details','training_quiz'],true),
 $link('ai_training_needs','training_recommendations','fa-wand-magic-sparkles','Training Needs Analysis','AI recommendations and HR review',['training_recommendations'],true),
 $link('training_calendar','training_courses','fa-calendar-days','Training Calendar','Schedules and upcoming training',['training_courses'],true),
 $link('training_records','training','fa-clipboard-check','Training Records','Attendance, results and history'),
 $link('certificates','training','fa-certificate','Certificates','Issued employee certificates'),
 ['section'=>'Document & Contract'],
 $link('document_management','documents','fa-folder-open','Document Management','Types, requirements and employee files',['documents'],true),
 $link('document_review_queue','documents','fa-list-check','Review Queue','Submitted and returned documents'),
 $link('contracts','documents_contracts','fa-file-contract','Contracts','Contracts, renewals and versions',['documents_contracts'],true),
 $link('expiring_documents','documents','fa-calendar-xmark','Expiring Documents','Expiration and renewal monitoring'),
 ['section'=>'Government Compliance'],
 $link('government_records','compliance','fa-id-card','Government Records','Employee statutory information',['compliance','compliance_bir2316'],true),
 $link('contribution_management','compliance','fa-coins','Contribution Management','Create and correct contributions'),
 $link('compliance_monitoring','compliance','fa-shield-halved','Compliance Monitoring','Missing records and verification'),
 $link('correction_requests','compliance','fa-pen-to-square','Correction Requests','Review proposed government changes'),
 $link('compliance_reports','compliance','fa-chart-column','Compliance Reports','Filters and internal CSV reports'),
 ['section'=>'Benefits & Loans'],
 $link('benefit_management','benefits','fa-hand-holding-heart','Benefit Management','Programs, eligibility and periods',['benefits'],true),
 $link('benefit_applications','benefits','fa-file-circle-check','Benefit Applications','Review and process applications'),
 $link('loan_management','benefits_loans','fa-building-columns','Loan Management','Programs, schedules and payments',['benefits_loans'],true),
 $link('loan_applications','benefits_loans','fa-money-check-dollar','Loan Applications','Review and release employee loans'),
 ['section'=>'Separation'],
 $link('separation_requests','separation','fa-person-walking-arrow-right','Separation Requests','Review and process requests',['separation'],true),
 $link('exit_clearance','separation','fa-list-check','Exit Clearance','Department clearance monitoring',['separation_clearance'],true),
 $link('exit_interviews','separation','fa-comments','Exit Interviews','Recorded offboarding interviews'),
 $link('separated_employees','separation','fa-box-archive','Separated Employees','Completed and archived separations'),
 ['section'=>'System'],
 $link('reports','reports','fa-chart-pie','Reports','Compliance and operational reporting',['reports'],true),
 $link('notifications','notifications','fa-bell','Notifications','System workflow updates',['notifications'],true),
 $link('audit_trail','admin_logs','fa-receipt','Audit Trail','Security and activity history',['admin_logs'],true),
 ...($isSuperAdmin?[
 $link('system_settings','admin_settings','fa-gears','System Settings','Organization and application configuration',['admin_settings'],true),
 $link('database_backup','admin_backup','fa-database','Database Backup','Export and protected recovery tools',['admin_backup'],true),
 ]:[]),
];}
$activeFor=static function($item)use($currentPage,$currentNav){if($currentNav!=='')return $currentNav===$item['key'];return !empty($item['default'])&&in_array($currentPage,$item['pages']?:[$item['page']],true);};
?>
<aside id="sidebar" class="dashboard-sidebar" aria-label="Primary navigation"><div>
 <div class="sidebar-brand"><div class="bi-brand-card sidebar-brand-card"><div class="sidebar-brand-icon"><img src="assets/images/logo-icon.svg" alt="Great Solomon Manpower Services Inc."></div><div class="brand-text sidebar-brand-copy"><h6 class="sidebar-brand-title">Core 3</h6><small><?=$isEmployee?'Employee':'Employee Development,<br>Compliance &amp; Benefits'?></small></div></div></div>
 <ul class="nav flex-column sidebar-nav">
  <li class="nav-item"><a class="nav-link sidebar-main-link <?=$currentPage==='dashboard'?'active':''?>" href="index.php?page=dashboard" <?=$currentPage==='dashboard'?'aria-current="page"':''?>><span class="sidebar-main-link-icon material-symbols-outlined">dashboard</span><span class="nav-copy sidebar-label"><strong>Dashboard</strong><small><?=$isEmployee?'My Employee Overview':'Core 3 Overview &amp; Insights'?></small></span></a></li>
  <?php foreach($items as $item):?>
   <?php if(isset($item['section'])):?><li class="nav-item"><div class="sidebar-section-title sidebar-section-label"><?=htmlspecialchars($item['section'])?></div></li>
   <?php else:$active=$activeFor($item);?><li class="nav-item"><a class="nav-link sidebar-subsystem-link <?=$active?'active':''?>" href="index.php?page=<?=rawurlencode($item['page'])?>&amp;nav=<?=rawurlencode($item['key'])?>" title="<?=htmlspecialchars($item['label'])?>" <?=$active?'aria-current="page"':''?>><span class="sidebar-subsystem-link-icon"><i class="fa-solid <?=$item['icon']?>"></i></span><span class="nav-copy sidebar-label"><strong><?=htmlspecialchars($item['label'])?></strong><small><?=htmlspecialchars($item['subtitle'])?></small></span></a></li><?php endif;?>
  <?php endforeach;?>
 </ul>
</div></aside>

<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$totalNotifications=(int)($pagination['total']??count($notifications));
$readNotifications=max(0,$totalNotifications-(int)$unread_count);
$noteIcons=['training'=>'fa-graduation-cap','documents'=>'fa-folder-open','compliance'=>'fa-landmark','benefits'=>'fa-hand-holding-heart','loans'=>'fa-wallet','separation'=>'fa-user-check','system'=>'fa-bell'];
?>
<div id="main-content"><?php require APP_PATH . 'Views/layouts/navbar.php'; ?>
 <section class="notification-center-hero mt-3 mb-4">
  <div class="notification-center-heading">
   <span class="notification-center-hero-icon"><i class="fa-solid fa-bell"></i></span>
   <div><div class="section-kicker">ACCOUNT</div><h4>Notification Center</h4><p>Persistent workflow updates addressed only to your account.</p></div>
  </div>
  <?php if($unread_count>0): ?><form method="post" action="index.php?page=notifications_mark_all"><?= csrf_input(); ?><button class="btn notification-mark-all" type="submit"><i class="fa-solid fa-check-double"></i><span>Mark all as read</span></button></form><?php else: ?><span class="notification-caught-up"><i class="fa-solid fa-circle-check"></i>All caught up</span><?php endif; ?>
 </section>

 <div class="notification-summary-grid mb-4">
  <div class="notification-summary-card"><span class="tone-all"><i class="fa-regular fa-bell"></i></span><div><small>All updates</small><strong><?= number_format($totalNotifications); ?></strong></div></div>
  <div class="notification-summary-card"><span class="tone-unread"><i class="fa-solid fa-envelope"></i></span><div><small>Unread</small><strong><?= number_format((int)$unread_count); ?></strong></div></div>
  <div class="notification-summary-card"><span class="tone-read"><i class="fa-solid fa-envelope-open-text"></i></span><div><small>Read</small><strong><?= number_format($readNotifications); ?></strong></div></div>
 </div>

 <section class="notification-center-card mb-4">
  <header class="notification-center-card-header"><div><h5>Recent updates</h5><small>Click an update to open its related workflow and mark it as read.</small></div><span><?= number_format($totalNotifications); ?> total</span></header>
  <?php if (!$notifications): ?>
   <div class="notification-center-empty"><span><i class="fa-regular fa-bell-slash"></i></span><h6>No notifications yet</h6><p>Training, document, compliance, benefit, loan and clearance updates will appear here.</p></div>
  <?php else: ?><div class="notification-center-list">
   <?php foreach($notifications as $note):
    $module=strtolower((string)($note['module']??'system'));
    $icon=$noteIcons[$module]??'fa-bell';
    $tone=in_array(($note['type']??'info'),['success','warning','error','info'],true)?$note['type']:'info';
   ?>
    <a href="index.php?page=notification_open&amp;id=<?= (int)$note['id']; ?>" class="notification-center-item tone-<?= htmlspecialchars($tone); ?> <?= empty($note['is_read'])?'is-unread':'is-read'; ?>">
     <span class="notification-center-item-icon"><i class="fa-solid <?= $icon; ?>"></i></span>
     <span class="notification-center-item-copy">
      <span class="notification-center-item-heading"><strong><?= htmlspecialchars($note['title']); ?></strong><?php if(empty($note['is_read'])): ?><i class="notification-unread-dot" aria-label="Unread"></i><?php endif; ?></span>
      <span class="notification-center-module"><?= htmlspecialchars(ucwords(str_replace('_',' ',$module))); ?></span>
      <span class="notification-center-message"><?= htmlspecialchars($note['message']); ?></span>
     </span>
     <span class="notification-center-item-side"><time><i class="fa-regular fa-clock"></i><?= date('M j, Y · g:i A',strtotime($note['created_at'])); ?></time><span class="notification-read-state <?= empty($note['is_read'])?'unread':'read'; ?>"><?= empty($note['is_read'])?'Unread':'Read'; ?></span><i class="fa-solid fa-chevron-right notification-center-chevron"></i></span>
    </a>
   <?php endforeach; ?>
  </div><?php endif; ?>
 </section>

 <?php if(($pagination['pages']??1)>1): ?><nav class="notification-pagination" aria-label="Notification pages"><span>Page <?= (int)$pagination['page']; ?> of <?= (int)$pagination['pages']; ?></span><ul class="pagination mb-0">
  <?php for($p=1;$p<=$pagination['pages'];$p++): ?><li class="page-item <?= $p===$pagination['page']?'active':''; ?>"><a class="page-link" href="index.php?page=notifications&amp;p=<?= $p; ?>"><?= $p; ?></a></li><?php endfor; ?>
 </ul></nav><?php endif; ?>
</div><?php require APP_PATH . 'Views/layouts/footer.php'; ?>
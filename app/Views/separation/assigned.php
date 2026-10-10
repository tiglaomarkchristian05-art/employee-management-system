<?php require APP_PATH.'Views/layouts/header.php';require APP_PATH.'Views/layouts/sidebar.php'; ?>
<div id="main-content"><?php require APP_PATH.'Views/layouts/navbar.php'; ?>
<div class="glass-card p-4 my-3"><h4>Assigned Clearance Items</h4><p>Review the requirements assigned to you.</p></div>
<?php foreach($assigned_clearances as $item): ?>
<form class="glass-card p-4 mb-3 assigned-clearance" method="post" action="index.php?page=separation_update_clearance">
<?=csrf_input()?><input type="hidden" name="clearance_id" value="<?=(int)$item['id']?>">
<h5><?=htmlspecialchars($item['employee_name'].' — '.$item['department_name'])?></h5>
<p><?=htmlspecialchars($item['clearance_requirement'])?> · <?=$item['is_mandatory']?'Mandatory':'Optional'?></p>
<?php if($item['required_document_file']):?><a href="index.php?page=separation_file&amp;kind=clearance&amp;id=<?=(int)$item['id']?>">View submitted evidence</a><?php endif;?>
<label class="form-label">Status</label><select name="status" class="form-select mb-2"><?php foreach(['Pending','With Accountability','Cleared'] as $status):?><option <?=$item['status']===$status?'selected':''?>><?=$status?></option><?php endforeach;?></select>
<label class="form-label">Accountability</label><textarea name="accountability_details" class="form-control mb-2"><?=htmlspecialchars($item['accountability_details']??'')?></textarea>
<label class="form-label">Remarks</label><textarea name="comments" class="form-control mb-3"><?=htmlspecialchars($item['comments']??'')?></textarea><button class="btn btn-primary">Save Assigned Item</button>
</form><?php endforeach;?>
<?php if(!$assigned_clearances):?><div class="glass-card p-4">No clearance items are assigned to you.</div><?php endif;?></div>
<?php require APP_PATH.'Views/layouts/footer.php'; ?>
<script>$(function(){$('.assigned-clearance').on('submit',function(e){e.preventDefault();$.post(this.action,$(this).serialize(),null,'json').done(r=>{if(r.status==='success'){showToast('success',r.message);setTimeout(()=>location.reload(),500)}else showErrorDialog(r.message)}).fail(x=>showErrorDialog(x.responseJSON?.message||'Unable to update clearance.'));});});</script>

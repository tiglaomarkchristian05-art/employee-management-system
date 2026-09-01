<?php require APP_PATH.'Views/layouts/header.php';require APP_PATH.'Views/layouts/sidebar.php';$csrf=generate_csrf_token(); ?>
<div id="main-content"><?php require APP_PATH.'Views/layouts/navbar.php'; ?>
 <div class="module-toolbar my-3"><div class="module-toolbar-title"><span class="module-toolbar-icon"><i class="fa-solid fa-file-contract"></i></span><div><h5><?= $is_admin?'Contract Management':'My Employment Contracts'; ?></h5><small>Current agreements, expiry tracking, renewals and archived versions</small></div></div><div class="d-flex gap-2"><a href="index.php?page=documents" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-left"></i>Documents</a><?php if($is_admin): ?><button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contractModal"><i class="fa-solid fa-upload"></i>New Contract</button><?php endif; ?></div></div>
 <div class="glass-card p-4"><div class="table-responsive"><table class="table align-middle datatable-init"><thead><tr><?php if($is_admin): ?><th>Employee</th><th>Department</th><?php endif; ?><th>Contract</th><th>Version</th><th>Effective Period</th><th>Status</th><th>Acknowledgement</th><th>Actions</th></tr></thead><tbody><?php foreach($contracts as $c): ?><tr><?php if($is_admin): ?><td><strong><?= htmlspecialchars($c['first_name'].' '.$c['last_name']); ?></strong><small class="d-block text-secondary"><?= htmlspecialchars($c['employee_code']); ?></small></td><td><?= htmlspecialchars($c['department_name']??''); ?></td><?php endif; ?><td><span class="badge badge-soft-info"><?= htmlspecialchars($c['contract_type']); ?></span><small class="d-block text-secondary"><?= htmlspecialchars($c['remarks']??''); ?></small></td><td>v<?= (int)$c['version_no']; ?><?= $c['previous_contract_id']?'<small class="d-block text-secondary">Renewal</small>':''; ?></td><td><?= date('M j, Y',strtotime($c['start_date'])); ?><br><small class="<?= $c['end_date']&&strtotime($c['end_date'])<=strtotime('+60 days')?'text-danger fw-bold':'text-secondary'; ?>"><?= $c['end_date']?date('M j, Y',strtotime($c['end_date'])):'No expiry'; ?></small></td><td><span class="badge badge-soft-<?= $c['status']==='Active'?'success':($c['status']==='Renewed'?'info':'warning'); ?>"><?= htmlspecialchars($c['status']); ?></span></td><td><?= $c['acknowledged_at']?'<span class="badge badge-soft-success">Acknowledged</span>':'<span class="badge badge-soft-warning">Pending</span>'; ?></td><td><div class="d-flex gap-1"><a class="btn btn-outline-primary btn-sm" href="index.php?page=contracts_download&amp;id=<?= (int)$c['id']; ?>"><i class="fa-solid fa-download"></i></a><?php if($is_admin&&in_array($c['status'],['Active','Expired'],true)): ?><button class="btn btn-primary btn-sm btn-renew" data-id="<?= (int)$c['id']; ?>" data-name="<?= htmlspecialchars($c['first_name'].' '.$c['last_name']); ?>" data-bs-toggle="modal" data-bs-target="#renewModal">Renew</button><?php elseif(!$is_admin&&!$c['acknowledged_at']&&$c['status']==='Active'): ?><button class="btn btn-primary btn-sm btn-ack-contract" data-id="<?= (int)$c['id']; ?>">Acknowledge</button><?php endif; ?></div></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
<?php if($is_admin): ?>
<div class="modal fade" id="contractModal"><div class="modal-dialog modal-dialog-centered"><form class="modal-content contract-form" data-url="index.php?page=contracts_store" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Upload Employment Contract</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3"><input type="hidden" name="csrf_token" value="<?= $csrf; ?>"><div class="col-12"><label class="form-label">Employee *</label><select class="form-select" name="employee_id" required><?php foreach($employees as $e): ?><option value="<?= (int)$e['id']; ?>"><?= htmlspecialchars($e['first_name'].' '.$e['last_name'].' ('.$e['employee_code'].')'); ?></option><?php endforeach; ?></select></div><div class="col-12"><label class="form-label">Contract Type *</label><select class="form-select" name="contract_type"><?php foreach(['Employment','Probation','Regularization','Consultancy','Internship'] as $t): ?><option><?= $t; ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">Start Date *</label><input type="date" class="form-control" name="start_date" required></div><div class="col-md-6"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date"></div><div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks"></textarea></div><div class="col-12"><label class="form-label">Signed PDF *</label><input type="file" class="form-control" name="contract_file" accept=".pdf" required><small class="form-text">PDF only, maximum 10 MB.</small></div></div><div class="modal-footer"><button class="btn btn-primary">Upload Contract</button></div></form></div></div>
<div class="modal fade" id="renewModal"><div class="modal-dialog modal-dialog-centered"><form class="modal-content contract-form" data-url="index.php?page=contracts_renew" enctype="multipart/form-data"><div class="modal-header"><h5 class="modal-title">Renew Contract</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body row g-3"><input type="hidden" name="csrf_token" value="<?= $csrf; ?>"><input type="hidden" name="id" id="renewId"><p id="renewEmployee"></p><div class="col-12"><label class="form-label">Contract Type *</label><select class="form-select" name="contract_type"><?php foreach(['Employment','Probation','Regularization','Consultancy','Internship'] as $t): ?><option><?= $t; ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label">New Start Date *</label><input type="date" class="form-control" name="start_date" required></div><div class="col-md-6"><label class="form-label">New End Date</label><input type="date" class="form-control" name="end_date"></div><div class="col-12"><label class="form-label">Renewal Remarks</label><textarea class="form-control" name="remarks"></textarea></div><div class="col-12"><label class="form-label">Renewed PDF *</label><input type="file" class="form-control" name="contract_file" accept=".pdf" required></div></div><div class="modal-footer"><button class="btn btn-primary">Create Renewal</button></div></form></div></div>
<?php endif; ?>
<script>$(function(){
    $('.contract-form').attr('novalidate','novalidate').on('submit',function(e){
        e.preventDefault();
        const f=this;
        const invalid=f.querySelector(':invalid');
        if(invalid){
            Swal.fire('Required information','Complete all required fields and select a signed PDF.','warning').then(()=>invalid.focus());
            return;
        }
        const start=f.querySelector('[name="start_date"]')?.value;
        const end=f.querySelector('[name="end_date"]')?.value;
        if(start&&end&&end<start){
            Swal.fire('Invalid contract dates','Contract end date must be on or after its start date.','warning');
            return;
        }
        const fileInput=f.querySelector('[name="contract_file"]');
        const file=fileInput?.files?.[0];
        if(file&&!file.name.toLowerCase().endsWith('.pdf')){
            Swal.fire('Invalid contract file','Upload a PDF file only.','warning').then(()=>fileInput.focus());
            return;
        }
        const submit=f.querySelector('button[type="submit"], .modal-footer button');
        const original=submit?.innerHTML;
        if(submit){submit.disabled=true;submit.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Saving...';}
        $.ajax({url:f.dataset.url,type:'POST',data:new FormData(f),contentType:false,processData:false,dataType:'json'})
            .done(r=>{
                if(r.status==='success'){
                    showToast('success',r.message);
                    setTimeout(()=>location.reload(),700);
                }else{
                    Swal.fire('Contract not saved',r.message||'Please check the contract details.','error');
                }
            })
            .fail(xhr=>{
                const message=xhr.responseJSON?.message||'The contract could not be saved. Please check the entered details and file.';
                Swal.fire('Contract not saved',message,'error');
            })
            .always(()=>{if(submit){submit.disabled=false;submit.innerHTML=original;}});
    });
    $('.btn-renew').on('click',function(){$('#renewId').val(this.dataset.id);$('#renewEmployee').text('Employee: '+this.dataset.name)});
    $('.btn-ack-contract').on('click',function(){$.post('index.php?page=contracts_acknowledge',{id:this.dataset.id,csrf_token:'<?= $csrf; ?>'},r=>{showToast(r.status,r.message);if(r.status==='success')setTimeout(()=>location.reload(),600)},'json')});
});</script>
<?php require APP_PATH.'Views/layouts/footer.php'; ?>

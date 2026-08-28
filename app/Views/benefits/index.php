<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> Benefits & Reimbursements Management</h4>
            <p class="text-secondary mb-0">HMO health plans, de minimis allowances, claims processing, and employee reimbursement records</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($isHRAdmin): ?>
                <button class="btn btn-success btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#adminAllowanceModal">
                    <i class="fa-solid fa-gift me-1"></i> Grant Allowance (HR/Admin)
                </button>
                <button class="btn btn-outline-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#adminClaimModal">
                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> File Claim for Employee
                </button>
            <?php endif; ?>
            <button class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#claimModal">
                <i class="fa-solid fa-receipt me-1"></i> Apply Reimbursement
            </button>
            <a href="index.php?page=benefits_loans" class="btn btn-info btn-sm fw-bold shadow"><i class="fa-solid fa-piggy-bank me-1"></i> Loan Center</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php foreach ($plans as $plan): ?>
        <div class="col-md-3">
            <div class="glass-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                    <span class="badge badge-soft-info mb-2"><?= $plan['type']; ?></span>
                    <h6 class="fw-bold text-light mb-1"><?= htmlspecialchars($plan['name']); ?></h6>
                    <p class="text-secondary small mb-3"><?= htmlspecialchars($plan['description']); ?></p>
                </div>
                <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <small class="text-secondary">Coverage</small>
                    <span class="fw-bold text-success">₱<?= number_format($plan['coverage_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold text-light mb-3"><i class="fa-solid fa-list-check text-info me-2"></i> Employee Benefit Claims & Reimbursements</h5>
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Benefit Plan</th>
                        <th>Claim Type / Description</th>
                        <th>Amount</th>
                        <th>Receipt / Ref No.</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <?php if ($isHRAdmin): ?>
                        <th class="text-center">HR Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($claims as $claim): ?>
                    <tr>
                        <td class="fw-bold text-light">
                            <div><?= htmlspecialchars($claim['first_name'] . ' ' . $claim['last_name']); ?></div>
                            <small class="text-secondary"><code><?= htmlspecialchars($claim['employee_code'] ?? ''); ?></code></small>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($claim['benefit_name']); ?></span></td>
                        <td><?= htmlspecialchars($claim['claim_type']); ?></td>
                        <td class="fw-bold text-info">₱<?= number_format($claim['amount'], 2); ?></td>
                        <td><code><?= htmlspecialchars($claim['receipt_number'] ?? 'N/A'); ?></code></td>
                        <td><?= $claim['submitted_at']; ?></td>
                        <td>
                            <?php if ($claim['status'] === 'Approved'): ?>
                                <span class="badge badge-soft-success"><i class="fa-solid fa-check me-1"></i> Approved</span>
                            <?php elseif ($claim['status'] === 'Paid'): ?>
                                <span class="badge badge-soft-info"><i class="fa-solid fa-money-check-dollar me-1"></i> Paid</span>
                            <?php elseif ($claim['status'] === 'Rejected'): ?>
                                <span class="badge badge-soft-danger"><i class="fa-solid fa-xmark me-1"></i> Rejected</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning"><i class="fa-solid fa-clock me-1"></i> Pending Approval</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isHRAdmin): ?>
                        <td class="text-center table-action-cell">
                            <div class="table-action-group">
                                <?php if ($claim['status'] !== 'Approved'): ?>
                                    <a href="index.php?page=admin_update_claim_status&claim_id=<?= $claim['id']; ?>&status=Approved" class="btn btn-sm btn-success btn-update-claim fw-bold" data-id="<?= $claim['id']; ?>" data-status="Approved" title="Approve Claim">
                                        <i class="fa-solid fa-check me-1"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <?php if ($claim['status'] !== 'Rejected'): ?>
                                    <a href="index.php?page=admin_update_claim_status&claim_id=<?= $claim['id']; ?>&status=Rejected" class="btn btn-sm btn-outline-danger btn-update-claim fw-bold" data-id="<?= $claim['id']; ?>" data-status="Rejected" title="Disapprove Claim">
                                        <i class="fa-solid fa-xmark me-1"></i> Disapprove
                                    </a>
                                <?php endif; ?>
                                <a href="index.php?page=benefits_claim_delete&claim_id=<?= $claim['id']; ?>" class="btn btn-sm btn-outline-secondary btn-delete-claim" data-id="<?= $claim['id']; ?>" title="Delete Claim">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="claimModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-receipt text-primary me-2"></i> Apply Reimbursement Claim</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="claimForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <?php if ($isHRAdmin && !empty($employees)): ?>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Employee / Candidate</label>
                        <select class="form-select" name="employee_id">
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Benefit Plan</label>
                        <select class="form-select" name="benefit_id" required>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Claim Type / Description</label>
                        <input type="text" class="form-control" name="claim_type" placeholder="e.g. Medical Exam / Transportation Reimbursement" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Claim Amount (PHP)</label>
                        <input type="number" class="form-control" name="amount" placeholder="e.g. 2500" step="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Official Receipt (O.R.) Number</label>
                        <input type="text" class="form-control" name="receipt_number" placeholder="e.g. OR-991823">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Submit Reimbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isHRAdmin): ?>
<div class="modal fade" id="adminClaimModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i> File Reimbursement for Employee</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="adminClaimForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Employee / Candidate</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">-- Choose Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Benefit Plan</label>
                        <select class="form-select" name="benefit_id" required>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Claim Description / Particulars</label>
                        <input type="text" class="form-control" name="claim_type" placeholder="e.g. Travel & Transport Medical Exam Reimbursement" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Reimbursement Amount (PHP)</label>
                        <input type="number" class="form-control" name="amount" placeholder="e.g. 3500" step="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Official Receipt (O.R.) / Ref Number</label>
                        <input type="text" class="form-control" name="receipt_number" placeholder="e.g. OR-881234">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Initial Status</label>
                        <select class="form-select" name="status">
                            <option value="Approved" selected>Approved</option>
                            <option value="Pending">Pending Approval</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Record Reimbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="adminAllowanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-gift text-success me-2"></i> Grant Employee Allowance</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="adminAllowanceForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Employee / Candidate</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">-- Choose Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Allowance Category / Plan</label>
                        <select class="form-select" name="benefit_id">
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id']; ?>" <?= strtolower($p['type']) === 'allowance' ? 'selected' : ''; ?>><?= htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Allowance Title</label>
                        <input type="text" class="form-control" name="allowance_title" value="Pre-Deployment & Transport Allowance" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Allowance Amount (PHP)</label>
                        <input type="number" class="form-control" name="amount" value="5000" step="500" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Reference / Voucher No.</label>
                        <input type="text" class="form-control" name="reference_no" placeholder="ALLOW-<?= rand(10000, 99999); ?>">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Disburse Allowance</button>
                </div>
            </form>
        </div>
</div>
<?php endif; ?>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    $('.btn-update-claim i, .btn-delete-claim i').css('pointer-events', 'none');

    $(document).on('submit', '#claimForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=benefits_submit_claim',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $(document).on('submit', '#adminClaimForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=admin_request_claim',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $(document).on('submit', '#adminAllowanceForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=admin_grant_allowance',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $(document).on('click', '.btn-update-claim', function(e) {
        e.preventDefault();
        var $btn = $(e.target).closest('.btn-update-claim');
        var claimId = $btn.attr('data-id') || $btn.data('id');
        var status = $btn.attr('data-status') || $btn.data('status');
        var csrf = '<?= csrf_token(); ?>';
        
        $.ajax({
            url: 'index.php?page=admin_update_claim_status',
            type: 'POST',
            data: { claim_id: claimId, status: status, csrf_token: csrf },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    $(document).on('click', '.btn-delete-claim', function(e) {
        e.preventDefault();
        var $btn = $(e.target).closest('.btn-delete-claim');
        var claimId = $btn.attr('data-id') || $btn.data('id');
        var csrf = '<?= csrf_token(); ?>';

        Swal.fire({
            title: 'Delete Claim Entry?',
            text: 'Are you sure you want to delete this benefit reimbursement record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=benefits_claim_delete',
                    type: 'POST',
                    data: { claim_id: claimId, csrf_token: csrf },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            showToast('success', res.message);
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Server error occurred.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });
});
</script>

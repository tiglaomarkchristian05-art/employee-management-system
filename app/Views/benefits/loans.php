<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-piggy-bank text-info me-2"></i> Employee Loans & Amortization Center</h4>
            <p class="text-secondary mb-0">Company loans, Pag-IBIG HDMF, SSS salary loans, placement financing, and payroll deduction schedules</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($isHRAdmin): ?>
                <button class="btn btn-info btn-sm fw-bold shadow" data-bs-toggle="modal" data-bs-target="#adminLoanModal">
                    <i class="fa-solid fa-plus me-1"></i> File Employee Loan (HR/Admin)
                </button>
            <?php endif; ?>
            <button class="btn btn-outline-info btn-sm fw-bold shadow" data-bs-toggle="modal" data-bs-target="#loanModal">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Apply New Loan
            </button>
        </div>
    </div>

    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold text-light mb-3"><i class="fa-solid fa-list text-primary me-2"></i> Loan Amortization Schedule & Balances</h5>
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Loan Type</th>
                        <th>Principal</th>
                        <th>Term</th>
                        <th>Monthly Deduction</th>
                        <th>Total Payable</th>
                        <th>Remaining Balance</th>
                        <th>Status</th>
                        <?php if ($isHRAdmin): ?>
                        <th class="text-center">HR Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td class="fw-bold text-light">
                            <div><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?></div>
                            <small class="text-secondary"><code><?= htmlspecialchars($loan['employee_code'] ?? ''); ?></code></small>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($loan['loan_type']); ?></span></td>
                        <td>₱<?= number_format($loan['principal_amount'], 2); ?></td>
                        <td><?= $loan['term_months']; ?> mos</td>
                        <td class="fw-bold text-warning">₱<?= number_format($loan['monthly_deduction'], 2); ?></td>
                        <td>₱<?= number_format($loan['total_payable'], 2); ?></td>
                        <td class="fw-bold text-danger">₱<?= number_format($loan['balance_remaining'], 2); ?></td>
                        <td>
                            <?php if ($loan['status'] === 'Active'): ?>
                                <span class="badge badge-soft-success">Active Amortization</span>
                            <?php elseif ($loan['status'] === 'Fully Paid'): ?>
                                <span class="badge badge-soft-info">Fully Paid</span>
                            <?php elseif ($loan['status'] === 'Rejected'): ?>
                                <span class="badge badge-soft-danger">Rejected</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning"><?= htmlspecialchars($loan['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isHRAdmin): ?>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                <?php if ($loan['status'] !== 'Active'): ?>
                                    <a href="index.php?page=admin_update_loan_status&loan_id=<?= $loan['id']; ?>&status=Active" class="btn btn-sm btn-success btn-update-loan fw-bold" data-id="<?= $loan['id']; ?>" data-status="Active" title="Approve & Activate Loan">
                                        <i class="fa-solid fa-check me-1"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <?php if ($loan['status'] !== 'Rejected'): ?>
                                    <a href="index.php?page=admin_update_loan_status&loan_id=<?= $loan['id']; ?>&status=Rejected" class="btn btn-sm btn-outline-danger btn-update-loan fw-bold" data-id="<?= $loan['id']; ?>" data-status="Rejected" title="Disapprove Loan">
                                        <i class="fa-solid fa-xmark me-1"></i> Disapprove
                                    </a>
                                <?php endif; ?>
                                <?php if ($loan['status'] === 'Active'): ?>
                                    <button type="button" class="btn btn-sm btn-info text-white btn-pay-loan fw-bold" data-id="<?= $loan['id']; ?>" data-balance="<?= $loan['balance_remaining']; ?>" data-deduction="<?= $loan['monthly_deduction']; ?>" title="Record Payment">
                                        <i class="fa-solid fa-coins me-1"></i> Pay
                                    </button>
                                <?php endif; ?>
                                <a href="index.php?page=benefits_loan_delete&loan_id=<?= $loan['id']; ?>" class="btn btn-sm btn-outline-secondary btn-delete-loan" data-id="<?= $loan['id']; ?>" title="Delete Loan">
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

<div class="modal fade" id="loanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-piggy-bank text-info me-2"></i> Apply for Salary / Emergency Loan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="loanForm">
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
                        <label class="form-label text-secondary fw-bold">Loan Type</label>
                        <select class="form-select" name="loan_type" required>
                            <option value="Emergency">Company Emergency Loan</option>
                            <option value="Salary">Company Salary Loan</option>
                            <option value="Pag-IBIG">Pag-IBIG Multi-Purpose Loan</option>
                            <option value="SSS">SSS Salary Loan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Principal Amount (PHP)</label>
                        <input type="number" class="form-control" name="principal_amount" placeholder="e.g. 30000" step="1000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Payment Term (Months)</label>
                        <select class="form-select" name="term_months" required>
                            <option value="6">6 Months</option>
                            <option value="12" selected>12 Months</option>
                            <option value="24">24 Months</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Interest Rate (%)</label>
                        <input type="number" class="form-control" name="interest_rate" value="2.0" step="0.1">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info fw-bold">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isHRAdmin): ?>
<div class="modal fade" id="adminLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-piggy-bank text-info me-2"></i> File Employee Loan Record (HR/Admin)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="adminLoanForm">
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
                        <label class="form-label text-secondary fw-bold">Loan Category</label>
                        <select class="form-select" name="loan_type" required>
                            <option value="Emergency">Company Emergency Loan</option>
                            <option value="Salary">Company Salary Loan</option>
                            <option value="Pag-IBIG">Pag-IBIG Multi-Purpose Loan</option>
                            <option value="SSS">SSS Salary Loan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Principal Loan Amount (PHP)</label>
                        <input type="number" class="form-control" name="principal_amount" placeholder="e.g. 50000" step="1000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Payment Term (Months)</label>
                        <select class="form-select" name="term_months" required>
                            <option value="6">6 Months</option>
                            <option value="12" selected>12 Months</option>
                            <option value="24">24 Months</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Interest Rate (%)</label>
                        <input type="number" class="form-control" name="interest_rate" value="2.0" step="0.1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Initial Loan Status</label>
                        <select class="form-select" name="status">
                            <option value="Pending" selected>Pending Approval</option>
                            <option value="Active">Active (Immediate Amortization)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info fw-bold">Record Loan Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card text-light">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-coins text-success me-2"></i> Record Loan Amortization Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <?= csrf_input(); ?>
                <input type="hidden" name="loan_id" id="pay_loan_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Payment Amount (PHP)</label>
                        <input type="number" class="form-control" name="amount" id="pay_amount" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="Payroll Deduction" selected>Payroll Deduction</option>
                            <option value="Cash Over-The-Counter">Cash Over-The-Counter</option>
                            <option value="Bank Transfer">Bank Transfer / Remittance</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Reference / OR Number</label>
                        <input type="text" class="form-control" name="reference_no" placeholder="e.g. PAY-99182">
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold">Record Payment</button>
                </div>
            </form>
        </div>
</div>
<?php endif; ?>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    // Prevent icon click interference
    $('.btn-update-loan i, .btn-delete-loan i, .btn-pay-loan i').css('pointer-events', 'none');

    $(document).on('submit', '#loanForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=benefits_request_loan',
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

    $(document).on('submit', '#adminLoanForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=admin_request_loan',
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

    $(document).on('click', '.btn-update-loan', function(e) {
        e.preventDefault();
        var $btn = $(e.target).closest('.btn-update-loan');
        var loanId = $btn.attr('data-id') || $btn.data('id');
        var status = $btn.attr('data-status') || $btn.data('status');
        var csrf = '<?= csrf_token(); ?>';
        
        if (!loanId) {
            Swal.fire('Error', 'Invalid loan reference.', 'error');
            return;
        }

        $.ajax({
            url: 'index.php?page=admin_update_loan_status',
            type: 'POST',
            data: { loan_id: loanId, status: status, csrf_token: csrf },
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

    $(document).on('click', '.btn-pay-loan', function(e) {
        e.preventDefault();
        var $btn = $(e.target).closest('.btn-pay-loan');
        var loanId = $btn.attr('data-id') || $btn.data('id');
        var deduction = $btn.attr('data-deduction') || $btn.data('deduction');
        $('#pay_loan_id').val(loanId);
        $('#pay_amount').val(deduction);
        var modalEl = document.getElementById('paymentModal');
        if (modalEl) {
            var modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });

    $(document).on('submit', '#paymentForm', function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            url: 'index.php?page=benefits_loan_payment',
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

    $(document).on('click', '.btn-delete-loan', function(e) {
        e.preventDefault();
        var $btn = $(e.target).closest('.btn-delete-loan');
        var loanId = $btn.attr('data-id') || $btn.data('id');
        var csrf = '<?= csrf_token(); ?>';

        Swal.fire({
            title: 'Delete Loan Record?',
            text: 'Are you sure you want to delete this loan amortization entry?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=benefits_loan_delete',
                    type: 'POST',
                    data: { loan_id: loanId, csrf_token: csrf },
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

<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-users me-2" style="color: var(--primary);"></i> OFW Candidate & Staff Directory</h4>
            <p class="text-secondary mb-0">Master candidate profiles, basic compensation, statutory IDs, and department assignments</p>
        </div>
        <?php if ($isHRAdmin): ?>
        <button class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newEmpModal">
            <i class="fa-solid fa-user-plus me-1"></i> Add New Candidate
        </button>
        <?php endif; ?>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Candidate / Employee Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Hire Date</th>
                        <th>Basic Salary</th>
                        <th>Status</th>
                        <?php if ($isHRAdmin): ?>
                        <th class="text-center">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($emp['employee_code']); ?></code></td>
                        <td class="fw-bold" style="color: var(--text);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle text-white fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px; background-color: var(--primary);">
                                    <?= strtoupper(substr($emp['first_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="color: var(--text); font-weight:700;"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></div>
                                    <div class="small text-secondary"><?= htmlspecialchars($emp['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($emp['department_name']); ?></span></td>
                        <td style="color: var(--text);"><?= htmlspecialchars($emp['position_title']); ?></td>
                        <td><?= $emp['hire_date']; ?></td>
                        <td class="fw-bold" style="color: var(--primary);">₱<?= number_format($emp['basic_salary'], 2); ?></td>
                        <td>
                            <?php if ($emp['status'] === 'Active'): ?>
                                <span class="badge badge-soft-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-soft-danger"><?= $emp['status']; ?></span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isHRAdmin): ?>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button class="btn btn-sm btn-outline-primary btn-edit-emp" data-id="<?= $emp['id']; ?>" title="Edit Candidate">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <a href="index.php?page=documents" class="btn btn-sm btn-outline-warning" title="Upload Document">
                                    <i class="fa-solid fa-file-arrow-up"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger btn-delete-emp" data-id="<?= $emp['id']; ?>" data-name="<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>" title="Delete Candidate">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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

<?php if ($isHRAdmin): ?>
<div class="modal fade" id="newEmpModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-user-plus text-primary me-2"></i> Register New Candidate / Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newEmpForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Email Address</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Phone Number</label>
                            <input type="text" class="form-control" name="phone" placeholder="09171234567">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" value="1995-01-01">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" value="<?= date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Department</label>
                            <select class="form-select" name="department_id">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Position</label>
                            <select class="form-select" name="position_id">
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Employment Status</label>
                            <select class="form-select" name="status">
                                <option value="Active">Active</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Resigned">Resigned</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Monthly Basic Salary (PHP)</label>
                            <input type="number" class="form-control" name="basic_salary" value="35000" step="500" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">SSS No.</label>
                            <input type="text" class="form-control" name="sss_no" placeholder="34-1234567-8">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">PhilHealth No.</label>
                            <input type="text" class="form-control" name="philhealth_no" placeholder="12-345678901-2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Pag-IBIG No.</label>
                            <input type="text" class="form-control" name="pagibig_no" placeholder="1210-9876-5432">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">TIN No.</label>
                            <input type="text" class="form-control" name="tin_no" placeholder="234-567-890-000">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Candidate Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editEmpModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Edit Candidate / Employee Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editEmpForm">
                <?= csrf_input(); ?>
                <input type="hidden" name="id" id="edit_emp_id">
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Email Address</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Phone Number</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Gender</label>
                            <select class="form-select" name="gender" id="edit_gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" id="edit_dob">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" id="edit_hire_date">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Department</label>
                            <select class="form-select" name="department_id" id="edit_department_id">
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id']; ?>"><?= htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Position</label>
                            <select class="form-select" name="position_id" id="edit_position_id">
                                <?php foreach ($positions as $p): ?>
                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Employment Status</label>
                            <select class="form-select" name="status" id="edit_status">
                                <option value="Active">Active</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Resigned">Resigned</option>
                                <option value="Terminated">Terminated</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">Monthly Basic Salary (PHP)</label>
                            <input type="number" class="form-control" name="basic_salary" id="edit_basic_salary" step="500" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold">SSS No.</label>
                            <input type="text" class="form-control" name="sss_no" id="edit_sss_no">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">PhilHealth No.</label>
                            <input type="text" class="form-control" name="philhealth_no" id="edit_philhealth_no">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">Pag-IBIG No.</label>
                            <input type="text" class="form-control" name="pagibig_no" id="edit_pagibig_no">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold">TIN No.</label>
                            <input type="text" class="form-control" name="tin_no" id="edit_tin_no">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
</div>
<?php endif; ?>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<script>
$(document).ready(function() {
    $(document).on('submit', '#newEmpForm', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?page=employee_store',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-edit-emp', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'index.php?page=employee_get&id=' + id,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    var emp = res.employee;
                    $('#edit_emp_id').val(emp.id);
                    $('#edit_first_name').val(emp.first_name);
                    $('#edit_last_name').val(emp.last_name);
                    $('#edit_email').val(emp.email);
                    $('#edit_phone').val(emp.phone);
                    $('#edit_gender').val(emp.gender);
                    $('#edit_dob').val(emp.dob);
                    $('#edit_hire_date').val(emp.hire_date);
                    $('#edit_department_id').val(emp.department_id);
                    $('#edit_position_id').val(emp.position_id);
                    $('#edit_status').val(emp.status);
                    $('#edit_basic_salary').val(emp.basic_salary);
                    $('#edit_sss_no').val(emp.sss_no);
                    $('#edit_philhealth_no').val(emp.philhealth_no);
                    $('#edit_pagibig_no').val(emp.pagibig_no);
                    $('#edit_tin_no').val(emp.tin_no);

                    var modalEl = document.getElementById('editEmpModal');
                    if (modalEl) {
                        var modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    });

    $(document).on('submit', '#editEmpForm', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?page=employee_update',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    showToast('success', res.message);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-emp', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var csrf = $('input[name="csrf_token"]').val();
        Swal.fire({
            title: 'Delete Employee Record?',
            text: 'Are you sure you want to delete ' + name + '? This will also remove associated statutory records.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=employee_delete',
                    type: 'POST',
                    data: { id: id, csrf_token: csrf },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            showToast('success', res.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>

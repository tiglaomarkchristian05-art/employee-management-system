<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
$isHRAdmin = Auth::hasRole(['Super Admin', 'HR Manager']);
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-user-shield text-primary me-2"></i> User & Role-Based Access Control (RBAC)</h4>
            <p class="text-secondary mb-0">Manage user credentials, role permissions, and active login sessions</p>
        </div>
        <?php if ($isHRAdmin): ?>
        <button class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newUserModal">
            <i class="fa-solid fa-user-plus me-1"></i> Add System User
        </button>
        <?php endif; ?>
    </div>

    <div class="glass-card p-4 mb-4">
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Linked Employee</th>
                        <th>Assigned Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <?php if ($isHRAdmin): ?>
                        <th class="text-center">Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-light"><code><?= htmlspecialchars($u['username']); ?></code></td>
                        <td><?= htmlspecialchars($u['first_name'] ? $u['first_name'] . ' ' . $u['last_name'] : 'System Admin'); ?></td>
                        <td><span class="badge badge-soft-primary"><?= htmlspecialchars($u['role_name']); ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-soft-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-soft-danger">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $u['last_login'] ?? 'Never'; ?></td>
                        <?php if ($isHRAdmin): ?>
                        <td class="text-center">
                            <?php $canManageRow = Auth::hasRole(['Super Admin']) || ($u['role_name'] ?? '') === 'Employee'; ?>
                            <div class="d-flex justify-content-center gap-1">
                                <?php if ($canManageRow): ?>
                                <button class="btn btn-sm btn-outline-primary btn-edit-user" 
                                    data-id="<?= $u['id']; ?>" 
                                    data-username="<?= htmlspecialchars($u['username']); ?>"
                                    data-role="<?= $u['role_id']; ?>" 
                                    data-employee="<?= $u['employee_id'] ?? ''; ?>" 
                                    title="Edit User Role & Mapping">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm <?= $u['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?> btn-toggle-user" data-id="<?= $u['id']; ?>" title="Toggle User Status">
                                    <i class="fa-solid <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info btn-reset-pass" data-id="<?= $u['id']; ?>" data-name="<?= htmlspecialchars($u['username']); ?>" title="Reset Password">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <?php if ($u['username'] !== 'admin' && (int)$u['id'] !== (int)Auth::user()['id']): ?>
                                <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="<?= $u['id']; ?>" data-name="<?= htmlspecialchars($u['username']); ?>" title="Delete User Account">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php endif; ?>
                                <?php else: ?><span class="badge badge-soft-primary">Protected account</span><?php endif; ?>
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
<div class="modal fade" id="newUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-user-plus text-primary me-2"></i> Create New User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newUserForm">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Username / Email</label>
                        <input type="text" class="form-control" name="username" placeholder="e.g. jdoe@mosesgroup.ph" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Password</label>
                        <input type="password" class="form-control" name="password" minlength="8" autocomplete="new-password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Assigned Role</label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id']; ?>" <?= ($r['name'] === 'Employee' || $r['id'] == 4) ? 'selected' : ''; ?>><?= htmlspecialchars($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Link to Employee Profile (Optional)</label>
                        <select class="form-select" name="employee_id">
                            <option value="">-- No Linked Employee (Admin Only) --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create User Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-user-pen text-primary me-2"></i> Edit User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm">
                <?= csrf_input(); ?>
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Username</label>
                        <input type="text" class="form-control" id="edit_username" readonly style="background-color:#F1F5F9;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Assigned Role</label>
                        <select class="form-select" name="role_id" id="edit_role_id" required>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id']; ?>"><?= htmlspecialchars($r['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Link to Employee Profile (Optional)</label>
                        <select class="form-select" name="employee_id" id="edit_employee_id">
                            <option value="">-- No Linked Employee (Admin Only) --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-key text-info me-2"></i> Reset User Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPassForm">
                <?= csrf_input(); ?>
                <input type="hidden" name="user_id" id="reset_pass_user_id">
                <div class="modal-body">
                    <p class="text-secondary">Resetting password for user account: <strong id="reset_pass_username" class="text-primary"></strong></p>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">New Password</label>
                        <input type="password" class="form-control" name="new_password" minlength="8" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function notifySuccess(msg) {
        if (typeof showToast === 'function') {
            showToast('success', msg);
        } else if (window.Swal) {
            Swal.fire({ icon: 'success', title: msg, timer: 1500, showConfirmButton: false });
        } else {
            alert(msg);
        }
    }

    function notifyError(msg) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: 'Error', text: msg });
        } else {
            alert(msg);
        }
    }

    function openModal(modalId) {
        var modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        if (window.bootstrap && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (window.jQuery && $.fn.modal) {
            $(modalEl).modal('show');
        }
    }

    function closeModal(modalId) {
        var modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        if (window.bootstrap && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) modalInstance.hide();
        } else if (window.jQuery && $.fn.modal) {
            $(modalEl).modal('hide');
        }
    }

    function getCsrfToken() {
        return $('input[name="csrf_token"]').first().val() || '<?= generate_csrf_token(); ?>';
    }

    // 1. Create User Account
    $(document).on('submit', '#newUserForm', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?page=admin_create_user',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    notifySuccess(res.message);
                    closeModal('newUserModal');
                    setTimeout(() => location.reload(), 800);
                } else {
                    notifyError(res.message);
                }
            },
            error: function() {
                notifyError('Failed to process user creation request.');
            }
        });
    });

    // 2. Open Edit User Modal
    $(document).on('click', '.btn-edit-user', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var username = $(this).data('username');
        var roleId = $(this).data('role');
        var empId = $(this).data('employee');

        $('#edit_user_id').val(userId);
        $('#edit_username').val(username);
        $('#edit_role_id').val(roleId);
        $('#edit_employee_id').val(empId);

        openModal('editUserModal');
    });

    // 3. Save Edit User Form
    $(document).on('submit', '#editUserForm', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?page=admin_update_user',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    notifySuccess(res.message);
                    closeModal('editUserModal');
                    setTimeout(() => location.reload(), 800);
                } else {
                    notifyError(res.message);
                }
            },
            error: function() {
                notifyError('Failed to update user account.');
            }
        });
    });

    // 4. Toggle Active/Disabled Status
    $(document).on('click', '.btn-toggle-user', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var csrf = getCsrfToken();
        $.ajax({
            url: 'index.php?page=admin_toggle_user_status',
            type: 'POST',
            data: { user_id: userId, csrf_token: csrf },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    notifySuccess(res.message);
                    setTimeout(() => location.reload(), 800);
                } else {
                    notifyError(res.message);
                }
            },
            error: function() {
                notifyError('Failed to toggle user status.');
            }
        });
    });

    // 5. Open Reset Password Modal
    $(document).on('click', '.btn-reset-pass', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var username = $(this).data('name');
        $('#reset_pass_user_id').val(userId);
        $('#reset_pass_username').text(username);

        openModal('resetPassModal');
    });

    // 6. Submit Reset Password Form
    $(document).on('submit', '#resetPassForm', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'index.php?page=admin_reset_password',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    notifySuccess(res.message);
                    closeModal('resetPassModal');
                } else {
                    notifyError(res.message);
                }
            },
            error: function() {
                notifyError('Failed to reset user password.');
            }
        });
    });

    // 7. Delete User Action
    $(document).on('click', '.btn-delete-user', function(e) {
        e.preventDefault();
        var userId = $(this).data('id');
        var username = $(this).data('name');
        var csrf = getCsrfToken();

        Swal.fire({
            title: 'Delete User Account?',
            text: 'Are you sure you want to delete user "' + username + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=admin_delete_user',
                    type: 'POST',
                    data: { user_id: userId, csrf_token: csrf },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            notifySuccess(res.message);
                            setTimeout(() => location.reload(), 800);
                        } else {
                            notifyError(res.message);
                        }
                    },
                    error: function() {
                        notifyError('Failed to delete user account.');
                    }
                });
            }
        });
    });
});
</script>

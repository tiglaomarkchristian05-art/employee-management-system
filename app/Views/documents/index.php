<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="d-flex align-items-center justify-content-between my-3">
        <div>
            <h4 class="fw-bold mb-1" style="color: var(--text);"><i class="fa-solid fa-folder-tree me-2" style="color: var(--warning);"></i> Document & Contract Management</h4>
            <p class="text-secondary mb-0">Secure file repository, QR verification stamps, contract renewal tracking, and approval workflows</p>
        </div>
        <button class="btn btn-warning text-white btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload New Document
        </button>
    </div>

    <?php if (!empty($expiring_contracts)): ?>
    <div class="alert alert-warning glass-card d-flex align-items-center justify-content-between mb-4 text-warning border-warning" role="alert">
        <div>
            <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
            <strong>Contract Expiry Alert:</strong> You have <?= count($expiring_contracts); ?> employment contract(s) due for renewal within 60 days.
        </div>
        <a href="index.php?page=documents_contracts" class="btn btn-sm btn-warning text-white fw-bold">Review Contracts</a>
    </div>
    <?php endif; ?>

    <div class="glass-card p-4 mb-4">
        <h5 class="fw-bold mb-3" style="color: var(--text);"><i class="fa-solid fa-file-pdf text-danger me-2"></i> Employee Document Repository</h5>
        <div class="table-responsive">
            <table class="table align-middle datatable-init">
                <thead>
                    <tr>
                        <th>Document Title</th>
                        <th>Category</th>
                        <th>Document No.</th>
                        <th>QR Verification Code</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td class="fw-bold" style="color: var(--text);">
                            <i class="fa-solid fa-file-pdf text-danger me-2"></i> <?= htmlspecialchars($doc['title']); ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($doc['category_name']); ?></span></td>
                        <td><code><?= htmlspecialchars($doc['document_number']); ?></code></td>
                        <td><small class="badge badge-soft-info"><i class="fa-solid fa-qrcode me-1"></i> <?= htmlspecialchars($doc['qr_code'] ?? 'QR-VERIFIED'); ?></small></td>
                        <td><?= $doc['expiry_date'] ?? 'N/A'; ?></td>
                        <td>
                            <?php if ($doc['status'] === 'Verified'): ?>
                                <span class="badge badge-soft-success"><i class="fa-solid fa-check-circle me-1"></i> Verified</span>
                            <?php else: ?>
                                <span class="badge badge-soft-warning">Pending Audit</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-info" onclick="viewDocModal(<?= (int)$doc['id']; ?>, <?= json_encode($doc['title']); ?>, <?= json_encode($doc['qr_code'] ?? 'QR-VERIFIED'); ?>)">
                                    <i class="fa-solid fa-eye me-1"></i> View
                                </button>
                                <?php if (Auth::hasRole(['Super Admin', 'HR Manager'])): ?>
                                <button class="btn btn-sm btn-outline-danger btn-delete-doc" data-id="<?= $doc['id']; ?>" title="Delete Document">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--text);"><i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Drag & Drop Upload Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadDocForm" enctype="multipart/form-data">
                <?= csrf_input(); ?>
                <div class="modal-body">
                    <?php if (Auth::hasRole(['Super Admin', 'HR Manager'])): ?>
                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Select Employee / Candidate</label>
                        <select class="form-select" name="employee_id" required>
                            <option value="">-- Choose Employee --</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_code'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Document Category</label>
                        <select class="form-select" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Document Title</label>
                        <input type="text" class="form-control" name="title" placeholder="e.g. NBI Clearance 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Document / ID Number</label>
                        <input type="text" class="form-control" name="document_number" placeholder="e.g. NBI-991823">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">Expiration Date (Optional)</label>
                        <input type="date" class="form-control" name="expiry_date">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary fw-bold">File Upload (PDF, JPG, PNG)</label>
                        <input type="file" name="document_file" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-white fw-bold"><i class="fa-solid fa-upload me-1"></i> Upload & Apply QR Verification</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $(document).on('submit', '#uploadDocForm', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: 'index.php?page=documents_upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
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

    $(document).on('click', '.btn-delete-doc', function() {
        var id = $(this).data('id');
        var csrf = $('input[name="csrf_token"]').val();
        Swal.fire({
            title: 'Delete Document?',
            text: 'Are you sure you want to permanently remove this document record?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'index.php?page=documents_delete',
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

function viewDocModal(id, title, qr) {
    Swal.fire({
        title: title,
        html: `
            <div class="text-center p-3">
                <div class="mb-3"><i class="fa-solid fa-file-pdf fs-1 text-danger"></i></div>
                <p class="text-secondary">Document cryptographically verified with security stamp:</p>
                <div class="p-2 bg-light rounded border font-monospace text-primary mb-3">${qr}</div>
                <a class="btn btn-sm btn-primary" href="index.php?page=documents_download&amp;id=${encodeURIComponent(id)}"><i class="fa-solid fa-download me-1"></i> Download File</a>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true
    });
}
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

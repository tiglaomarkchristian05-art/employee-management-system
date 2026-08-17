<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-database text-info me-2"></i> Database Backup & Disaster Recovery Utility</h4>
        <p class="text-secondary">Export single-click MySQL database dumps or restore default database schema & seed scripts</p>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="glass-card p-4 text-center">
                <div class="mb-3 text-info"><i class="fa-solid fa-download fs-1"></i></div>
                <h5 class="fw-bold text-light mb-2">Download Full SQL Backup</h5>
                <p class="text-secondary small mb-4">Generates a complete `.sql` script containing all normalized database tables, foreign key definitions, and employee records.</p>
                <a href="index.php?page=admin_backup&download=sql" class="btn btn-info btn-lg w-100 fw-bold shadow"><i class="fa-solid fa-file-export me-2"></i> Export Database SQL Dump</a>
            </div>
        </div>

        <div class="col-md-6">
            <div class="glass-card p-4 text-center">
                <div class="mb-3 text-warning"><i class="fa-solid fa-rotate-left fs-1"></i></div>
                <h5 class="fw-bold text-light mb-2">Restore Factory Default Database</h5>
                <p class="text-secondary small mb-4">Re-initializes `schema.sql` and `seed.sql` to reset all demo datasets back to factory enterprise defaults.</p>
                <button class="btn btn-warning btn-lg w-100 fw-bold shadow" id="btnRestoreDb"><i class="fa-solid fa-arrows-rotate me-2"></i> Restore Enterprise Data</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#btnRestoreDb').on('click', function() {
    confirmAction('Reset & Restore Database', 'This will re-initialize default schema and seed data!', 'Restore Now', function() {
        $.post('index.php?page=admin_restore', {
            csrf_token: '<?= generate_csrf_token(); ?>'
        }, function(res) {
            if (res.status === 'success') {
                Swal.fire('Restored', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json');
    });
});
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

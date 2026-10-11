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

    <div class="glass-card p-4 mt-4">
        <h5 class="fw-bold text-light">Core 3 Presentation Data</h5>
        <p class="text-secondary small">Creates clearly labeled fictional employees, training evidence, comparison records, and demo user accounts. Existing records and passwords are preserved. Newly created demo accounts start disabled.</p>
        <p class="text-warning small">First download and securely store a fresh database backup above. Then enter your current Super Admin password and the confirmation phrase. This action changes the live database.</p>
        <form id="core3PresentationForm" autocomplete="off">
            <?= csrf_input(); ?>
            <div class="row g-2">
                <div class="col-md-5"><input type="password" name="current_password" class="form-control" placeholder="Current Super Admin password" required autocomplete="current-password"></div>
                <div class="col-md-4"><input type="text" name="confirmation" class="form-control" placeholder="Type PREPARE CORE3 DEMO" required></div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Prepare Demo Records</button></div>
            </div>
        </form>
        <div id="core3PresentationResult" class="mt-3" hidden>
            <p class="text-warning mb-2">Save these new passwords now. They are displayed only once and cannot be recovered here. Activate the needed accounts from User Accounts after saving them.</p>
            <pre class="p-3 rounded bg-dark text-light" id="core3PresentationCredentials"></pre>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#core3PresentationForm').on('submit', function(event) {
    event.preventDefault();
    const form = this;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    $.ajax({
        url: 'index.php?page=admin_prepare_presentation_data',
        method: 'POST',
        data: $(form).serialize(),
        dataType: 'json'
    }).done(function(res) {
        if (res.status !== 'success') {
            Swal.fire('Could not prepare data', res.message || 'Unknown error', 'error');
            return;
        }
        const credentials = res.data && res.data.accounts ? res.data.accounts : {};
        const lines = Object.entries(credentials).map(([username, password]) => username + ' : ' + password);
        document.getElementById('core3PresentationCredentials').textContent = lines.length ? lines.join('\n') : 'No new accounts were created. Existing demo passwords were not changed.';
        document.getElementById('core3PresentationResult').hidden = false;
        form.reset();
        Swal.fire('Presentation records prepared', res.message, 'success');
    }).fail(function(xhr) {
        const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Setup failed. Check the application logs; no password was changed.';
        Swal.fire('Could not prepare data', message, 'error');
    }).always(function() { button.disabled = false; });
});
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

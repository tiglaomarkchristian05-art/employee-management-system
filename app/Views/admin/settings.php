<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-gears text-info me-2"></i> System Configuration & Settings</h4>
        <p class="text-secondary">Enterprise parameters, currency symbols, and corporate profile settings</p>
    </div>

    <div class="glass-card p-4 max-w-700 mx-auto mb-4">
        <form id="settingsForm">
            <?= csrf_input(); ?>
            <?php foreach ($settings as $setting): ?>
            <div class="mb-3">
                <label class="form-label text-secondary fw-bold"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $setting['setting_key']))); ?></label>
                <input type="text" class="form-control" name="settings[<?= $setting['setting_key']; ?>]" value="<?= htmlspecialchars($setting['setting_value']); ?>">
                <small class="text-secondary"><?= htmlspecialchars($setting['description'] ?? ''); ?></small>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-info fw-bold w-100 shadow mt-3"><i class="fa-solid fa-floppy-disk me-2"></i> Save Configurations</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#settingsForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'index.php?page=admin_save_settings',
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
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

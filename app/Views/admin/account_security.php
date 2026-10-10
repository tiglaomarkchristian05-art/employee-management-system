<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>
<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>
    <div class="my-3">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-shield-halved text-primary me-2"></i>Account Security</h4>
        <p class="text-secondary">Control how your own account signs in.</p>
    </div>
    <div class="glass-card p-4 max-w-700 mx-auto mb-4" id="mfaSettingsCard">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div><h5 class="fw-bold mb-1">Two-Factor Authentication (2FA)</h5><p class="text-secondary small mb-0">Choose email verification or password-only sign-in.</p></div>
            <span class="badge <?= $mfa_mode === 'off' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'; ?> px-3 py-2"><?= $mfa_mode === 'off' ? '2FA Disabled (Off)' : 'Email Verification On'; ?></span>
        </div>
        <form id="mfaModeForm">
            <?= csrf_input(); ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="mfa-choice d-block h-100 <?= $mfa_mode === 'email' ? 'selected' : ''; ?>"><input type="radio" name="mfa_mode" value="email" <?= $mfa_mode === 'email' ? 'checked' : ''; ?> required><span class="mfa-choice-icon"><i class="fa-regular fa-envelope"></i></span><strong>Email Verification</strong><small>Receive a one-time code at <span class="fw-semibold"><?= htmlspecialchars($mfa_email ?: 'your configured MFA email'); ?></span> when you sign in.</small></label></div>
                <div class="col-md-6"><label class="mfa-choice mfa-choice-off d-block h-100 <?= $mfa_mode === 'off' ? 'selected' : ''; ?>"><input type="radio" name="mfa_mode" value="off" <?= $mfa_mode === 'off' ? 'checked' : ''; ?>><span class="mfa-choice-icon"><i class="fa-solid fa-power-off"></i></span><strong>Disable 2FA (Off)</strong><small>Sign in with your password only. Other users keep their own verification settings.</small></label></div>
            </div>
            <div id="mfaPasswordField" class="mt-3 d-none"><label for="mfaCurrentPassword" class="form-label fw-semibold">Current password to turn off 2FA</label><input type="password" id="mfaCurrentPassword" name="current_password" class="form-control" autocomplete="current-password" placeholder="Enter your current password"><small class="text-secondary">Required when switching from Email Verification to Off.</small></div>
            <p class="small text-secondary mt-3 mb-2">The SMTP sender settings remain unchanged. This choice applies on your next sign-in; changing it ends your other active sessions.</p>
            <button type="submit" class="btn btn-primary" id="saveMfaModeButton"><i class="fa-solid fa-floppy-disk me-2"></i>Save 2FA Setting</button>
        </form>
    </div>
</div>
<style>
#mfaSettingsCard .mfa-choice{position:relative;border:1px solid #c8d0e6;border-radius:16px;padding:20px;cursor:pointer;background:#fff;transition:border-color .15s,background .15s,box-shadow .15s}
#mfaSettingsCard .mfa-choice:hover{border-color:#6254e9}
#mfaSettingsCard .mfa-choice.selected{border:2px solid #5548e8;background:#f7f6ff;box-shadow:0 0 0 2px #5548e811}
#mfaSettingsCard .mfa-choice-off.selected{border-color:#e64d56;background:#fff5f5}
#mfaSettingsCard .mfa-choice input{position:absolute;right:18px;top:18px;width:18px;height:18px;accent-color:#5548e8}
#mfaSettingsCard .mfa-choice-icon{display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:#eef1fb;color:#3f56a8;margin-bottom:11px}
#mfaSettingsCard .mfa-choice-off .mfa-choice-icon{background:#ffe5e6;color:#e3414d}
#mfaSettingsCard .mfa-choice strong{display:block;color:#152342;font-size:14px;margin-bottom:5px}
#mfaSettingsCard .mfa-choice small{display:block;color:#596a8b;font-size:12px;line-height:1.45;padding-right:12px}
</style>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
const mfaChoices = $('#mfaModeForm input[name="mfa_mode"]');
const originalMfaMode = <?= json_encode($mfa_mode); ?>;
function updateMfaSelection() {
    $('#mfaModeForm .mfa-choice').removeClass('selected');
    mfaChoices.filter(':checked').closest('.mfa-choice').addClass('selected');
    const turningOff = mfaChoices.filter(':checked').val() === 'off' && originalMfaMode !== 'off';
    $('#mfaPasswordField').toggleClass('d-none', !turningOff);
    $('#mfaCurrentPassword').prop('required', turningOff).prop('disabled', !turningOff);
}
mfaChoices.on('change', updateMfaSelection);
updateMfaSelection();
$('#mfaModeForm').on('submit', function(e) {
    e.preventDefault();
    const button = $('#saveMfaModeButton').prop('disabled', true);
    $.ajax({url:'index.php?page=admin_save_mfa_mode',type:'POST',data:$(this).serialize(),dataType:'json'})
      .done(function(res) {
          if (res.status === 'success') { showToast('success', res.message); setTimeout(() => location.reload(), 700); }
          else Swal.fire('Error', res.message, 'error');
      })
      .fail(function(xhr) { Swal.fire('Error', xhr.responseJSON?.message || 'Unable to update 2FA setting.', 'error'); })
      .always(function() { button.prop('disabled', false); });
});
</script>
<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

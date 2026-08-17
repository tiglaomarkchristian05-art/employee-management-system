/**
 * Moses Group of Company - Recruitment Management System Login Script
 */

document.addEventListener('DOMContentLoaded', function () {
    const toggleEyeBtn = document.getElementById('toggleEye');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    const alertBox = document.getElementById('alertBox');
    const alertMsg = document.getElementById('alertMsg');
    const loginForm = document.getElementById('loginForm');
    const btnLogin = document.getElementById('btnLogin');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');

    // 1. Toggle Password Visibility
    if (toggleEyeBtn && passwordInput && eyeIcon) {
        toggleEyeBtn.addEventListener('click', function () {
            const isPass = passwordInput.type === 'password';
            passwordInput.type = isPass ? 'text' : 'password';
            eyeIcon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        });
    }

    // 2. Demo Credential Auto-Fill
    document.querySelectorAll('.demo-card-item').forEach(card => {
        const autoFill = function () {
            const username = card.getAttribute('data-user');
            const pass = card.getAttribute('data-pass');
            document.getElementById('username').value = username;
            document.getElementById('password').value = pass;

            // Soft Toast Notification
            if (window.Swal) {
                const role = card.querySelector('.demo-role-name').textContent;
                Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1600,
                    timerProgressBar: true
                }).fire({
                    icon: 'info',
                    title: `${role} credentials loaded`
                });
            }
        };

        card.addEventListener('click', autoFill);
        card.addEventListener('dblclick', autoFill);
    });

    // 3. AJAX Form Submission with Spinner Animation
    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (alertBox) alertBox.style.display = 'none';

            // Loading state
            if (btnLogin) btnLogin.disabled = true;
            if (btnText) btnText.textContent = 'Authenticating...';
            if (btnIcon) btnIcon.className = 'fa-solid fa-circle-notch fa-spin';

            $.ajax({
                url: 'index.php?page=login',
                type: 'POST',
                data: $(loginForm).serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        if (btnText) btnText.textContent = 'Redirecting...';
                        if (btnIcon) btnIcon.className = 'fa-solid fa-check';

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Welcome Back!',
                                text: res.message,
                                timer: 1000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = res.data.redirect;
                            });
                        } else {
                            window.location.href = res.data.redirect;
                        }
                    } else {
                        if (alertBox && alertMsg) {
                            alertBox.style.display = 'flex';
                            alertMsg.textContent = res.message;
                        }
                        resetBtnState();
                    }
                },
                error: function () {
                    if (alertBox && alertMsg) {
                        alertBox.style.display = 'flex';
                        alertMsg.textContent = 'Connection error. Please try again.';
                    }
                    resetBtnState();
                }
            });
        });
    }

    function resetBtnState() {
        if (btnLogin) btnLogin.disabled = false;
        if (btnText) btnText.textContent = 'Sign In';
        if (btnIcon) btnIcon.className = 'fa-solid fa-right-to-bracket';
    }
});

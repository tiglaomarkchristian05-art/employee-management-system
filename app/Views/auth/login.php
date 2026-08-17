<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Moses Group of Companies</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        background: linear-gradient(135deg, #ECE6FA 0%, #F4F0FD 50%, #E6DCFA 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        color: #1A1A2E;
    }

    .login-card-container {
        width: 100%;
        max-width: 980px;
        min-height: 580px;
        background: #FFFFFF;
        border-radius: 28px;
        box-shadow: 0 20px 60px rgba(100, 30, 180, 0.14);
        position: relative;
        overflow: hidden;
        display: flex;
    }

    .wave-canvas {
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }

    .login-grid-wrapper {
        position: relative;
        z-index: 2;
        width: 100%;
        display: flex;
    }

    .form-column {
        flex: 0 0 50%;
        width: 50%;
        padding: 56px 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .greeting-title {
        font-size: 34px;
        font-weight: 800;
        color: #1A1A2E;
        margin-bottom: 4px;
        letter-spacing: -0.5px;
    }

    .greeting-sub {
        font-size: 14px;
        color: #7A7A9E;
        margin-bottom: 28px;
        font-weight: 500;
    }

    .alert-box {
        background: #FEF2F2;
        border: 1px solid #FECACA;
        color: #DC2626;
        padding: 10px 16px;
        border-radius: 50px;
        font-size: 13px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 10px;
    }
    .alert-box.show { display: flex; }

    .form-field-group {
        margin-bottom: 18px;
    }

    .input-pill-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: #FFFFFF;
        border-radius: 50px;
        box-shadow: 0 8px 24px rgba(124, 58, 237, 0.12);
        padding: 4px;
        border: 1px solid rgba(124, 58, 237, 0.08);
        transition: all 0.25s ease;
    }

    .input-pill-wrapper:focus-within {
        box-shadow: 0 10px 30px rgba(124, 58, 237, 0.22);
        border-color: #8E24AA;
    }

    .input-pill-icon {
        width: 40px;
        height: 40px;
        border-radius: 50px;
        background: linear-gradient(135deg, #8E24AA 0%, #673AB7 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #FFFFFF;
        font-size: 16px;
        flex-shrink: 0;
        margin-right: 12px;
        box-shadow: 0 4px 12px rgba(142, 36, 170, 0.3);
    }

    .pill-control-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 14.5px;
        font-family: inherit;
        color: #1A1A2E;
        padding: 10px 0;
    }

    .pill-control-input::placeholder {
        color: #A3A3C2;
        font-size: 14px;
    }

    .toggle-eye-pill {
        background: none;
        border: none;
        color: #8E24AA;
        font-size: 16px;
        cursor: pointer;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        outline: none;
    }

    .form-meta-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 8px 8px 24px 8px;
        font-size: 13px;
        color: #7A7A9E;
    }

    .remember-label-custom {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-weight: 500;
    }

    .checkbox-purple-custom {
        width: 16px;
        height: 16px;
        accent-color: #8E24AA;
        cursor: pointer;
    }

    .forgot-link-custom {
        color: #7A7A9E;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }
    .forgot-link-custom:hover { color: #8E24AA; }

    .btn-signin-pill {
        width: 180px;
        height: 46px;
        border-radius: 50px;
        border: none;
        background: linear-gradient(90deg, #8E24AA 0%, #673AB7 100%);
        color: #FFFFFF;
        font-size: 13.5px;
        font-weight: 700;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 8px 25px rgba(142, 36, 170, 0.4);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-signin-pill:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(142, 36, 170, 0.5);
    }

    .bottom-account-text {
        margin-top: 24px;
        font-size: 13.5px;
        color: #7A7A9E;
    }
    .bottom-account-text a {
        color: #8E24AA;
        font-weight: 700;
        text-decoration: none;
    }

    .info-column {
        flex: 0 0 50%;
        width: 50%;
        padding: 56px 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .welcome-hero-title {
        font-size: 30px;
        font-weight: 800;
        color: #1A1A2E;
        margin-bottom: 16px;
        letter-spacing: -0.5px;
    }

    .welcome-hero-desc {
        font-size: 14.5px;
        color: #4A4A68;
        line-height: 1.7;
        font-weight: 400;
        margin-bottom: 24px;
    }

    .demo-pills-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8E24AA;
        margin-bottom: 8px;
    }

    .demo-cards-row {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .demo-card-item {
        background: #FFFFFF;
        border: 1px solid rgba(142, 36, 170, 0.2);
        padding: 6px 14px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.06);
    }

    .demo-card-item:hover {
        background: #8E24AA;
        color: #FFFFFF !important;
        transform: translateY(-2px);
    }
    .demo-card-item:hover .demo-role-name,
    .demo-card-item:hover .demo-user-val {
        color: #FFFFFF !important;
    }

    .demo-role-name {
        font-size: 11px;
        font-weight: 700;
        color: #673AB7;
    }

    .demo-user-val {
        font-size: 10px;
        color: #7A7A9E;
    }

    @media (max-width: 840px) {
        .login-card-container { flex-direction: column; min-height: auto; border-radius: 20px; }
        .form-column, .info-column { flex: 1 1 100%; width: 100%; padding: 36px 28px; }
        .info-column { background: linear-gradient(135deg, #8E24AA, #673AB7); color: white; }
        .welcome-hero-title { color: #FFFFFF; }
        .welcome-hero-desc { color: rgba(255, 255, 255, 0.9); }
    }
    </style>
</head>
<body>

    <div class="login-card-container">
        <div class="wave-canvas">
            <svg viewBox="0 0 980 580" preserveAspectRatio="none" style="width:100%; height:100%;">
                <defs>
                    <linearGradient id="purpleGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#8E24AA" />
                        <stop offset="50%" stop-color="#673AB7" />
                        <stop offset="100%" stop-color="#4A148C" />
                    </linearGradient>
                </defs>
                <path d="M 390,0 Q 460,90 560,50 Q 660,10 740,70 Q 820,130 980,60 L 980,0 Z" fill="url(#purpleGrad)" />
                <path d="M 470,0 Q 550,110 670,60 Q 770,10 860,80 Q 930,120 980,100 L 980,0 Z" fill="url(#purpleGrad)" opacity="0.9" />
                <path d="M 280,580 Q 360,500 480,540 Q 600,580 720,490 Q 840,410 980,480 L 980,580 Z" fill="url(#purpleGrad)" />
            </svg>
        </div>

        <div class="login-grid-wrapper">
            <div class="form-column">
                <h1 class="greeting-title">Hello!</h1>
                <p class="greeting-sub">Sign in to your account</p>

                <div class="alert-box" id="alertBox">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="alertMsg">Invalid credentials or inactive account.</span>
                </div>

                <form id="loginForm">
                    <?= csrf_input(); ?>

                    <div class="form-field-group">
                        <div class="input-pill-wrapper">
                            <div class="input-pill-icon">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <input type="text" class="pill-control-input" id="username" name="username" placeholder="E-mail" required value="admin@mosesgroup.ph">
                        </div>
                    </div>

                    <div class="form-field-group">
                        <div class="input-pill-wrapper">
                            <div class="input-pill-icon">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <input type="password" class="pill-control-input" id="password" name="password" placeholder="Password" required value="Admin@123">
                            <button type="button" class="toggle-eye-pill" id="toggleEye" title="Toggle password visibility">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-meta-row">
                        <label class="remember-label-custom">
                            <input type="checkbox" class="checkbox-purple-custom" name="remember" checked>
                            <span>Remember me</span>
                        </label>
                        <a href="javascript:void(0)" class="forgot-link-custom" onclick="Swal.fire('Password Reset', 'Please contact Moses Group HR Administrator to reset your password.', 'info')">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-signin-pill" id="btnLogin">
                        <span id="btnText">SIGN IN</span>
                        <i class="fa-solid fa-right-to-bracket" id="btnIcon" style="display:none;"></i>
                    </button>
                </form>

                <div class="bottom-account-text">
                    Don't have an account? <a href="javascript:void(0)" onclick="Swal.fire('Account Creation', 'Please contact Agency HR to create a new user account.', 'info')">Create</a>
                </div>
            </div>

            <div class="info-column">
                <h2 class="welcome-hero-title">Welcome Back!</h2>
                <p class="welcome-hero-desc">
                    Access the Moses Group HR Management Portal to process OFW candidates, PDOS training records, visa compliance, OWWA benefits, and flight deployments.
                </p>

                <div>
                    <div class="demo-pills-title"><i class="fa-solid fa-key me-1"></i> Quick Demo Access</div>
                    <div class="demo-cards-row">
                        <div class="demo-card-item" data-user="admin@mosesgroup.ph" data-pass="Admin@123">
                            <div class="demo-role-name">Admin</div>
                            <div class="demo-user-val">admin@mosesgroup.ph</div>
                        </div>
                        <div class="demo-card-item" data-user="hr@mosesgroup.ph" data-pass="Hr@123">
                            <div class="demo-role-name">HR Manager</div>
                            <div class="demo-user-val">hr@mosesgroup.ph</div>
                        </div>
                        <div class="demo-card-item" data-user="employee@mosesgroup.ph" data-pass="Emp@123">
                            <div class="demo-role-name">Employee</div>
                            <div class="demo-user-val">employee@mosesgroup.ph</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>

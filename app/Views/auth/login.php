<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Sign In | Core 3</title>
 <link rel="icon" type="image/svg+xml" href="assets/images/logo-icon.svg">
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
 <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
 <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
 <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
 <link href="assets/css/login.css?v=<?= time(); ?>" rel="stylesheet">
</head>
<body class="auth-page">
 <main class="auth-shell">
  <section class="auth-card" aria-labelledby="loginTitle">
   <header class="auth-heading">
    <div class="auth-brand-mark"><img src="assets/images/logo-icon.svg" alt="Great Solomon Manpower Services Inc."></div>
    <h1 id="loginTitle">Sign In</h1>
    <p>Great Solomon Manpower Services Inc.</p>
   </header>
   <div class="auth-error" id="alertBox" role="alert"><span class="material-symbols-outlined">error</span><span id="alertMsg">Invalid credentials or inactive account.</span></div>
   <form id="loginForm" class="auth-form">
    <?= csrf_input(); ?>
    <label for="username">Email Address</label>
    <div class="auth-input"><span class="material-symbols-outlined">mail</span><input type="text" id="username" name="username" placeholder="admin@ismers.local" autocomplete="username" required value="admin@ismers.local"></div>
    <label for="password">Password</label>
    <div class="auth-input"><span class="material-symbols-outlined">lock</span><input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required><button type="button" id="toggleEye" aria-label="Show password"><span class="material-symbols-outlined" id="eyeIcon">visibility</span></button></div>
    <div class="auth-options"><label class="remember"><input type="checkbox" name="remember"><span>Remember me</span></label><a href="javascript:void(0)" class="auth-link" onclick="Swal.fire('Password Reset','Please contact the System Administrator to reset your password.','info')">Forgot password?</a></div>
    <button type="submit" class="auth-submit" id="btnLogin"><span id="btnText">Sign In</span><i class="fa-solid fa-right-to-bracket" id="btnIcon" hidden></i></button>
   </form>
   <footer class="auth-footer">
    <a class="auth-back-link" href="javascript:history.back()"><span class="material-symbols-outlined">arrow_back</span>Back to System Hub</a>
   </footer>
  </section>
 </main>
 <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 <script src="assets/js/login.js"></script>
</body>
</html>

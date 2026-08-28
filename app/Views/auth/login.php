<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
 <title>Sign In | Core 3</title>
 <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
    <div class="auth-brand-mark"><span class="material-symbols-outlined">groups</span></div>
    <h1 id="loginTitle">Sign In</h1>
    <p>Core 3 — Employee Development, Compliance &amp; Benefits</p>
   </header>
   <div class="auth-error" id="alertBox" role="alert"><span class="material-symbols-outlined">error</span><span id="alertMsg">Invalid credentials or inactive account.</span></div>
   <form id="loginForm" class="auth-form">
    <?= csrf_input(); ?>
    <label for="username">Email Address</label>
    <div class="auth-input"><span class="material-symbols-outlined">mail</span><input type="text" id="username" name="username" placeholder="Enter email or username" autocomplete="username" required value="admin"></div>
    <label for="password">Password</label>
    <div class="auth-input"><span class="material-symbols-outlined">lock</span><input type="password" id="password" name="password" placeholder="Enter password" autocomplete="current-password" required value="admin123"><button type="button" id="toggleEye" aria-label="Show password"><i class="fa-solid fa-eye" id="eyeIcon"></i></button></div>
    <div class="auth-options"><label class="remember"><input type="checkbox" name="remember"><span>Remember me</span></label><a href="javascript:void(0)" class="auth-link" onclick="Swal.fire('Password Reset','Please contact the System Administrator to reset your password.','info')">Forgot password?</a></div>
    <button type="submit" class="auth-submit" id="btnLogin"><span id="btnText">Sign In</span><i class="fa-solid fa-right-to-bracket" id="btnIcon" hidden></i></button>
   </form>
   <footer class="auth-footer">
    <a class="auth-back-link" href="javascript:history.back()"><span class="material-symbols-outlined">arrow_back</span>Back to System Hub</a>
    <details class="demo-access"><summary>Development demo accounts</summary><div class="demo-cards-row"><button type="button" class="demo-card-item" data-user="admin" data-pass="admin123"><span class="demo-role-name">System Administrator</span><small class="demo-user-val">admin</small></button><button type="button" class="demo-card-item" data-user="employee" data-pass="user123"><span class="demo-role-name">Employee</span><small class="demo-user-val">employee</small></button></div></details>
   </footer>
  </section>
 </main>
 <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script src="assets/js/login.js"></script>
</body>
</html>

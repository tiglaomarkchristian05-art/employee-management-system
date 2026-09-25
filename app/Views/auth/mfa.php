<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verify Sign In | Core 3</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/logo-icon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="assets/css/login.css?v=<?= time(); ?>" rel="stylesheet">
</head>
<body class="auth-page mfa-page">
<main class="auth-shell">
    <section class="auth-card mfa-card" aria-labelledby="mfaTitle">
        <header class="auth-heading mfa-heading">
            <div class="auth-brand-mark mfa-brand-mark"><img src="assets/images/logo-icon.svg" alt="Great Solomon Manpower Services Inc."></div>
            <div class="mfa-security-badge"><span class="material-symbols-outlined">verified_user</span>Secure verification</div>
            <h1 id="mfaTitle">Verify your identity</h1>
            <p>We sent a 6-digit verification code to</p>
            <div class="mfa-email"><span class="material-symbols-outlined">mail</span><strong><?= htmlspecialchars($masked_email); ?></strong></div>
        </header>
        <div class="auth-error" id="alertBox" role="alert" aria-live="polite"><span class="material-symbols-outlined">error</span><span id="alertMsg"></span></div>
        <form id="mfaForm" class="auth-form mfa-form">
            <?= csrf_input(); ?>
            <label for="otp">Verification code</label>
            <div class="auth-input mfa-code-input"><span class="material-symbols-outlined">password</span><input type="text" id="otp" name="otp" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" placeholder="000000" aria-describedby="codeHelp" required autofocus></div>
            <p class="mfa-code-help" id="codeHelp">Enter the code exactly as shown in your email.</p>
            <button type="submit" class="auth-submit mfa-submit" id="verifyButton"><span id="verifyText">Verify and continue</span><span class="material-symbols-outlined">arrow_forward</span></button>
        </form>
        <div class="mfa-actions">
            <div class="mfa-resend-group"><span>Didn't receive the email?</span><button type="button" class="mfa-link-button" id="resendLink" disabled>Resend in 60s</button></div>
            <a href="index.php?page=login" class="auth-back-link"><span class="material-symbols-outlined">arrow_back</span>Back to sign in</a>
        </div>
        <footer class="auth-footer mfa-footer">
            <div class="mfa-notice"><span class="material-symbols-outlined">schedule</span><p>This code expires in <strong><?= MFA_OTP_TTL_MINUTES; ?> minutes</strong> and can only be used once.</p></div>
            <p class="mfa-safety-note">Never share your verification code with anyone.</p>
        </footer>
    </section>
</main>
<script>
const form=document.getElementById('mfaForm'),otp=document.getElementById('otp'),alertBox=document.getElementById('alertBox'),alertMsg=document.getElementById('alertMsg'),button=document.getElementById('verifyButton'),verifyText=document.getElementById('verifyText'),resend=document.getElementById('resendLink');
function show(message,success=false){alertMsg.textContent=message;alertBox.classList.toggle('mfa-message-success',success);alertBox.querySelector('.material-symbols-outlined').textContent=success?'check_circle':'error';alertBox.style.display='flex'}
async function send(url,data){const response=await fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data)});return response.json()}
function startResendCountdown(seconds=60){let remaining=seconds;resend.disabled=true;resend.textContent=`Resend in ${remaining}s`;const timer=setInterval(()=>{remaining--;if(remaining<=0){clearInterval(timer);resend.disabled=false;resend.textContent='Resend code';return}resend.textContent=`Resend in ${remaining}s`},1000)}
otp.addEventListener('input',()=>{otp.value=otp.value.replace(/\D/g,'').slice(0,6);alertBox.style.display='none'});
form.addEventListener('submit',async event=>{event.preventDefault();alertBox.style.display='none';button.disabled=true;verifyText.textContent='Verifying...';try{const result=await send('index.php?page=mfa_verify',Object.fromEntries(new FormData(form)));if(result.status==='success'){verifyText.textContent='Verified';location.href=result.data.redirect;return}show(result.message);if(result.data?.redirect)setTimeout(()=>location.href=result.data.redirect,1200)}catch(error){show('Connection error. Please try again.')}finally{button.disabled=false;if(verifyText.textContent!=='Verified')verifyText.textContent='Verify and continue'}});
resend.addEventListener('click',async()=>{if(resend.disabled)return;resend.disabled=true;resend.textContent='Sending...';try{const result=await send('index.php?page=mfa_resend',{csrf_token:form.querySelector('[name=csrf_token]').value});show(result.message,result.status==='success');if(result.data?.redirect){setTimeout(()=>location.href=result.data.redirect,1200);return}startResendCountdown(result.data?.retry_after||60)}catch(error){show('Connection error. Please try again.');resend.disabled=false;resend.textContent='Resend code'}});
startResendCountdown(60);
</script>
</body></html>

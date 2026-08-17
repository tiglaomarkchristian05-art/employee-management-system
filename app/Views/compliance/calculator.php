<?php
require APP_PATH . 'Views/layouts/header.php';
require APP_PATH . 'Views/layouts/sidebar.php';
?>

<div id="main-content">
    <?php require APP_PATH . 'Views/layouts/navbar.php'; ?>

    <div class="my-3">
        <a href="index.php?page=compliance" class="btn btn-sm btn-outline-secondary mb-2"><i class="fa-solid fa-arrow-left me-1"></i> Back to Compliance</a>
        <h4 class="fw-bold text-light mb-1"><i class="fa-solid fa-calculator text-success me-2"></i> Live Philippine Statutory & Tax Calculator (TRAIN Law)</h4>
        <p class="text-secondary">Enter a monthly gross compensation salary to simulate real-time SSS, PhilHealth, Pag-IBIG HDMF, and BIR Withholding Tax deductions.</p>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <div class="card p-4 bg-white text-dark border border-light shadow-sm" style="background-color: #ffffff !important;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-sliders text-success me-2"></i> Salary Input Parameters</h5>
                <div class="mb-3">
                    <label class="form-label text-secondary fw-bold">Monthly Gross Salary (PHP)</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light border-secondary-subtle text-dark fw-bold">₱</span>
                        <input type="number" class="form-control bg-white text-dark border-secondary-subtle" id="calcGrossInput" value="50000" placeholder="e.g. 50000" step="500" style="background-color: #ffffff !important; color: #000000 !important;">
                    </div>
                </div>

                <div class="alert bg-light border-info text-dark small mb-0" style="background-color: #f8f9fa !important;">
                    <i class="fa-solid fa-circle-info text-info me-1"></i> <strong>2026 Statutory Rules Applied:</strong><br>
                    • SSS Employee share (4.5%, MSC cap ₱30,000)<br>
                    • PhilHealth Rate (5.0% split 50-50)<br>
                    • Pag-IBIG HDMF (2% cap ₱200)<br>
                    • BIR TRAIN Law Progressive Withholding Tax Brackets
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card p-4 bg-white text-dark border border-light shadow-sm" style="background-color: #ffffff !important;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Deduction & Net Take-Home Breakdown</h5>
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="p-3 rounded bg-white border border-light shadow-sm" style="background-color: #ffffff !important;">
                            <small class="text-secondary fw-bold">SSS Employee Share</small>
                            <h5 class="fw-bold text-primary mb-0 mt-1" id="resSSSEmp">₱1,350.00</h5>
                            <small class="text-muted" id="resSSSComp">Employer: ₱2,850.00</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded bg-white border border-light shadow-sm" style="background-color: #ffffff !important;">
                            <small class="text-secondary fw-bold">PhilHealth Premium (EE)</small>
                            <h5 class="fw-bold text-success mb-0 mt-1" id="resPHEmp">₱1,250.00</h5>
                            <small class="text-muted" id="resPHComp">Employer: ₱1,250.00</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded bg-white border border-light shadow-sm" style="background-color: #ffffff !important;">
                            <small class="text-secondary fw-bold">Pag-IBIG HDMF (EE)</small>
                            <h5 class="fw-bold text-warning mb-0 mt-1" id="resHDMFEmp">₱200.00</h5>
                            <small class="text-muted" id="resHDMFComp">Employer: ₱200.00</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded bg-white border border-light shadow-sm" style="background-color: #ffffff !important;">
                            <small class="text-secondary fw-bold">BIR Withholding Tax</small>
                            <h5 class="fw-bold text-danger mb-0 mt-1" id="resBIRTax">₱4,648.40</h5>
                            <small class="text-muted">Progressive TRAIN Tier</small>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded bg-white border border-success shadow-sm d-flex align-items-center justify-content-between" style="background-color: #ffffff !important;">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Estimated Monthly Net Take-Home Pay</h6>
                        <small class="text-secondary">Gross minus all mandatory statutory deductions & taxes</small>
                    </div>
                    <h3 class="fw-bold text-success mb-0" id="resNetTakeHome">₱42,551.60</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
function runCalc() {
    const gross = $('#calcGrossInput').val();
    const res = calculatePHStatutory(gross);

    $('#resSSSEmp').text('₱' + parseFloat(res.sssEmp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resSSSComp').text('Employer: ₱' + parseFloat(res.sssComp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resPHEmp').text('₱' + parseFloat(res.phEmp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resPHComp').text('Employer: ₱' + parseFloat(res.phComp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resHDMFEmp').text('₱' + parseFloat(res.hdmfEmp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resHDMFComp').text('Employer: ₱' + parseFloat(res.hdmfComp).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resBIRTax').text('₱' + parseFloat(res.birTax).toLocaleString('en-US', {minimumFractionDigits:2}));
    $('#resNetTakeHome').text('₱' + parseFloat(res.netTakeHome).toLocaleString('en-US', {minimumFractionDigits:2}));
}

$('#calcGrossInput').on('input', runCalc);
$(document).ready(runCalc);
</script>

<?php require APP_PATH . 'Views/layouts/footer.php'; ?>

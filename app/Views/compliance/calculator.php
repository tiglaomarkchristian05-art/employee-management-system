<?php
require APP_PATH.'Views/layouts/header.php';
require APP_PATH.'Views/layouts/sidebar.php';
?>
<div id="main-content">
<?php require APP_PATH.'Views/layouts/navbar.php'; ?>
<div class="glass-card p-4 my-3">
<h4>Statutory Calculation - Local Demonstration</h4>
<div class="alert alert-warning">Legacy project values only. These are NOT validated official rates. Client/payroll/legal confirmation, a source and effective dates are required before posting monetary records. Existing government ID and correction workflows remain available.</div>
<label for="calcGrossInput" class="form-label">Monthly gross salary (PHP)</label>
<input type="number" min="0.01" step="0.01" id="calcGrossInput" class="form-control" placeholder="Enter an amount">
<button type="button" id="calculateDemo" class="btn btn-primary mt-3">Preview unvalidated calculation</button>
<p id="calculationMessage" class="mt-3" role="status"></p>
<table class="table" id="calculationResults" hidden><thead><tr><th>Legacy calculation item</th><th>Amount (PHP)</th></tr></thead><tbody></tbody></table>
</div></div>
<script>
$(function(){
 const results=$('#calculationResults'),message=$('#calculationMessage');
 $('#calcGrossInput').on('input',()=>{results.prop('hidden',true);message.text('');});
 $('#calculateDemo').on('click',function(){
  results.prop('hidden',true);message.text('Checking configured range...');
  $.getJSON('index.php?page=compliance_calculator',{gross:$('#calcGrossInput').val()})
   .done(function(r){
    if(r.status!=='success'){message.text(r.message);return;}
    const labels={sssEmp:'SSS employee',sssComp:'SSS employer',phEmp:'PhilHealth employee',phComp:'PhilHealth employer',hdmfEmp:'Pag-IBIG employee',hdmfComp:'Pag-IBIG employer',birTax:'Withholding tax',totalStatutory:'Total employee deductions',netTakeHome:'Illustrative net - NOT payroll output'};
    const body=results.find('tbody').empty();
    Object.entries(labels).forEach(([key,label])=>body.append($('<tr>').append($('<td>').text(label),$('<td>').text(Number(r.data[key]).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})))));
    results.prop('hidden',false);message.text(r.message);
   }).fail(xhr=>message.text(xhr.responseJSON?.message||'Calculation rule requires current statutory configuration.'));
 });
});
</script>
<?php require APP_PATH.'Views/layouts/footer.php'; ?>

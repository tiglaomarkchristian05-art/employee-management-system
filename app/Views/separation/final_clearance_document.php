<?php
$escape = static fn($value) => htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$date = static function ($value, bool $withTime = false) use ($escape): string {
    if (!$value) return '—';
    $timestamp = strtotime((string)$value);
    return $timestamp ? $escape(date($withTime ? 'F j, Y · g:i A' : 'F j, Y', $timestamp)) : $escape($value);
};
$employeeName = trim((string)$sep['first_name'] . ' ' . (string)$sep['last_name']);
$payrollStatus = (string)($sep['final_pay_processing_status'] ?? 'Not Ready');
$handoffReady = $payrollStatus === 'Ready for Payroll';
$mandatoryCount = count(array_filter($items, static fn($item) => (int)$item['is_mandatory'] === 1));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Final Exit Clearance — <?=$escape($sep['employee_code'])?></title>
<style>
  @page { size: A4 portrait; margin: 14mm; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #eef0fa; color: #17243d; font: 14px/1.5 Arial, Helvetica, sans-serif; }
  .document { width: 210mm; min-height: 297mm; margin: 24px auto; padding: 18mm 17mm; background: #fff; border: 1px solid #dce2f4; box-shadow: 0 18px 48px #1f2a5b1a; }
  .topline { height: 5px; background: #5145de; margin: -18mm -17mm 15mm; }
  .header { display: flex; align-items: center; justify-content: space-between; gap: 20px; border-bottom: 1px solid #dfe4f0; padding-bottom: 17px; }
  .brand { display: flex; align-items: center; gap: 13px; }
  .brand img { width: 52px; height: 52px; object-fit: contain; }
  .brand-name { color: #28357c; font-weight: 700; font-size: 16px; line-height: 1.25; }
  .brand-sub { color: #697898; font-size: 10px; text-transform: uppercase; letter-spacing: 1.4px; margin-top: 4px; }
  .doc-label { text-align: right; color: #64718b; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
  .doc-label strong { display: block; color: #17243d; font-size: 12px; letter-spacing: 0; margin-top: 3px; }
  .eyebrow { color: #5145de; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin: 26px 0 5px; }
  h1 { font-size: 27px; line-height: 1.2; margin: 0 0 8px; color: #15203a; }
  .intro { margin: 0 0 22px; color: #61708e; font-size: 12px; max-width: 590px; }
  .status { display: flex; align-items: center; gap: 12px; border: 1px solid #b8e7d6; background: #edf9f3; border-radius: 9px; padding: 12px 14px; margin-bottom: 22px; }
  .status-mark { display: inline-flex; align-items: center; justify-content: center; width: 31px; height: 31px; border-radius: 50%; background: #0d9967; color: #fff; font-size: 17px; font-weight: 700; flex: none; }
  .status strong { color: #087452; font-size: 12px; }
  .status small { display: block; color: #507160; margin-top: 1px; }
  h2 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #354470; margin: 23px 0 10px; }
  .details { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid #e0e5f0; border-radius: 8px; overflow: hidden; }
  .field { padding: 11px 13px; min-height: 60px; border-bottom: 1px solid #e7ebf3; }
  .field:nth-child(odd) { border-right: 1px solid #e7ebf3; }
  .field:nth-last-child(-n+2) { border-bottom: 0; }
  .field span { display: block; color: #70809e; font-size: 10px; text-transform: uppercase; letter-spacing: .7px; }
  .field strong { display: block; font-size: 13px; margin-top: 3px; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; table-layout: fixed; }
  thead th { background: #f0f2fb; color: #4b5b80; text-align: left; text-transform: uppercase; letter-spacing: .5px; font-size: 9px; padding: 9px 8px; }
  tbody td { padding: 9px 8px; border-bottom: 1px solid #e6eaf2; vertical-align: top; overflow-wrap: anywhere; }
  tbody tr:nth-child(even) { background: #fbfcff; }
  .cleared { color: #087452; font-weight: 700; }
  .handoff { border: 1px solid #cdd4fa; background: #f4f5ff; border-radius: 8px; padding: 15px; margin-top: 20px; }
  .handoff .label { color: #5145de; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; font-size: 10px; }
  .handoff strong { display: block; color: #242e80; font-size: 16px; margin: 4px 0; }
  .handoff p { color: #5e6c8b; font-size: 11px; margin: 0; }
  .signature { margin-top: 36px; display: flex; justify-content: flex-end; }
  .signature div { width: 220px; border-top: 1px solid #64718b; padding-top: 6px; text-align: center; color: #64718b; font-size: 10px; }
  .footer { border-top: 1px solid #dfe4f0; margin-top: 30px; padding-top: 9px; color: #7b89a3; font-size: 9px; display: flex; justify-content: space-between; gap: 10px; }
  @media print { body { background: #fff; } .document { width: auto; min-height: 0; margin: 0; padding: 0; border: 0; box-shadow: none; } .topline { margin: 0 0 13mm; } .status, .handoff, thead th { print-color-adjust: exact; -webkit-print-color-adjust: exact; } tr, .status, .handoff { break-inside: avoid; } }
</style>
</head>
<body>
<main class="document">
 <div class="topline"></div>
 <div class="header">
  <div class="brand"><?php if($logoData): ?><img src="<?=$escape($logoData)?>" alt="Company logo"><?php endif; ?><div><div class="brand-name"><?=$escape($companyName)?></div><div class="brand-sub">Human Resources · Exit Clearance</div></div></div>
  <div class="doc-label">Employee code<strong><?=$escape($sep['employee_code'])?></strong></div>
 </div>
 <p class="eyebrow">Official HR clearance record</p>
 <h1>Final Exit Clearance</h1>
 <p class="intro">This document records completion of the employee's department clearance and confirms the handoff status for the separate Payroll process.</p>
 <div class="status"><span class="status-mark">✓</span><div><strong>Final HR Clearance Completed</strong><small>Completed <?=$date($sep['completed_at'], true)?></small></div></div>
 <h2>Employee information</h2>
 <div class="details">
  <div class="field"><span>Employee</span><strong><?=$escape($employeeName)?></strong></div>
  <div class="field"><span>Employee ID</span><strong><?=$escape($sep['employee_code'])?></strong></div>
  <div class="field"><span>Department</span><strong><?=$escape($sep['department_name']?:'—')?></strong></div>
  <div class="field"><span>Position</span><strong><?=$escape($sep['position_title']?:'—')?></strong></div>
  <div class="field"><span>Separation type</span><strong><?=$escape($sep['separation_type'])?></strong></div>
  <div class="field"><span>Final working date</span><strong><?=$date($sep['final_working_date'])?></strong></div>
 </div>
 <h2>Department clearance · <?=count($items)?> items / <?=$mandatoryCount?> mandatory</h2>
 <table aria-label="Department clearance results"><thead><tr><th style="width:27%">Department</th><th style="width:16%">Requirement</th><th style="width:15%">Status</th><th style="width:22%">Verified by</th><th style="width:20%">Cleared on</th></tr></thead><tbody>
 <?php foreach($items as $item): ?><tr><td><?=$escape($item['department_name'])?></td><td><?=$escape($item['clearance_requirement']?:($item['is_mandatory']?'Mandatory':'Optional'))?></td><td class="cleared"><?=$escape($item['status'])?></td><td><?=$escape($item['responsible_name']?:$item['cleared_by']?:'—')?></td><td><?=$date($item['clearance_date'])?></td></tr><?php endforeach; ?>
 </tbody></table>
 <div class="handoff"><div class="label">Payroll handoff status</div><strong><?=$escape($payrollStatus)?></strong><p><?=$handoffReady?'All required HR clearance steps have been completed. This record is ready for processing by the separate Payroll subsystem.':'This record does not yet indicate a completed Payroll handoff.'?> No salary, deduction, or final-pay amount is calculated by Core 3.</p></div>
 <div class="signature"><div>Authorized HR Representative<br><strong><?=$escape($companyName)?></strong></div></div>
 <div class="footer"><span>System-generated final clearance record · Core 3</span><span>Reference: <?=$escape($sep['employee_code'])?> / Separation #<?=(int)$sep['id']?></span></div>
</main>
</body>
</html>

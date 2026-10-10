<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/core/Database.php';
require dirname(__DIR__) . '/core/Model.php';
require dirname(__DIR__) . '/app/Models/Separation.php';

$model = new Separation();
$row = $model->db->fetchOne("SELECT id FROM separations WHERE status='Completed' ORDER BY id LIMIT 1");
if (!$row) {
    echo "SKIP: no completed local separation to render.\n";
    exit(0);
}

$sep = $model->details((int)$row['id']);
$items = $model->getClearanceStatus((int)$row['id']);
$companyName = 'Great Solomon Manpower Services Inc.';
$logoData = '';
ob_start();
require dirname(__DIR__) . '/app/Views/separation/final_clearance_document.php';
$html = (string)ob_get_clean();

foreach (['Final Exit Clearance', 'Payroll handoff status', 'Ready for Payroll', $sep['employee_code']] as $expected) {
    if (strpos($html, $expected) === false) {
        throw new RuntimeException('Final clearance output is missing: ' . $expected);
    }
}
foreach (['Basic due', 'Loan deductions', 'Net Final Pay'] as $legacy) {
    if (strpos($html, $legacy) !== false) {
        throw new RuntimeException('Legacy monetary detail is visible: ' . $legacy);
    }
}
echo "PASS: final clearance document renders the completed local record and Payroll handoff without pay amounts.\n";

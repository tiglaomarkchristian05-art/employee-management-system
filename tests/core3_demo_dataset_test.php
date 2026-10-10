<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/core/Database.php';
require dirname(__DIR__) . '/core/Model.php';
require dirname(__DIR__) . '/app/Models/Benefit.php';
require dirname(__DIR__) . '/app/Models/Document.php';
require dirname(__DIR__) . '/app/Models/Separation.php';

$db = Database::getInstance();
$assert = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS: $message\n";
};
$employee = static function (string $code) use ($db): array {
    return $db->fetchOne('SELECT * FROM employees WHERE employee_code=?', [$code]) ?: throw new RuntimeException("Missing employee $code");
};
try {
    $assert((int)$db->fetchOne('SELECT COUNT(*) n FROM employees')['n'] === 12, '12 total employee profiles');
    $assert((int)$db->fetchOne("SELECT COUNT(*) n FROM employees WHERE employee_code LIKE 'DEMO-C3-%'")['n'] === 6, 'six new marked local demo profiles');
    $assert((int)$db->fetchOne('SELECT COUNT(*) n FROM (SELECT employee_id FROM users WHERE employee_id IS NOT NULL GROUP BY employee_id HAVING COUNT(*)>1) duplicate_links')['n'] === 0, 'no employee has duplicate linked users');
    $unlinked = $db->fetchAll('SELECT e.employee_code FROM employees e LEFT JOIN users u ON u.employee_id=e.id WHERE u.id IS NULL ORDER BY e.id');
    $assert(array_column($unlinked, 'employee_code') === ['OFW-2026-001','OFW-2026-006'], 'Create User dropdown has only Alexander and Clarissa as unlinked profiles');
    foreach (['DEMO-C3-001','DEMO-C3-002','DEMO-C3-003','DEMO-C3-004','DEMO-C3-005','DEMO-C3-006'] as $code) {
        $e = $employee($code);
        $assert(str_ends_with($e['email'], '@test.local') && (bool)$db->fetchOne('SELECT id FROM users WHERE employee_id=?', [$e['id']]), "$code has a local email and linked account");
    }
    $assert($employee('OFW-2026-004')['first_name']==='Maria', 'Maria Santos preserved');
    $plan = $db->fetchOne("SELECT id FROM benefit_plans WHERE name='Manual Test Medical Allowance'");
    $benefits = new Benefit();
    [$juanOk] = $benefits->eligibility((int)$plan['id'], (int)$employee('OFW-2026-005')['id']);
    [$lizaOk,$lizaReason] = $benefits->eligibility((int)$plan['id'], (int)$employee('DEMO-C3-003')['id']);
    $assert($juanOk && !$lizaOk && str_contains($lizaReason, 'status'), 'same benefit: Juan eligible, probationary Liza ineligible');
    $contracts = (new Document())->getContractsWithDetails();
    $byCode = [];
    foreach ($contracts as $contract) $byCode[$contract['employee_code']][] = $contract;
    $find = static function (string $code, string $type) use ($byCode): array {
        foreach ($byCode[$code] ?? [] as $row) if ($row['contract_type'] === $type) return $row;
        throw new RuntimeException("Missing contract for $code");
    };
    $healthy = $find('OFW-2026-002','LOCAL DEMO - Standard Contract');
    $expiring = $find('DEMO-C3-004','LOCAL DEMO - Expiring Contract');
    $assert($healthy['lifecycle_status']==='Active' && (int)$healthy['days_remaining']>=120, 'healthy contract is Active');
    $assert($expiring['lifecycle_status']==='Expiring Soon' && (int)$expiring['days_remaining']>=20 && (int)$expiring['days_remaining']<=30, '25-day contract is Expiring Soon');
    $j = $db->fetchOne('SELECT status,expiration_date FROM employee_certifications WHERE employee_id=? AND certification_name=?', [$employee('DEMO-C3-002')['id'],'SMAW 6G Pipe Welding Trade Certification']);
    $l = $db->fetchOne('SELECT status,expiration_date FROM employee_certifications WHERE employee_id=? AND certification_name=?', [$employee('DEMO-C3-003')['id'],'SMAW 6G Pipe Welding Trade Certification']);
    $assert($j['status']==='Expired' && $j['expiration_date']<date('Y-m-d') && $l['status']==='Valid' && $l['expiration_date']>date('Y-m-d'), 'same certification: John expired, Liza valid');
    $recommendation = $db->fetchOne("SELECT a.reason FROM ai_training_recommendations a JOIN training_courses c ON c.id=a.training_id WHERE a.employee_id=? AND c.title='LOCAL DEMO - SMAW 6G Certification Renewal'", [$employee('DEMO-C3-002')['id']]);
    $assert($recommendation && str_contains($recommendation['reason'], 'expired'), 'TNA explains expired certification for John');
    $lizaRenewal = $db->fetchOne("SELECT a.id FROM ai_training_recommendations a JOIN training_courses c ON c.id=a.training_id WHERE a.employee_id=? AND c.title='LOCAL DEMO - SMAW 6G Certification Renewal' AND a.status<>'Expired'", [$employee('DEMO-C3-003')['id']]);
    $assert(!$lizaRenewal, 'no false renewal recommendation for Liza');
    $carlo = $db->fetchOne('SELECT id,final_pay_processing_status FROM separations WHERE employee_id=?', [$employee('DEMO-C3-006')['id']]);
    $clarissa = $db->fetchOne('SELECT id,final_pay_processing_status FROM separations WHERE employee_id=?', [$employee('OFW-2026-006')['id']]);
    $exit = new Separation();
    $assert($exit->blockers((int)$carlo['id'])>0 && $carlo['final_pay_processing_status']==='Not Ready', 'Carlo clearance remains blocked by asset/accountability');
    $assert((int)$db->fetchOne('SELECT COUNT(*) n FROM clearances WHERE separation_id=? AND responsible_user_id IS NOT NULL', [$carlo['id']])['n']===4, 'Carlo clearance rows have assigned approvers');
    $assert($exit->blockers((int)$clarissa['id'])===0 && $clarissa['final_pay_processing_status']==='Ready for Payroll', 'Clarissa existing completed clearance remains ready');
    $loan = $db->fetchOne('SELECT id,balance_remaining,total_payable FROM loans WHERE employee_id=?', [$employee('DEMO-C3-005')['id']]);
    $assert($loan && $loan['balance_remaining']<$loan['total_payable'] && (int)$db->fetchOne('SELECT COUNT(*) n FROM loan_payments WHERE loan_id=?', [$loan['id']])['n']>0, 'Paolo active loan has payment history and remaining balance');
    foreach ($db->fetchAll("SELECT file_path FROM documents WHERE title LIKE 'LOCAL DEMO - %'") as $document) {
        $assert(is_file(dirname(__DIR__) . '/public/' . $document['file_path']), 'demo document has a downloadable local PDF');
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}

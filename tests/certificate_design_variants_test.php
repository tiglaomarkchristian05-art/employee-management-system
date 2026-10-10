<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/core/Database.php';
require dirname(__DIR__) . '/core/Model.php';
require dirname(__DIR__) . '/app/Services/TrainingCertificateService.php';

$db = Database::getInstance();
$courses = [];
$registrations = [];
$categories = [];
$files = [];
$assert = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
};

try {
    $employeeCodes = ['DEMO-C3-001', 'DEMO-C3-003', 'DEMO-C3-004'];
    $employees = [];
    foreach ($employeeCodes as $code) {
        $employee = $db->fetchOne('SELECT id FROM employees WHERE employee_code=?', [$code]);
        if (!$employee) throw new RuntimeException('Missing local demo employee: ' . $code);
        $employees[] = (int)$employee['id'];
    }
    $user = $db->fetchOne("SELECT id FROM users WHERE is_active=1 ORDER BY id LIMIT 1");
    if (!$user) throw new RuntimeException('No active issuing user exists.');
    $trainer = $db->fetchOne('SELECT id FROM trainers ORDER BY id LIMIT 1');
    $compliance = $db->fetchOne("SELECT id FROM training_categories WHERE name='Compliance, Data Privacy & Information Security'");
    if (!$compliance) throw new RuntimeException('Configured compliance category was not found.');

    $healthCategory = (int)$db->insert('training_categories', ['name' => 'LOCAL TEST - Health & Safety']);
    $leadershipCategory = (int)$db->insert('training_categories', ['name' => 'LOCAL TEST - Leadership & Management']);
    $categories = [$healthCategory, $leadershipCategory];
    $cases = [
        ['A', 'LOCAL TEST - Data Privacy Awareness', (int)$compliance['id'], 8, 95, 92, 12, $trainer['id'] ?? null],
        ['B', 'LOCAL TEST - First Aid and Emergency Response', $healthCategory, 4, 87, 86, 0, null],
        ['C', 'LOCAL TEST - Leadership Development Program', $leadershipCategory, 16, 100, 88, 0, $trainer['id'] ?? null],
    ];

    $service = new TrainingCertificateService();
    foreach ($cases as $index => [$label, $title, $categoryId, $hours, $attendance, $assessment, $validity, $trainerId]) {
        $courseId = (int)$db->insert('training_courses', [
            'category_id' => $categoryId, 'title' => $title, 'description' => 'Local certificate layout test',
            'course_type' => 'Internal', 'duration_hours' => $hours, 'trainer_id' => $trainerId,
            'venue' => 'Local QA', 'start_date' => date('Y-m-d', strtotime('-2 days')),
            'end_date' => date('Y-m-d', strtotime('-1 day')), 'status' => 'Completed',
            'capacity' => 30, 'certification_validity_months' => $validity
        ]);
        $courses[] = $courseId;
        $registrationId = (int)$db->insert('training_registrations', [
            'course_id' => $courseId, 'employee_id' => $employees[$index], 'status' => 'Completed',
            'attendance_percentage' => $attendance, 'assessment_result' => $assessment,
            'completed_at' => date('Y-m-d')
        ]);
        $registrations[] = $registrationId;
        [$eligible] = $service->eligibility($registrationId);
        $assert($eligible, "case {$label} meets the existing completion requirements");
        $issued = $service->generate($registrationId, (int)$user['id']);
        $files[] = $issued['path'];
        $record = $service->record($registrationId);
        $pdf = file_get_contents(dirname(__DIR__) . '/public/' . $issued['path']);
        $assert($issued['generated'] && str_starts_with($pdf, '%PDF'), "case {$label} generated through the real service");
        $assert(preg_match_all('/\/Type\s*\/Page\b/', $pdf) === 1, "case {$label} fits one landscape page");
        $assert($record['course_title'] === $title && (int)$record['duration_hours'] === $hours
            && (int)$record['attendance_percentage'] === $attendance
            && (float)$record['assessment_result'] === (float)$assessment,
            "case {$label} keeps its own course, duration, attendance and assessment");
        $registry = $db->fetchOne('SELECT expiration_date FROM employee_certifications WHERE related_training_registration_id=?', [$registrationId]);
        $assert((bool)$registry && ($validity > 0 ? $registry['expiration_date'] !== null : $registry['expiration_date'] === null),
            "case {$label} registry validity follows its own course setting");
        echo "CASE_{$label}_PDF=" . dirname(__DIR__) . '/public/' . $issued['path'] . "\n";
    }

    $renderer = new CertificatePdfRenderer();
    $long = $record;
    $long['first_name'] = 'Christopher Alexander';
    $long['last_name'] = 'De la Cruz Santos';
    $long['course_title'] = 'Advanced Data Privacy, Information Security, Cybersecurity Awareness and Regulatory Compliance';
    $long['category_name'] = 'Compliance, Data Privacy, Cybersecurity and Information Governance';
    $longPdf = $renderer->render($long, 'LOCAL-LONG-TEST', 'LOCAL-VERIFY', date('Y-m-d H:i:s'), 'Moses Group of Companies');
    $assert(preg_match_all('/\/Type\s*\/Page\b/', $longPdf) === 1, 'long name, title and category fit one landscape page');

    $minimal = $record;
    $minimal['first_name'] = 'Ana';
    $minimal['last_name'] = 'Cruz';
    $minimal['course_title'] = 'First Aid';
    $minimal['trainer_name'] = null;
    $minimal['assessment_result'] = null;
    $minimal['duration_hours'] = null;
    $minimalPdf = $renderer->render($minimal, 'LOCAL-FALLBACK-TEST', 'LOCAL-VERIFY', date('Y-m-d H:i:s'), 'Moses Group of Companies');
    $assert(preg_match_all('/\/Type\s*\/Page\b/', $minimalPdf) === 1, 'missing-data display and short content fit one landscape page');
} finally {
    foreach ($registrations as $registrationId) {
        $db->delete('employee_certifications', 'related_training_registration_id=?', [$registrationId]);
        $db->delete('training_certificate_versions', 'registration_id=?', [$registrationId]);
        $db->delete('training_registrations', 'id=?', [$registrationId]);
    }
    foreach ($courses as $courseId) $db->delete('training_courses', 'id=?', [$courseId]);
    foreach ($categories as $categoryId) $db->delete('training_categories', 'id=?', [$categoryId]);
    foreach ($files as $relative) {
        if (!str_starts_with($relative, 'uploads/training/certificates/generated/certificate-')) continue;
        $absolute = dirname(__DIR__) . '/public/' . $relative;
        if (is_file($absolute)) unlink($absolute);
    }
}

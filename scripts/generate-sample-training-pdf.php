<?php

declare(strict_types=1);

$output = dirname(__DIR__) . '/sample-training-material-data-privacy.pdf';

$pages = [
    [
        'DATA PRIVACY AND APPLICANT INFORMATION SECURITY',
        'Sample Training Material',
        '',
        'Purpose',
        'To help employees handle applicant and employee information securely, lawfully, and confidentially.',
        '',
        'Learning objectives',
        '1. Identify personal and sensitive personal information.',
        '2. Apply proper collection, access, storage, sharing, and disposal practices.',
        '3. Recognize common privacy and information-security risks.',
        '4. Report suspected data incidents promptly.',
        '',
        'Important principle',
        'Access personal information only when it is necessary for an authorized work purpose.',
    ],
    [
        'SECURE DATA-HANDLING PRACTICES',
        '',
        'Collection',
        '- Collect only information required for a legitimate business process.',
        '- Explain why the information is needed and how it will be used.',
        '',
        'Access and storage',
        '- Use individual accounts and strong passwords. Never share credentials.',
        '- Keep paper records in secured cabinets and digital files in approved systems.',
        '- Do not copy employee records to personal devices or unapproved cloud storage.',
        '',
        'Sharing',
        '- Verify the recipient and authorization before sending a record.',
        '- Use the minimum information necessary and approved secure channels.',
        '- Recheck email recipients and attachments before clicking Send.',
        '',
        'Disposal',
        '- Follow the retention schedule and approved secure-disposal procedure.',
    ],
    [
        'INCIDENT RESPONSE AND KNOWLEDGE CHECK',
        '',
        'If information may have been exposed',
        '1. Stop the activity, but do not delete evidence or conceal the incident.',
        '2. Inform the designated supervisor, HR, or data-protection contact immediately.',
        '3. Record what happened, when it happened, and what information was involved.',
        '4. Follow instructions for containment, investigation, and notification.',
        '',
        'Knowledge check',
        '1. May you send applicant documents to your personal email for convenience?',
        '2. What should you verify before sharing an employee record?',
        '3. Who should be notified when a record is sent to the wrong recipient?',
        '',
        'Expected answers',
        '1. No. Use only approved organizational systems and channels.',
        '2. The recipient, authorization, purpose, and minimum necessary information.',
        '3. The designated supervisor, HR, or data-protection contact immediately.',
        '',
        'Acknowledgement',
        'I understand my responsibility to protect applicant and employee information.',
        '',
        'Employee name: ____________________   Date: __________   Signature: ____________________',
    ],
];

function pdfEscape(string $value): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

$objects = [];
$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
$kids = [];
$nextObject = 4;
$pageObjects = [];

foreach ($pages as $pageLines) {
    $pageObject = $nextObject++;
    $contentObject = $nextObject++;
    $kids[] = $pageObject . ' 0 R';

    $commands = ["BT", "/F1 18 Tf", "50 790 Td"];
    foreach ($pageLines as $index => $line) {
        if ($index === 0) {
            $commands[] = '(' . pdfEscape($line) . ') Tj';
            $commands[] = '/F1 11 Tf';
            $commands[] = '0 -28 Td';
            continue;
        }
        $commands[] = '(' . pdfEscape($line) . ') Tj';
        $commands[] = '0 -22 Td';
    }
    $commands[] = 'ET';
    $stream = implode("\n", $commands) . "\n";

    $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] '
        . '/Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
    $objects[$contentObject] = "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "endstream";
    $pageObjects[] = $pageObject;
}

$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
ksort($objects);

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $number => $object) {
    $offsets[$number] = strlen($pdf);
    $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
}

$xref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($number = 1; $number <= count($objects); $number++) {
    $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
}
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
$pdf .= "startxref\n" . $xref . "\n%%EOF\n";

file_put_contents($output, $pdf);
echo $output . PHP_EOL;

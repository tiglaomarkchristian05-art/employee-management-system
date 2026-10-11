<?php

require_once ROOT_PATH . 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class CertificatePdfRenderer {
    public function render(array $record, string $number, string $verification, string $issuedAt, string $company, ?int $certificateVersion = null): string {
        $escape = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        $name = $escape(trim($record['first_name'] . ' ' . $record['last_name']));
        $course = $escape($record['course_title']);
        $category = $escape($record['category_name'] ?: 'Professional Development');
        $trainerName = trim((string)($record['trainer_name'] ?? ''));
        $trainer = $escape($trainerName ?: 'Training Facilitator');
        $companyName = $escape($company);
        $completedAt = $record['completed_at'] ?: ($record['end_date'] ?? null);
        $completion = $completedAt ? $escape(date('F j, Y', strtotime($completedAt))) : 'Not specified';
        $issueDate = $escape(date('F j, Y', strtotime($issuedAt)));
        $certificateNumber = $escape($number);
        $verificationCode = $escape($verification);
        $version = $certificateVersion ?? ((int)$record['certificate_version'] + 1);
        $duration = (int)($record['duration_hours'] ?? 0);
        $durationText = $duration > 0 ? $duration . ' ' . ($duration === 1 ? 'hour' : 'hours') : 'Not specified';
        $attendanceValue = $record['attendance_percentage'] ?? null;
        $attendance = $attendanceValue === null ? 'Not specified' : rtrim(rtrim(number_format((float)$attendanceValue, 1), '0'), '.') . '%';
        $assessmentValue = $record['assessment_result'] ?? null;
        $assessment = (int)($record['assessment_required']??1)===0 ? 'Not Required' : ($assessmentValue===null?'Not recorded':number_format((float)$assessmentValue, 1) . '%');
        $validityMonths = max(0, (int)($record['certification_validity_months'] ?? 0));
        $validUntil = $completedAt && $validityMonths > 0
            ? date('F j, Y', strtotime($completedAt . ' +' . $validityMonths . ' months')) : null;
        $validityHtml = $validUntil ? '<div class="validity">Valid Until: ' . $escape($validUntil) . '</div>' : '';
        $nameSize = mb_strlen($name) > 38 ? '26px' : '34px';
        $courseSize = mb_strlen($course) > 70 ? '20px' : (mb_strlen($course) > 46 ? '23px' : '27px');
        $logoPath = ROOT_PATH . 'assets/images/logo-icon.svg';
        $logoSvg = is_file($logoPath) ? file_get_contents($logoPath) : '';
        $logoSvg = str_replace('url(#themeIndigo)', '#4553FF', $logoSvg);
        $logoHtml = $logoSvg !== '' ? '<div class="logo-slot"><img class="logo" src="data:image/svg+xml;base64,' . base64_encode($logoSvg) . '" alt="Company logo"></div>' : '';
        $ornamentPath = ROOT_PATH . 'assets/images/certificate-ornament.svg';
        $ornamentSvg = is_file($ornamentPath) ? file_get_contents($ornamentPath) : '';
        if ($ornamentSvg !== '' && preg_match('/<path\b[^>]*\bd="([^"]+)"/', $logoSvg, $logoMatch)) {
            $sealLogo = '<g transform="translate(960.4 64.5) scale(.05)"><path d="'
                . htmlspecialchars($logoMatch[1], ENT_QUOTES, 'UTF-8')
                . '" fill="#4553FF" fill-rule="evenodd"/></g>';
            $ornamentSvg = str_replace('<!-- CERTIFICATE_SEAL_LOGO -->', $sealLogo, $ornamentSvg);
        }
        $ornament = $ornamentSvg !== '' ? 'data:image/svg+xml;base64,' . base64_encode($ornamentSvg) : '';
        $ornamentHtml = $ornament !== '' ? '<img class="ornament" src="' . $ornament . '" alt="">' : '';

        $html = <<<HTML
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><style>
@page { size: A4 landscape; margin: 0; }
* { box-sizing: border-box; }
body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #07144F; background: #fff; }
.sheet { width: 273mm; margin: 6mm auto; padding: 0; position: relative; }
.ornament { position: absolute; top: -6mm; left: -12mm; width: 297mm; height: 210mm; }
.frame { position: relative; padding: 3mm 2mm; }
.inner { position: relative; padding: 4mm 12mm 5mm; text-align: center; }
.header { width: 100%; text-align: center; }
.logo-slot { height: 25mm; text-align: center; }
.logo { display: block; width: 13mm; height: 13mm; margin: 0 auto; }
.validity { font-size: 9px; color: #5269B8; margin-top: 1mm; }
.company { font-weight: bold; font-size: 17px; color: #07144F; letter-spacing: .2px; white-space: nowrap; }
.department { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #5269B8; letter-spacing: 2px; margin-top: 1mm; white-space: nowrap; }
.rule { width: 64mm; height: 1px; background: #4553FF; margin: 2mm auto 2mm; }
.eyebrow { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; font-weight: bold; letter-spacing: 2.8px; color: #5269B8; text-transform: uppercase; }
h1 { margin: 2mm 0 2mm; font-family: DejaVu Serif, Georgia, serif; font-size: 31px; font-weight: bold; letter-spacing: 1px; color: #07144F; }
.title-flourish { font-size: 12px; color: #4553FF; letter-spacing: 2px; margin: 0 0 1mm; }
.intro { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #5269B8; margin: 0; }
.recipient { font-family: DejaVu Serif, Georgia, serif; font-size: {$nameSize}; color: #19129A; font-weight: bold; margin: 1mm auto 0; line-height: 1.2; }
.name-rule { width: 115mm; border-top: 1px solid #4553FF; margin: 0 auto 1mm; }
.completed-text { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #5269B8; margin: 0 0 1mm; }
.course { font-family: DejaVu Serif, Georgia, serif; font-size: {$courseSize}; font-weight: bold; line-height: 1.2; color: #07144F; margin: 0 auto 2mm; max-width: 225mm; }
.achievement { display: inline-block; color: #FFFFFF; background: #4553FF; border: 2px solid #AAB4FF; padding: 2mm 11mm; border-radius: 10mm; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; font-weight: bold; }
.facts { width: 100%; border-collapse: collapse; margin: 3mm auto 0; table-layout: fixed; background: #F8FAFF; }
.facts td { width: 20%; border-right: 1px solid #AAB4FF; padding: 1mm 2mm; vertical-align: top; }
.facts td:last-child { border-right: 0; }
.facts .icon { display: none; }
.facts .label { display: block; font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #5269B8; margin-bottom: 2mm; }
.facts .value { display: block; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; font-weight: bold; color: #07144F; line-height: 1.35; }
.signatures { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 5mm; }
.signatures td { width: 33.33%; padding: 0 5mm; vertical-align: bottom; text-align: center; }
.signature-line { border-top: 1px solid #273C97; margin: 0 auto 2mm; width: 55mm; }
.signature-name { font-size: 9px; font-weight: bold; color: #07144F; }
.signature-role { font-size: 8px; color: #5269B8; margin-top: 1mm; }
.footer { margin-top: 4mm; border-top: 1px solid #4553FF; padding-top: 2mm; width: 100%; border-collapse: collapse; }
.footer td { width: 50%; font-size: 8px; color: #5269B8; line-height: 1.5; }
.footer td:last-child { text-align: right; }
.footer strong { color: #19129A; }
</style></head><body><div class="sheet">{$ornamentHtml}<div class="frame"><div class="inner">
<div class="header">{$logoHtml}<div class="company">{$companyName}</div><div class="department">HUMAN RESOURCES &bull; TRAINING &amp; DEVELOPMENT</div></div>
<div class="rule"></div><div class="eyebrow">Recognition of achievement</div>
<h1>CERTIFICATE OF COMPLETION</h1><div class="title-flourish">&mdash; &#9670; &mdash;</div>
<p class="intro">This is to certify that</p><div class="recipient">{$name}</div><div class="name-rule"></div>
<p class="completed-text">has successfully completed the professional development program</p>
<div class="course">{$course}</div><div class="achievement">SUCCESSFULLY COMPLETED</div>{$validityHtml}
<table class="facts"><tr>
<td><span class="icon">&#9671;</span><span class="label">Category</span><span class="value">{$category}</span></td>
<td><span class="icon">&#9671;</span><span class="label">Completion date</span><span class="value">{$completion}</span></td>
<td><span class="icon">&#9671;</span><span class="label">Duration</span><span class="value">{$durationText}</span></td>
<td><span class="icon">&#9671;</span><span class="label">Attendance</span><span class="value">{$attendance}</span></td>
<td><span class="icon">&#9671;</span><span class="label">Assessment</span><span class="value">{$assessment}</span></td>
</tr></table>
<table class="signatures"><tr>
<td><div class="signature-line"></div><div class="signature-name">Authorized HR Representative</div><div class="signature-role">{$companyName}</div></td>
<td><div class="signature-line"></div><div class="signature-name">{$trainer}</div><div class="signature-role">Trainer / Facilitator</div></td>
<td><div class="signature-line"></div><div class="signature-name">Date Issued</div><div class="signature-role">{$issueDate}</div></td>
</tr></table>
<table class="footer"><tr><td>Certificate No. <strong>{$certificateNumber}</strong><br>Version {$version} &bull; Issued {$issueDate}</td><td>Verification code <strong>{$verificationCode}</strong><br>Validate through the internal Certification Registry</td></tr></table>
</div></div></div></body></html>
HTML;

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->setChroot(ROOT_PATH);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        return $pdf->output();
    }
}

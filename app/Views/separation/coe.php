<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Employment - <?= htmlspecialchars($separation['first_name'] . ' ' . $separation['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-family: 'Times New Roman', Times, serif;
            padding: 40px;
        }
        .coe-paper {
            background: #ffffff;
            border: 2px solid #cbd5e1;
            padding: 60px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header-logo {
            font-size: 2rem;
            font-family: sans-serif;
            font-weight: 700;
            color: #0284c7;
        }
        @media print {
            body { padding: 0; background: none; }
            .coe-paper { border: none; box-shadow: none; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-4 no-print">
        <button class="btn btn-primary btn-lg shadow fw-bold" onclick="window.print()"><i class="fa-solid fa-print me-2"></i> Print / Export PDF</button>
        <button class="btn btn-secondary btn-lg ms-2" onclick="window.close()">Close Window</button>
    </div>

    <div class="coe-paper">
        <div class="text-center mb-5 border-bottom pb-4">
            <div class="header-logo mb-1"><i class="fa-solid fa-layer-group"></i> <?= APP_COMPANY; ?></div>
            <div class="text-muted small">Human Resource Department | Taguig City, Metro Manila, Philippines</div>
            <div class="text-muted small">Contact: hr@apexhr.com | Tel: +63 2 8888 1000</div>
        </div>

        <div class="text-center my-4">
            <h2 class="fw-bold text-uppercase" style="letter-spacing: 2px; font-family:sans-serif;">Certificate of Employment</h2>
            <div class="text-muted">Ref No: <code>COE-<?= date('Y') . '-' . str_pad($separation['id'], 4, '0', STR_PAD_LEFT); ?></code></div>
        </div>

        <div class="my-5 lh-lg" style="font-size: 1.15rem; text-align: justify;">
            <p><strong>TO WHOM IT MAY CONCERN:</strong></p>

            <p>This is to certify that <strong>MR./MS. <?= strtoupper(htmlspecialchars($separation['first_name'] . ' ' . $separation['last_name'])); ?></strong> has been officially employed with <strong><?= APP_COMPANY; ?></strong> from <strong><?= date('F d, Y', strtotime($separation['hire_date'])); ?></strong> to <strong><?= date('F d, Y', strtotime($separation['effective_date'])); ?></strong>.</p>

            <p>During their tenure with the organization, they served in the position of <strong><?= htmlspecialchars($separation['position_title']); ?></strong> under the <strong><?= htmlspecialchars($separation['department_name']); ?></strong>, receiving a final basic compensation of <strong>PHP <?= number_format($separation['basic_salary'], 2); ?></strong> per month.</p>

            <p>This certification is being issued upon the request of the above-named employee for whatever legal or professional purposes it may serve best.</p>

            <p>Given this <strong><?= date('jS \o\f F, Y'); ?></strong> at Taguig City, Metro Manila, Philippines.</p>
        </div>

        <div class="row mt-5 pt-5">
            <div class="col-6">
                <div class="border-top border-dark pt-2 w-75">
                    <strong>Alexander Pierce</strong><br>
                    <span class="text-muted small">Chief Human Resource Officer</span><br>
                    <small class="text-muted"><?= APP_COMPANY; ?></small>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="border-top border-dark pt-2 w-75 ms-auto">
                    <strong>Official Corporate Seal</strong><br>
                    <span class="text-muted small">Digitally Verified & Encrypted</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php

require_once ROOT_PATH . 'core/Controller.php';
require_once APP_PATH . 'Models/Compliance.php';
require_once APP_PATH . 'Models/Employee.php';

class ComplianceController extends Controller {
    public function index() {
        Auth::requireAuth();

        $complianceModel = new Compliance();
        $employeeModel = new Employee();

        $data = [
            'contributions' => $complianceModel->getContributionsWithDetails(),
            'deadlines'     => $complianceModel->getUpcomingDeadlines(),
            'employees'     => $employeeModel->getAllWithDetails()
        ];

        $this->view('compliance/index', $data);
    }

    public function calculator() {
        Auth::requireAuth();
        $this->view('compliance/calculator');
    }

    public function bir2316() {
        Auth::requireAuth();
        $complianceModel = new Compliance();
        $employeeModel = new Employee();

        $empId = intval($_GET['employee_id'] ?? 4);
        $year = intval($_GET['year'] ?? 2026);

        $data = [
            'bir_data'  => $complianceModel->getBIR2316Data($empId, $year),
            'employees' => $employeeModel->getAllWithDetails()
        ];

        $this->view('compliance/bir2316', $data);
    }

    public function generateContribution() {
        Auth::requireRole(['Super Admin', 'HR Manager', 'Finance']);
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->json('error', 'Invalid CSRF token.');
        }

        $empId = intval($_POST['employee_id'] ?? 0);
        $gross = floatval($_POST['gross_salary'] ?? 0);

        if (!$empId || $gross <= 0) {
            $this->json('error', 'Please select a valid employee and gross salary amount.');
        }

        // Live Calculate Statutory
        $msc = min(max($gross, 4000), 30000);
        $sssEmp = $msc * 0.045;
        $sssComp = $msc * 0.095;

        $phSalary = min(max($gross, 10000), 100000);
        $phEmp = ($phSalary * 0.05) / 2;
        $phComp = ($phSalary * 0.05) / 2;

        $hdmfEmp = $gross >= 5000 ? 200 : $gross * 0.02;
        $hdmfComp = $gross >= 5000 ? 200 : $gross * 0.02;

        $statutoryEmp = $sssEmp + $phEmp + $hdmfEmp;
        $taxable = max(0, $gross - $statutoryEmp);

        // BIR Tax
        $birTax = 0;
        if ($taxable > 20833 && $taxable <= 33333) $birTax = ($taxable - 20833) * 0.15;
        else if ($taxable > 33333 && $taxable <= 66667) $birTax = 1875 + ($taxable - 33333) * 0.20;
        else if ($taxable > 66667 && $taxable <= 166667) $birTax = 8541.67 + ($taxable - 66667) * 0.25;

        $totalStatutory = $statutoryEmp + $birTax;

        $complianceModel = new Compliance();
        $complianceModel->create([
            'employee_id'         => $empId,
            'period_month'        => date('n'),
            'period_year'         => date('Y'),
            'gross_salary'        => $gross,
            'sss_employee'        => $sssEmp,
            'sss_employer'        => $sssComp,
            'philhealth_employee' => $phEmp,
            'philhealth_employer' => $phComp,
            'pagibig_employee'    => $hdmfEmp,
            'pagibig_employer'    => $hdmfComp,
            'bir_tax_withheld'    => $birTax,
            'total_statutory'     => $totalStatutory
        ]);

        AuditLogger::log('CALCULATE_STATUTORY', 'Government Compliance', "Generated contribution for Employee ID {$empId}");
        $this->json('success', 'Statutory remittance & BIR withholding logged successfully!');
    }
}

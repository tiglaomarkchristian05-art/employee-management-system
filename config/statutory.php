<?php
// Legacy project values, NOT verified official rates. Payroll/client/legal must
// confirm and maintain this configuration before monetary records can be posted.
return [
    'confirmed' => false,
    'source' => null,
    'effective_from' => null,
    'effective_until' => null,
    'sss' => ['floor'=>4000, 'ceiling'=>30000, 'employee_rate'=>0.045, 'employer_rate'=>0.095],
    'philhealth' => ['floor'=>10000, 'ceiling'=>100000, 'rate'=>0.05, 'employee_fraction'=>0.5],
    'pagibig' => ['threshold'=>5000, 'fixed_share'=>200, 'rate'=>0.02],
    'tax_brackets' => [
        ['ceiling'=>20833, 'base'=>0, 'excess_over'=>0, 'rate'=>0],
        ['ceiling'=>33333, 'base'=>0, 'excess_over'=>20833, 'rate'=>0.15],
        ['ceiling'=>66667, 'base'=>1875, 'excess_over'=>33333, 'rate'=>0.20],
        ['ceiling'=>166667, 'base'=>8541.67, 'excess_over'=>66667, 'rate'=>0.25],
    ],
];

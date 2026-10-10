<?php
class StatutoryCalculator {
    private array $rules;
    public function __construct(?array $rules=null){$this->rules=$rules??require ROOT_PATH.'config/statutory.php';}
    public function assertConfirmed(string $period): void {
        $r=$this->rules;
        if(empty($r['confirmed'])||empty($r['source'])||empty($r['effective_from'])||empty($r['effective_until'])||$period<$r['effective_from']||$period>$r['effective_until'])
            throw new RuntimeException('Calculation rule requires current statutory configuration and client/payroll/legal confirmation for this period. No monetary record was posted.');
    }
    public function calculate(float $gross): array {
        if(!is_finite($gross)||$gross<=0)throw new RuntimeException('Enter a positive finite gross salary.');
        $s=$this->rules['sss'];$p=$this->rules['philhealth'];$h=$this->rules['pagibig'];
        $msc=min(max($gross,$s['floor']),$s['ceiling']);
        $sssEmp=$msc*$s['employee_rate'];$sssComp=$msc*$s['employer_rate'];
        $premium=min(max($gross,$p['floor']),$p['ceiling'])*$p['rate'];
        $phEmp=$premium*$p['employee_fraction'];$phComp=$premium-$phEmp;
        $hdmfEmp=$gross>=$h['threshold']?$h['fixed_share']:$gross*$h['rate'];$hdmfComp=$hdmfEmp;
        $statutoryEmp=$sssEmp+$phEmp+$hdmfEmp;$taxable=max(0,$gross-$statutoryEmp);$birTax=null;
        foreach($this->rules['tax_brackets'] as $bracket){if($taxable<=$bracket['ceiling']){$birTax=$bracket['base']+max(0,$taxable-$bracket['excess_over'])*$bracket['rate'];break;}}
        if($birTax===null)throw new RuntimeException('Tax rule requires current configuration: taxable income exceeds the supported configured range. No validated monetary result is available.');
        $totalStatutory=$statutoryEmp+$birTax;$netTakeHome=$gross-$totalStatutory;
        return array_map(static fn($n)=>round($n,2),compact('sssEmp','sssComp','phEmp','phComp','hdmfEmp','hdmfComp','birTax','totalStatutory','netTakeHome'));
    }
}

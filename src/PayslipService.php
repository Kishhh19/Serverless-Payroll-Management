<?php

declare(strict_types=1);

namespace PayrollApi;

class PayslipService
{
    private CsvRepository $repository;

    public function __construct(?CsvRepository $repository = null)
    {
        $this->repository = $repository ?? new CsvRepository();
    }

    /**
     * Retrieve individual payslip data matching the payroll ID.
     *
     * @param int $payrollId
     * @return array<string, mixed>|null
     */
    public function getPayslipData(int $payrollId): ?array
    {
        if ($payrollId <= 0) {
            return null;
        }

        $payroll = $this->repository->findPayrollById($payrollId);
        if ($payroll === null) {
            return null;
        }

        $employeeId = (int)($payroll['employee_id'] ?? 0);
        $employee = $this->repository->findEmployeeById($employeeId);

        $employeeName = $employee !== null ? (string)$employee['full_name'] : 'Unknown Employee';
        $departmentName = null;
        $positionTitle = null;

        if ($employee !== null) {
            $dept = $this->repository->findDepartmentById((int)($employee['department_id'] ?? 0));
            $departmentName = $dept['name'] ?? null;

            $pos = $this->repository->findPositionById((int)($employee['position_id'] ?? 0));
            $positionTitle = $pos['title'] ?? null;
        }

        $gross = is_numeric($payroll['gross_pay'] ?? null) ? (float)$payroll['gross_pay'] : 0.00;
        $net = is_numeric($payroll['net_pay'] ?? null) ? (float)$payroll['net_pay'] : 0.00;

        if (isset($payroll['deductions']) && is_numeric($payroll['deductions'])) {
            $deductions = (float)$payroll['deductions'];
        } else {
            $grossCents = (int)round($gross * 100);
            $netCents = (int)round($net * 100);
            $deductions = round(($grossCents - $netCents) / 100, 2);
        }

        return [
            'payroll_id' => $payrollId,
            'employee_id' => $employeeId,
            'employee_name' => $employeeName,
            'department' => $departmentName,
            'position' => $positionTitle,
            'pay_period_start' => $payroll['pay_period_start'] ?? null,
            'pay_period_end' => $payroll['pay_period_end'] ?? null,
            'gross_pay' => round($gross, 2),
            'deductions' => round($deductions, 2),
            'net_pay' => round($net, 2),
        ];
    }
}

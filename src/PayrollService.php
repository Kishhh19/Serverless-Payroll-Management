<?php

declare(strict_types=1);

namespace PayrollApi;

class PayrollService
{
    private CsvRepository $repository;

    public function __construct(?CsvRepository $repository = null)
    {
        $this->repository = $repository ?? new CsvRepository();
    }

    /**
     * Compute summary metrics across all valid payroll records in the dataset.
     * Uses integer cents arithmetic to avoid floating point precision drift.
     *
     * @return array{
     *     total_payroll_records: int,
     *     total_gross_pay: float,
     *     total_deductions: float,
     *     total_net_pay: float
     * }
     */
    public function getPayrollSummary(): array
    {
        $records = $this->repository->getPayrollRecords();

        $validCount = 0;
        $totalGrossCents = 0;
        $totalNetCents = 0;
        $totalDeductionsCents = 0;

        foreach ($records as $record) {
            // Validate required columns
            if (!isset($record['id']) || !isset($record['gross_pay']) || !isset($record['net_pay'])) {
                continue;
            }

            // Validate numeric values; handle invalid or missing values as 0.00
            $rawGross = trim((string)$record['gross_pay']);
            $rawNet = trim((string)$record['net_pay']);

            if (!is_numeric($rawGross)) {
                $rawGross = '0.00';
            }
            if (!is_numeric($rawNet)) {
                $rawNet = '0.00';
            }

            // Decimal-safe conversion into cents
            $grossCents = (int)round((float)$rawGross * 100);
            $netCents = (int)round((float)$rawNet * 100);

            // Deductions calculated as gross_pay - net_pay if dedicated column is unavailable
            if (isset($record['deductions']) && is_numeric(trim((string)$record['deductions']))) {
                $deductionCents = (int)round((float)trim((string)$record['deductions']) * 100);
            } else {
                $deductionCents = $grossCents - $netCents;
            }

            $totalGrossCents += $grossCents;
            $totalNetCents += $netCents;
            $totalDeductionsCents += $deductionCents;
            $validCount++;
        }

        return [
            'total_payroll_records' => $validCount,
            'total_gross_pay' => round($totalGrossCents / 100, 2),
            'total_deductions' => round($totalDeductionsCents / 100, 2),
            'total_net_pay' => round($totalNetCents / 100, 2),
        ];
    }
}

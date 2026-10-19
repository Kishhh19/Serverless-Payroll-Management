<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PayrollApi\CsvRepository;
use PayrollApi\EmployeeService;
use PayrollApi\PayrollService;
use PayrollApi\PayslipService;
use PayrollApi\PdfGenerator;
use PayrollApi\S3Storage;

class PayrollUnitTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function runAll(): void
    {
        echo "========================================================\n";
        echo "RUNNING UNIT AND INTEGRATION TESTS FOR PAYROLL API\n";
        echo "========================================================\n\n";

        $this->testCsvRepositoryLoading();
        $this->testEmployeeService();
        $this->testPayrollServiceCalculation();
        $this->testPayslipServiceExistingRecord();
        $this->testPayslipServiceNonExistentRecord();
        $this->testPdfGenerator();
        $this->testEmptyAndMalformedCsvHandling();
        $this->testInvalidMonetaryValuesHandling();
        $this->testS3UploadFailureHandling();

        echo "\n--------------------------------------------------------\n";
        echo "Test Summary: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "--------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(string $testName, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            echo "  [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo "  [FAIL] {$testName}: {$detail}\n";
            $this->failed++;
        }
    }

    private function testCsvRepositoryLoading(): void
    {
        echo "[1] Testing CsvRepository:\n";
        $repo = new CsvRepository();

        $employees = $repo->getEmployees();
        $this->assert("Loads 500 employee records", count($employees) === 500, "Expected 500, got " . count($employees));

        $payroll = $repo->getPayrollRecords();
        $this->assert("Loads 600 payroll records", count($payroll) === 600, "Expected 600, got " . count($payroll));

        $departments = $repo->getDepartments();
        $this->assert("Loads 10 department records", count($departments) === 10, "Expected 10, got " . count($departments));

        $positions = $repo->getPositions();
        $this->assert("Loads 25 position records", count($positions) === 25, "Expected 25, got " . count($positions));
    }

    private function testEmployeeService(): void
    {
        echo "\n[2] Testing EmployeeService:\n";
        $service = new EmployeeService();

        $employees = $service->getAllEmployees();
        $this->assert("Employee list is not empty", count($employees) > 0);

        $firstEmp = $employees[0];
        $this->assert("First employee ID is 1", ($firstEmp['employee_id'] ?? 0) === 1);
        $this->assert("Employee full_name is populated", !empty($firstEmp['full_name']));
        $this->assert("Employee department_name is enriched", !empty($firstEmp['department_name']));
        $this->assert("Employee position_title is enriched", !empty($firstEmp['position_title']));

        $singleEmp = $service->getEmployeeById(1);
        $this->assert("Single employee lookup by ID matches", $singleEmp !== null && $singleEmp['id'] === 1);
    }

    private function testPayrollServiceCalculation(): void
    {
        echo "\n[3] Testing PayrollService Calculations:\n";
        $service = new PayrollService();
        $summary = $service->getPayrollSummary();

        $this->assert("Total records is exactly 600", $summary['total_payroll_records'] === 600);
        $this->assert("Total gross pay matches dataset calculation (4,981,004.63)", $summary['total_gross_pay'] === 4981004.63, "Got " . $summary['total_gross_pay']);
        $this->assert("Total deductions matches dataset calculation (849,478.70)", $summary['total_deductions'] === 849478.70, "Got " . $summary['total_deductions']);
        $this->assert("Total net pay matches dataset calculation (4,131,525.93)", $summary['total_net_pay'] === 4131525.93, "Got " . $summary['total_net_pay']);

        // Decimal equality check: gross - deductions == net
        $diff = round($summary['total_gross_pay'] - $summary['total_deductions'] - $summary['total_net_pay'], 2);
        $this->assert("Gross minus Deductions equals Net Pay exactly ($diff = 0)", $diff === 0.0);
    }

    private function testPayslipServiceExistingRecord(): void
    {
        echo "\n[4] Testing PayslipService for existing record (ID #1):\n";
        $service = new PayslipService();
        $payslip = $service->getPayslipData(1);

        $this->assert("Payslip #1 exists", $payslip !== null);
        $this->assert("Payroll ID is 1", ($payslip['payroll_id'] ?? 0) === 1);
        $this->assert("Employee ID is 319", ($payslip['employee_id'] ?? 0) === 319);
        $this->assert("Employee name is populated", !empty($payslip['employee_name']));
        $this->assert("Gross pay is 14131.54", ($payslip['gross_pay'] ?? 0.0) === 14131.54);
        $this->assert("Net pay is 1505.18", ($payslip['net_pay'] ?? 0.0) === 1505.18);
        $this->assert("Deductions is 12626.36", ($payslip['deductions'] ?? 0.0) === 12626.36);
    }

    private function testPayslipServiceNonExistentRecord(): void
    {
        echo "\n[5] Testing PayslipService for non-existent record (ID #99999):\n";
        $service = new PayslipService();
        $payslip = $service->getPayslipData(99999);

        $this->assert("Payslip #99999 returns null", $payslip === null);

        $negativePayslip = $service->getPayslipData(-5);
        $this->assert("Negative ID returns null", $negativePayslip === null);
    }

    private function testPdfGenerator(): void
    {
        echo "\n[6] Testing PdfGenerator with Dompdf:\n";
        $payslipService = new PayslipService();
        $payslip = $payslipService->getPayslipData(1);

        $pdfGenerator = new PdfGenerator();
        $pdfOutput = $pdfGenerator->generate($payslip);

        $this->assert("PDF binary is generated", !empty($pdfOutput));
        $this->assert("PDF has valid magic header (%PDF-)", str_starts_with($pdfOutput, '%PDF-'));
        $this->assert("PDF length is substantial (> 5KB)", strlen($pdfOutput) > 5000, "Length: " . strlen($pdfOutput));
    }

    private function testEmptyAndMalformedCsvHandling(): void
    {
        echo "\n[7] Testing Empty and Malformed CSV Handling:\n";
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payroll_test_' . uniqid();
        mkdir($tempDir);

        // Test 1: Empty file
        file_put_contents($tempDir . DIRECTORY_SEPARATOR . 'empty.csv', "");
        $repo = new CsvRepository($tempDir);
        $emptyResult = $repo->readCsv('empty.csv');
        $this->assert("Empty CSV returns empty array", empty($emptyResult));

        // Test 2: Malformed row with missing columns
        file_put_contents(
            $tempDir . DIRECTORY_SEPARATOR . 'malformed.csv',
            "id,name,value\n1,Alpha\n2,Beta,100,ExtraCol\n"
        );
        $malformedResult = $repo->readCsv('malformed.csv');
        $this->assert("Malformed CSV normalizes column count", count($malformedResult) === 2);
        $this->assert("Underfilled row pads with empty string", isset($malformedResult[0]['value']) && $malformedResult[0]['value'] === '');
        $this->assert("Overfilled row slices to header length", !isset($malformedResult[1][3]));

        // Cleanup
        unlink($tempDir . DIRECTORY_SEPARATOR . 'empty.csv');
        unlink($tempDir . DIRECTORY_SEPARATOR . 'malformed.csv');
        rmdir($tempDir);
    }

    private function testInvalidMonetaryValuesHandling(): void
    {
        echo "\n[8] Testing Invalid Monetary Values Handling in PayrollService:\n";
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'payroll_money_test_' . uniqid();
        mkdir($tempDir);

        file_put_contents(
            $tempDir . DIRECTORY_SEPARATOR . 'payroll.csv',
            "id,employee_id,pay_period_start,pay_period_end,gross_pay,net_pay\n" .
            "1,10,2023-01-01,2023-01-31,1000.00,800.00\n" .
            "2,11,2023-01-01,2023-01-31,invalid,N/A\n" .
            "3,12,2023-01-01,2023-01-31,500.50,\n"
        );

        $repo = new CsvRepository($tempDir);
        $service = new PayrollService($repo);
        $summary = $service->getPayrollSummary();

        $this->assert("Processes rows with invalid numbers gracefully", $summary['total_payroll_records'] === 3);
        $this->assert("Calculates total gross including decimal safety (1500.50)", $summary['total_gross_pay'] === 1500.50);
        $this->assert("Calculates total net (800.00)", $summary['total_net_pay'] === 800.00);

        unlink($tempDir . DIRECTORY_SEPARATOR . 'payroll.csv');
        rmdir($tempDir);
    }

    private function testS3UploadFailureHandling(): void
    {
        echo "\n[9] Testing S3 Storage Failure Handling (safe mock):\n";
        // Instantiate without bucket configured
        $s3 = new S3Storage(null, '');
        $exceptionCaught = false;

        try {
            $s3->uploadPayslip(1, "%PDF-mock-content");
        } catch (RuntimeException $e) {
            $exceptionCaught = true;
        }

        $this->assert("Throws RuntimeException when S3 bucket is unconfigured", $exceptionCaught);

        // Test invalid negative ID
        $s3Configured = new S3Storage(null, 'test-bucket');
        $idExceptionCaught = false;
        try {
            $s3Configured->uploadPayslip(-1, "%PDF-mock-content");
        } catch (RuntimeException $e) {
            $idExceptionCaught = true;
        }

        $this->assert("Throws RuntimeException for negative payroll ID", $idExceptionCaught);
    }
}

$testRunner = new PayrollUnitTest();
$testRunner->runAll();

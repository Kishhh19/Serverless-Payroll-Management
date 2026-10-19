<?php

declare(strict_types=1);

namespace PayrollApi;

use Bramus\Router\Router as BramusRouter;
use Throwable;

class Router
{
    private BramusRouter $router;
    private EmployeeService $employeeService;
    private PayrollService $payrollService;
    private PayslipService $payslipService;
    private PdfGenerator $pdfGenerator;
    private S3Storage $s3Storage;

    public function __construct(
        ?EmployeeService $employeeService = null,
        ?PayrollService $payrollService = null,
        ?PayslipService $payslipService = null,
        ?PdfGenerator $pdfGenerator = null,
        ?S3Storage $s3Storage = null
    ) {
        $this->router = new BramusRouter();
        $this->employeeService = $employeeService ?? new EmployeeService();
        $this->payrollService = $payrollService ?? new PayrollService();
        $this->payslipService = $payslipService ?? new PayslipService();
        $this->pdfGenerator = $pdfGenerator ?? new PdfGenerator();
        $this->s3Storage = $s3Storage ?? new S3Storage();

        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        // Root / info route
        $this->router->get('/', function () {
            Response::success([
                'service' => 'Serverless Payroll Management API',
                'runtime' => 'PHP ' . PHP_VERSION . ' on AWS Lambda (Bref)',
                'endpoints' => [
                    'GET /api/employees' => 'List all employee records from CSV',
                    'GET /api/reports/payroll' => 'Calculated payroll summary report (gross, deductions, net)',
                    'GET /api/payslips/{payroll_id}' => 'Individual employee payslip by payroll ID',
                    'POST /api/payslips/{payroll_id}/generate' => 'Generate payslip PDF and upload to AWS S3',
                ],
            ], 200, 'Serverless Payroll API is healthy.');
        });

        // -------------------------------------------------------------
        // Endpoint A: List Employees
        // Method: GET /api/employees
        // -------------------------------------------------------------
        $this->router->get('/api/employees', function () {
            try {
                $employees = $this->employeeService->getAllEmployees();
                Response::success($employees, 200);
            } catch (Throwable $e) {
                error_log("Error in /api/employees: " . $e->getMessage());
                Response::error("Failed to retrieve employee records.", 500);
            }
        });

        $this->router->match('POST|PUT|DELETE|PATCH', '/api/employees', function () {
            Response::error("Method Not Allowed. Supported method: GET.", 405);
        });

        // -------------------------------------------------------------
        // Endpoint B: Payroll Summary Report
        // Method: GET /api/reports/payroll
        // -------------------------------------------------------------
        $this->router->get('/api/reports/payroll', function () {
            try {
                $summary = $this->payrollService->getPayrollSummary();
                Response::success($summary, 200);
            } catch (Throwable $e) {
                error_log("Error in /api/reports/payroll: " . $e->getMessage());
                Response::error("Failed to generate payroll summary report.", 500);
            }
        });

        $this->router->match('POST|PUT|DELETE|PATCH', '/api/reports/payroll', function () {
            Response::error("Method Not Allowed. Supported method: GET.", 405);
        });

        // -------------------------------------------------------------
        // Endpoint D: Generate and Upload PDF Payslip (matched before C)
        // Method: POST /api/payslips/{payroll_id}/generate
        // -------------------------------------------------------------
        $this->router->post('/api/payslips/([^/]+)/generate', function ($id) {
            if (!ctype_digit((string)$id) || (int)$id <= 0) {
                Response::error("Invalid payroll identifier: '{$id}'. Must be a positive integer.", 400);
                return;
            }

            $payrollId = (int)$id;

            try {
                // 1. Retrieve payslip data
                $payslipData = $this->payslipService->getPayslipData($payrollId);
                if ($payslipData === null) {
                    Response::error("Payroll record #{$payrollId} not found.", 404);
                    return;
                }

                // 2. Generate PDF in memory
                $pdfBinary = $this->pdfGenerator->generate($payslipData);

                // 3. Upload to AWS S3
                $uploadResult = $this->s3Storage->uploadPayslip($payrollId, $pdfBinary);

                Response::success($uploadResult, 200, "Payslip generated and uploaded.");
            } catch (Throwable $e) {
                error_log("Error generating/uploading payslip #{$payrollId}: " . $e->getMessage());
                Response::error("Failed to generate or upload payslip: " . $e->getMessage(), 500);
            }
        });

        $this->router->match('GET|PUT|DELETE|PATCH', '/api/payslips/([^/]+)/generate', function ($id) {
            Response::error("Method Not Allowed. Supported method: POST.", 405);
        });

        // -------------------------------------------------------------
        // Endpoint C: Individual Payslip
        // Method: GET /api/payslips/{payroll_id}
        // -------------------------------------------------------------
        $this->router->get('/api/payslips/([^/]+)', function ($id) {
            if (!ctype_digit((string)$id) || (int)$id <= 0) {
                Response::error("Invalid payroll identifier: '{$id}'. Must be a positive integer.", 400);
                return;
            }

            $payrollId = (int)$id;

            try {
                $payslip = $this->payslipService->getPayslipData($payrollId);
                if ($payslip === null) {
                    Response::error("Payroll record #{$payrollId} not found.", 404);
                    return;
                }

                Response::success($payslip, 200);
            } catch (Throwable $e) {
                error_log("Error retrieving payslip #{$payrollId}: " . $e->getMessage());
                Response::error("Failed to retrieve payslip details.", 500);
            }
        });

        $this->router->match('POST|PUT|DELETE|PATCH', '/api/payslips/([^/]+)', function ($id) {
            Response::error("Method Not Allowed. Supported method: GET.", 405);
        });

        // -------------------------------------------------------------
        // 404 Handler for Unknown Routes
        // -------------------------------------------------------------
        $this->router->set404(function () {
            Response::error("The requested endpoint does not exist.", 404);
        });
    }

    /**
     * Dispatch the request.
     */
    public function dispatch(): void
    {
        try {
            $this->router->run();
        } catch (Throwable $e) {
            error_log("Router Dispatch Exception: " . $e->getMessage());
            Response::error("Internal server error.", 500);
        }
    }
}

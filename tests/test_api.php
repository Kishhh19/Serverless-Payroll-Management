<?php

declare(strict_types=1);

class ApiIntegrationTest
{
    private string $baseUrl;
    private $serverProcess = null;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct(string $baseUrl = 'http://127.0.0.1:8888')
    {
        $this->baseUrl = $baseUrl;
    }

    public function runAll(): void
    {
        echo "========================================================\n";
        echo "RUNNING FULL HTTP ENDPOINT INTEGRATION TESTS\n";
        echo "========================================================\n\n";

        $this->startLocalServer();

        try {
            $this->testRootHealth();
            $this->testEndpointAEmployees();
            $this->testEndpointBPayrollSummary();
            $this->testEndpointCExistingPayslip();
            $this->testEndpointCNonExistentPayslip();
            $this->testEndpointCInvalidId();
            $this->testEndpointDNonExistentGenerate();
            $this->testEndpointDMethodNotAllowed();
            $this->testEndpointAMethodNotAllowed();
            $this->testEndpointBMethodNotAllowed();
            $this->testUnknownRouteNotFound();
        } finally {
            $this->stopLocalServer();
        }

        echo "\n--------------------------------------------------------\n";
        echo "API Integration Summary: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "--------------------------------------------------------\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function startLocalServer(): void
    {
        $docRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public';
        $cmd = "php -S 127.0.0.1:8888 -t " . escapeshellarg($docRoot);
        echo "Starting local PHP server on 127.0.0.1:8888...\n";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $this->serverProcess = proc_open($cmd, $descriptors, $pipes, dirname(__DIR__));
        // Give server a moment to bind socket
        usleep(800000);
    }

    private function stopLocalServer(): void
    {
        if (is_resource($this->serverProcess)) {
            echo "\nStopping local PHP test server...\n";
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }
    }

    /**
     * @return array{status: int, headers: array<string, string>, body: string, json: mixed}
     */
    private function request(string $method, string $path, ?string $body = null): array
    {
        $url = $this->baseUrl . $path;
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => 5,
            ]
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        $status = 0;
        $responseHeaders = [];

        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $headerLine) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $headerLine, $matches)) {
                    $status = (int)$matches[1];
                } elseif (strpos($headerLine, ':') !== false) {
                    [$name, $val] = explode(':', $headerLine, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($val);
                }
            }
        }

        $json = json_decode((string)$responseBody, true);

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string)$responseBody,
            'json' => $json,
        ];
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

    private function testRootHealth(): void
    {
        echo "[1] Testing GET / (Root Health Info):\n";
        $res = $this->request('GET', '/');
        $this->assert("Status is 200 OK", $res['status'] === 200, "Got " . $res['status']);
        $this->assert("Content-Type is application/json", str_contains($res['headers']['content-type'] ?? '', 'application/json'));
        $this->assert("Response reports success", ($res['json']['success'] ?? false) === true);
    }

    private function testEndpointAEmployees(): void
    {
        echo "\n[2] Testing Endpoint A: GET /api/employees:\n";
        $res = $this->request('GET', '/api/employees');
        $this->assert("Status is 200 OK", $res['status'] === 200, "Got " . $res['status']);
        $this->assert("Content-Type is application/json", str_contains($res['headers']['content-type'] ?? '', 'application/json'));
        $this->assert("Returns 500 employees array", is_array($res['json']['data']) && count($res['json']['data']) === 500);

        $firstEmp = $res['json']['data'][0] ?? [];
        $this->assert("First employee has employee_id, full_name, department_id",
            isset($firstEmp['employee_id']) && isset($firstEmp['full_name']) && isset($firstEmp['department_id'])
        );
    }

    private function testEndpointBPayrollSummary(): void
    {
        echo "\n[3] Testing Endpoint B: GET /api/reports/payroll:\n";
        $res = $this->request('GET', '/api/reports/payroll');
        $this->assert("Status is 200 OK", $res['status'] === 200, "Got " . $res['status']);
        $this->assert("Content-Type is application/json", str_contains($res['headers']['content-type'] ?? '', 'application/json'));

        $data = $res['json']['data'] ?? [];
        $this->assert("total_payroll_records is 600", ($data['total_payroll_records'] ?? 0) === 600);
        $this->assert("total_gross_pay is 4981004.63", ($data['total_gross_pay'] ?? 0.0) === 4981004.63);
        $this->assert("total_deductions is 849478.70", ($data['total_deductions'] ?? 0.0) === 849478.70);
        $this->assert("total_net_pay is 4131525.93", ($data['total_net_pay'] ?? 0.0) === 4131525.93);
    }

    private function testEndpointCExistingPayslip(): void
    {
        echo "\n[4] Testing Endpoint C: GET /api/payslips/1 (Existing ID):\n";
        $res = $this->request('GET', '/api/payslips/1');
        $this->assert("Status is 200 OK", $res['status'] === 200, "Got " . $res['status']);
        $data = $res['json']['data'] ?? [];
        $this->assert("payroll_id is 1", ($data['payroll_id'] ?? 0) === 1);
        $this->assert("employee_name is present", !empty($data['employee_name']));
        $this->assert("gross_pay is 14131.54", ($data['gross_pay'] ?? 0.0) === 14131.54);
        $this->assert("deductions is 12626.36", ($data['deductions'] ?? 0.0) === 12626.36);
        $this->assert("net_pay is 1505.18", ($data['net_pay'] ?? 0.0) === 1505.18);
    }

    private function testEndpointCNonExistentPayslip(): void
    {
        echo "\n[5] Testing Endpoint C: GET /api/payslips/99999 (Non-existent ID):\n";
        $res = $this->request('GET', '/api/payslips/99999');
        $this->assert("Status is 404 Not Found", $res['status'] === 404, "Got " . $res['status']);
        $this->assert("success is false", ($res['json']['success'] ?? true) === false);
        $this->assert("Error message mentions not found", str_contains($res['json']['error'] ?? '', 'not found'));
    }

    private function testEndpointCInvalidId(): void
    {
        echo "\n[6] Testing Endpoint C: GET /api/payslips/invalid_id (Invalid param):\n";
        $res = $this->request('GET', '/api/payslips/invalid_id');
        $this->assert("Status is 400 Bad Request", $res['status'] === 400, "Got " . $res['status']);
        $this->assert("Error message specifies positive integer", str_contains($res['json']['error'] ?? '', 'positive integer'));
    }

    private function testEndpointDNonExistentGenerate(): void
    {
        echo "\n[7] Testing Endpoint D: POST /api/payslips/99999/generate (Non-existent):\n";
        $res = $this->request('POST', '/api/payslips/99999/generate');
        $this->assert("Status is 404 Not Found", $res['status'] === 404, "Got " . $res['status']);
        $this->assert("success is false", ($res['json']['success'] ?? true) === false);
    }

    private function testEndpointDMethodNotAllowed(): void
    {
        echo "\n[8] Testing Endpoint D: GET /api/payslips/1/generate (Method Not Allowed):\n";
        $res = $this->request('GET', '/api/payslips/1/generate');
        $this->assert("Status is 405 Method Not Allowed", $res['status'] === 405, "Got " . $res['status']);
        $this->assert("Error specifies POST supported", str_contains($res['json']['error'] ?? '', 'POST'));
    }

    private function testEndpointAMethodNotAllowed(): void
    {
        echo "\n[9] Testing Endpoint A: POST /api/employees (Method Not Allowed):\n";
        $res = $this->request('POST', '/api/employees');
        $this->assert("Status is 405 Method Not Allowed", $res['status'] === 405, "Got " . $res['status']);
        $this->assert("Error specifies GET supported", str_contains($res['json']['error'] ?? '', 'GET'));
    }

    private function testEndpointBMethodNotAllowed(): void
    {
        echo "\n[10] Testing Endpoint B: POST /api/reports/payroll (Method Not Allowed):\n";
        $res = $this->request('POST', '/api/reports/payroll');
        $this->assert("Status is 405 Method Not Allowed", $res['status'] === 405, "Got " . $res['status']);
        $this->assert("Error specifies GET supported", str_contains($res['json']['error'] ?? '', 'GET'));
    }

    private function testUnknownRouteNotFound(): void
    {
        echo "\n[11] Testing Unknown Route: GET /api/unknown_route:\n";
        $res = $this->request('GET', '/api/unknown_route');
        $this->assert("Status is 404 Not Found", $res['status'] === 404, "Got " . $res['status']);
        $this->assert("success is false", ($res['json']['success'] ?? true) === false);
    }
}

$apiTest = new ApiIntegrationTest();
$apiTest->runAll();

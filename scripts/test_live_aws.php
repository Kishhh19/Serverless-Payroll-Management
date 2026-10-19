<?php

$baseUrl = 'https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com';

echo "========================================================\n";
echo "TESTING LIVE AWS DEPLOYED ENDPOINTS\n";
echo "Base URL: {$baseUrl}\n";
echo "========================================================\n\n";

function request($url, $method = 'GET') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code' => $httpCode,
        'headers' => trim($header),
        'body' => $body,
        'json' => json_decode($body, true),
    ];
}

// 1. Root
echo "[1] GET /\n";
$r1 = request($baseUrl . '/');
echo "Status: {$r1['code']}\n";
echo "Body:\n" . substr($r1['body'], 0, 300) . "\n\n";

// 2. Endpoint A: Employees
echo "[2] GET /api/employees\n";
$r2 = request($baseUrl . '/api/employees');
echo "Status: {$r2['code']}\n";
$empCount = isset($r2['json']['data']) && is_array($r2['json']['data']) ? count($r2['json']['data']) : 0;
echo "Employees Count: {$empCount}\n";
if ($empCount > 0) {
    echo "Sample Employee:\n" . json_encode($r2['json']['data'][0], JSON_PRETTY_PRINT) . "\n";
}
echo "\n";

// 3. Endpoint B: Payroll Report
echo "[3] GET /api/reports/payroll\n";
$r3 = request($baseUrl . '/api/reports/payroll');
echo "Status: {$r3['code']}\n";
echo "Summary Report:\n" . json_encode($r3['json'], JSON_PRETTY_PRINT) . "\n\n";

// 4. Endpoint C: Payslip 1
echo "[4] GET /api/payslips/1\n";
$r4 = request($baseUrl . '/api/payslips/1');
echo "Status: {$r4['code']}\n";
echo "Payslip 1 Data:\n" . json_encode($r4['json'], JSON_PRETTY_PRINT) . "\n\n";

// 5. Endpoint D: POST /api/payslips/1/generate
echo "[5] POST /api/payslips/1/generate\n";
$r5 = request($baseUrl . '/api/payslips/1/generate', 'POST');
echo "Status: {$r5['code']}\n";
echo "Generation Response:\n" . json_encode($r5['json'], JSON_PRETTY_PRINT) . "\n\n";

// 6. Endpoint C: 404 Non-existent
echo "[6] GET /api/payslips/99999\n";
$r6 = request($baseUrl . '/api/payslips/99999');
echo "Status: {$r6['code']}\n";
echo "Response: {$r6['body']}\n\n";

// 7. Method Not Allowed: POST /api/employees
echo "[7] POST /api/employees\n";
$r7 = request($baseUrl . '/api/employees', 'POST');
echo "Status: {$r7['code']}\n";
echo "Response: {$r7['body']}\n\n";

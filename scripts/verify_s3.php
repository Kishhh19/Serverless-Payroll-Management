<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

$s3 = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-southeast-1',
]);

$bucket = 'payroll-payslips-408852558600-dev';
echo "Checking S3 Bucket: {$bucket}\n";

$objects = $s3->listObjectsV2([
    'Bucket' => $bucket,
    'Prefix' => 'payslips/',
]);

if (isset($objects['Contents'])) {
    foreach ($objects['Contents'] as $obj) {
        echo "Found S3 Object: {$obj['Key']}\n";
        echo "Size: {$obj['Size']} bytes\n";
        echo "Last Modified: {$obj['LastModified']}\n";
    }
} else {
    echo "No objects found under prefix payslips/\n";
}

// Download the uploaded object to verify it's a real valid PDF
$result = $s3->getObject([
    'Bucket' => $bucket,
    'Key'    => 'payslips/1.pdf',
]);

$body = (string)$result['Body'];
$localDest = __DIR__ . '/../screenshots/s3_downloaded_payslip_1.pdf';
file_put_contents($localDest, $body);

echo "Downloaded real S3 PDF to {$localDest} (Size: " . strlen($body) . " bytes)\n";
echo "Header: " . substr($body, 0, 8) . "\n";

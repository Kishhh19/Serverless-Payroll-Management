<?php

declare(strict_types=1);

namespace PayrollApi;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use RuntimeException;

class S3Storage
{
    private S3Client $s3Client;
    private string $bucket;
    private string $region;
    private string $prefix;

    public function __construct(?S3Client $s3Client = null, ?string $bucket = null, ?string $region = null, ?string $prefix = null)
    {
        $this->region = $region ?? (getenv('AWS_REGION') ?: (getenv('AWS_DEFAULT_REGION') ?: 'ap-southeast-1'));
        $this->bucket = $bucket ?? (getenv('PAYROLL_S3_BUCKET') ?: '');
        $this->prefix = $prefix ?? (getenv('PAYSLIP_PREFIX') ?: 'payslips/');

        if ($s3Client !== null) {
            $this->s3Client = $s3Client;
        } else {
            // Uses AWS default credential provider chain (IAM execution role on Lambda)
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region'  => $this->region,
            ]);
        }
    }

    /**
     * Upload a generated PDF payslip to the configured S3 bucket.
     *
     * @param int $payrollId
     * @param string $pdfContent
     * @return array{
     *     payroll_id: int,
     *     s3_bucket: string,
     *     s3_object_key: string,
     *     s3_uri: string,
     *     payslip_file: string
     * }
     */
    public function uploadPayslip(int $payrollId, string $pdfContent): array
    {
        if ($payrollId <= 0) {
            throw new RuntimeException("Invalid payroll ID: {$payrollId}");
        }

        if (empty($this->bucket)) {
            throw new RuntimeException("PAYROLL_S3_BUCKET is not configured in the environment.");
        }

        // Sanitized key preventing directory traversal
        $cleanPrefix = trim($this->prefix, '/');
        $key = ($cleanPrefix !== '' ? $cleanPrefix . '/' : '') . $payrollId . '.pdf';

        try {
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $pdfContent,
                'ContentType' => 'application/pdf',
                'ServerSideEncryption' => 'AES256',
            ]);

            // Construct safe S3 object reference and resource URI
            $s3Uri = "s3://{$this->bucket}/{$key}";
            $objectUrl = (string)($result['ObjectURL'] ?? "https://{$this->bucket}.s3.{$this->region}.amazonaws.com/{$key}");

            return [
                'payroll_id' => $payrollId,
                's3_bucket' => $this->bucket,
                's3_object_key' => $key,
                's3_uri' => $s3Uri,
                'payslip_file' => $s3Uri, // S3 object reference without making bucket public
            ];
        } catch (AwsException $e) {
            error_log("AWS S3 PutObject Error: " . $e->getAwsErrorMessage());
            throw new RuntimeException("S3 upload failed: " . $e->getAwsErrorMessage(), (int)$e->getCode(), $e);
        } catch (\Throwable $e) {
            error_log("S3 Storage Upload Error: " . $e->getMessage());
            throw new RuntimeException("S3 upload failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Generate a short-lived presigned download URL for an uploaded payslip.
     *
     * @param string $key
     * @param string $expires Expiration time string (e.g. '+15 minutes')
     * @return string
     */
    public function getPresignedUrl(string $key, string $expires = '+15 minutes'): string
    {
        $cmd = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        $request = $this->s3Client->createPresignedRequest($cmd, $expires);
        return (string)$request->getUri();
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getRegion(): string
    {
        return $this->region;
    }
}

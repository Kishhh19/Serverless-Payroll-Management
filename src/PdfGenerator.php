<?php

declare(strict_types=1);

namespace PayrollApi;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class PdfGenerator
{
    private string $templatePath;

    public function __construct(?string $templatePath = null)
    {
        $this->templatePath = $templatePath ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'payslip.php');
    }

    /**
     * Generate PDF binary content for the given payslip data.
     *
     * @param array<string, mixed> $payslip
     * @return string Raw PDF binary string
     */
    public function generate(array $payslip): string
    {
        if (!file_exists($this->templatePath)) {
            throw new RuntimeException("Payslip template not found at {$this->templatePath}");
        }

        // Render HTML template via output buffering
        ob_start();
        include $this->templatePath;
        $html = ob_get_clean();

        if ($html === false || empty($html)) {
            throw new RuntimeException("Failed to render payslip HTML template");
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        
        // Ensure temporary and font caches write to Lambda-compatible writable directory (/tmp)
        $tempDir = sys_get_temp_dir();
        if (is_writable($tempDir)) {
            $options->set('tempDir', $tempDir);
            $options->set('fontDir', $tempDir);
            $options->set('fontCache', $tempDir);
        }

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        $pdfOutput = $dompdf->output();
        if (empty($pdfOutput)) {
            throw new RuntimeException("Dompdf generated empty PDF output");
        }

        return $pdfOutput;
    }
}

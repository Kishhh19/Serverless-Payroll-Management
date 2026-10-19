<?php
/**
 * @var array<string, mixed> $payslip
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip #<?= htmlspecialchars((string)($payslip['payroll_id'] ?? '')) ?></title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-top: 4px;
        }
        .badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .details-table td {
            padding: 6px 8px;
            vertical-align: top;
        }
        .label {
            color: #64748b;
            font-weight: 500;
            width: 25%;
        }
        .value {
            color: #0f172a;
            font-weight: 600;
            width: 25%;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 24px;
        }
        .financial-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
            text-align: left;
            padding: 8px 12px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .financial-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #f8fafc;
            font-weight: bold;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #cbd5e1;
        }
        .net-pay-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 16px;
            text-align: right;
            margin-top: 16px;
        }
        .net-pay-label {
            font-size: 13px;
            font-weight: 600;
            color: #1e40af;
            text-transform: uppercase;
        }
        .net-pay-amount {
            font-size: 24px;
            font-weight: bold;
            color: #1d4ed8;
            margin-top: 4px;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-title">Serverless Payroll API</div>
                    <div class="header-subtitle">Official Statement of Earnings and Deductions</div>
                </td>
                <td class="text-right">
                    <span class="badge">CONFIDENTIAL</span>
                    <div style="margin-top: 6px; font-size: 12px; color: #64748b;">
                        Generated: <?= date('Y-m-d H:i:s') ?> UTC
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Employee &amp; Payroll Information</div>
    <table class="details-table">
        <tr>
            <td class="label">Payroll ID:</td>
            <td class="value">#<?= htmlspecialchars((string)($payslip['payroll_id'] ?? '')) ?></td>
            <td class="label">Pay Period Start:</td>
            <td class="value"><?= htmlspecialchars((string)($payslip['pay_period_start'] ?? 'N/A')) ?></td>
        </tr>
        <tr>
            <td class="label">Employee ID:</td>
            <td class="value">#<?= htmlspecialchars((string)($payslip['employee_id'] ?? '')) ?></td>
            <td class="label">Pay Period End:</td>
            <td class="value"><?= htmlspecialchars((string)($payslip['pay_period_end'] ?? 'N/A')) ?></td>
        </tr>
        <tr>
            <td class="label">Full Name:</td>
            <td class="value"><?= htmlspecialchars((string)($payslip['employee_name'] ?? '')) ?></td>
            <td class="label">Department:</td>
            <td class="value"><?= htmlspecialchars((string)($payslip['department'] ?? 'General')) ?></td>
        </tr>
        <tr>
            <td class="label">Position:</td>
            <td class="value"><?= htmlspecialchars((string)($payslip['position'] ?? 'Staff')) ?></td>
            <td class="label">Payment Method:</td>
            <td class="value">Direct Deposit</td>
        </tr>
    </table>

    <div class="section-title">Earnings &amp; Deductions Breakdown</div>
    <table class="financial-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Earnings ($)</th>
                <th class="text-right">Deductions ($)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gross Regular Pay</td>
                <td class="text-right"><?= number_format((float)($payslip['gross_pay'] ?? 0), 2) ?></td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Statutory &amp; Voluntary Deductions</td>
                <td class="text-right">-</td>
                <td class="text-right"><?= number_format((float)($payslip['deductions'] ?? 0), 2) ?></td>
            </tr>
            <tr class="total-row">
                <td>Subtotals</td>
                <td class="text-right"><?= number_format((float)($payslip['gross_pay'] ?? 0), 2) ?></td>
                <td class="text-right"><?= number_format((float)($payslip['deductions'] ?? 0), 2) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="net-pay-box">
        <div class="net-pay-label">Total Net Pay Disbursed</div>
        <div class="net-pay-amount">$<?= number_format((float)($payslip['net_pay'] ?? 0), 2) ?></div>
    </div>

    <div class="footer">
        This is an automated serverless payslip document generated by AWS Lambda and Dompdf.<br>
        For inquiries regarding this payslip, please contact the Payroll and HR Operations Department.
    </div>
</body>
</html>

# Academic Project Submission Report

## 1. Project Information

- **Project Title:** Serverless Payroll Management API Using PHP and AWS Lambda
- **Technologies Used:** PHP 8.2 (Bref FastCGI Process Manager), AWS Lambda, Amazon API Gateway (HTTP API v2), Amazon S3 (AES-256 Encrypted), Dompdf 3.1, Bramus Router 1.6, Composer 2.9, Serverless Framework 3.40.
- **Student / Author:** Mark Laurence Santos (`santos.marklaurence@student.auf.edu.ph`)
- **GitHub Repository URL:** https://github.com/Kishhh19/Serverless-Payroll-Management.git
- **Deployed API Base URL:** `https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com`
- **AWS Account ID:** `408852558600`
- **AWS Region:** `ap-southeast-1` (Asia Pacific - Singapore)
- **S3 Bucket Name:** `payroll-payslips-408852558600-dev`
- **Date of Verification:** October 19, 2026

---

## 2. Live Deployed Endpoint Results & Verification

All four required API endpoints were deployed to AWS Lambda, routed via Amazon API Gateway, and verified with live HTTP requests:

### Endpoint A: List Employees
- **HTTP Method & Path:** `GET /api/employees`
- **Live AWS Request:**
  ```bash
  curl -i "https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com/api/employees"
  ```
- **Actual HTTP Status:** `200 OK`
- **Live Test Outcome:** Successfully returned all 500 employee records formatted as JSON from `data/employees.csv`, preserving actual dataset fields (`employee_id`, `id`, `full_name`, `email`, `position_id`, `department_id`, `manager_id`) and enriched with department names and position titles.
- **Screenshot Filename:** `screenshots/01_employee_endpoint.png`

---

### Endpoint B: Payroll Summary Report
- **HTTP Method & Path:** `GET /api/reports/payroll`
- **Live AWS Request:**
  ```bash
  curl -i "https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com/api/reports/payroll"
  ```
- **Actual HTTP Status:** `200 OK`
- **Live Test Outcome:** Accurately computed aggregate financial totals across all 600 payroll records using decimal-safe integer cents arithmetic:
  - `total_payroll_records`: `600`
  - `total_gross_pay`: `$4,981,004.63`
  - `total_deductions`: `$849,478.70`
  - `total_net_pay`: `$4,131,525.93`
  - Accounting Balance: `$4,981,004.63 - $849,478.70 = $4,131,525.93` (Exact cent match).
- **Screenshot Filename:** `screenshots/02_payroll_summary_report.png`

---

### Endpoint C: Individual Payslip
- **HTTP Method & Path:** `GET /api/payslips/{payroll_id}`
- **Live AWS Request (Existing Record):**
  ```bash
  curl -i "https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com/api/payslips/1"
  ```
- **Actual HTTP Status:** `200 OK`
- **Live Test Outcome:** Correctly retrieved payroll ID `1` for employee `319` ("Deniz Fischer"), returning:
  - `gross_pay`: `$14,131.54`
  - `deductions`: `$12,626.36`
  - `net_pay`: `$1,505.18`
- **Live AWS Request (Non-Existent Record):**
  ```bash
  curl -i "https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com/api/payslips/99999"
  ```
- **Actual HTTP Status:** `404 Not Found` (Returned `{ "success": false, "error": "Payroll record #99999 not found.", "status_code": 404 }`).
- **Screenshot Filename:** `screenshots/03_individual_payslip.png`

---

### Endpoint D: Generate and Upload PDF Payslip
- **HTTP Method & Path:** `POST /api/payslips/{payroll_id}/generate`
- **Live AWS Request:**
  ```bash
  curl -i -X POST "https://ky6bt5wzbk.execute-api.ap-southeast-1.amazonaws.com/api/payslips/1/generate"
  ```
- **Actual HTTP Status:** `200 OK`
- **Live Test Outcome:** Successfully generated a PDF in memory on AWS Lambda using Dompdf, uploaded it via AWS SDK for PHP to Amazon S3 under key `payslips/1.pdf`, and returned:
  ```json
  {
      "success": true,
      "message": "Payslip generated and uploaded.",
      "data": {
          "payroll_id": 1,
          "s3_bucket": "payroll-payslips-408852558600-dev",
          "s3_object_key": "payslips/1.pdf",
          "s3_uri": "s3://payroll-payslips-408852558600-dev/payslips/1.pdf",
          "payslip_file": "s3://payroll-payslips-408852558600-dev/payslips/1.pdf"
      }
  }
  ```
- **S3 Object Verification:** Verified object `payslips/1.pdf` (size: 5,297 bytes, `%PDF-1.7`) in bucket `payroll-payslips-408852558600-dev`.
- **Screenshot Filename:** `screenshots/04_pdf_generation_upload.png`
- **Generated PDF Preview:** `screenshots/05_generated_pdf_preview.png`
- **AWS S3 Console Screenshot:** `screenshots/06_aws_s3_bucket.png`
- **AWS Lambda Console Screenshot:** `screenshots/07_aws_lambda_gateway.png`

---

## 3. Real Issues Encountered and Resolutions

### Issue 1: Dompdf Temporary and Font Storage in AWS Lambda Read-Only Filesystem
- **Error Encountered:** In AWS Lambda execution environments, all filesystem paths except `/tmp` are strictly read-only. Standard Dompdf installations attempt to write temporary font metric caches to `vendor/dompdf/dompdf/lib/fonts/` or the document root, causing fatal write permission errors during PDF rendering.
- **Root Cause:** Dompdf default configuration assumes a persistent, writable local filesystem.
- **Resolution:** In `src/PdfGenerator.php`, explicitly configured Dompdf options to point `tempDir`, `fontDir`, and `fontCache` to `sys_get_temp_dir()`, which safely maps to `/tmp` in the Lambda execution environment. Disabled remote resource fetching (`isRemoteEnabled = false`) to enhance execution security and speed.

### Issue 2: AWS IAM Maximum Access Keys Limit Throttling
- **Error Encountered:** When deploying through Serverless CLI, authentication failed because the IAM user `lambda` already had the maximum allowed limit of 2 active access keys, preventing new programmatic key creation.
- **Root Cause:** AWS enforces a hard limit of two active or inactive access keys per IAM identity.
- **Resolution:** Cleaned up unused inactive keys through the IAM Console security credentials tab, generated a clean CLI access key pair, and configured the AWS credential chain in `~/.aws/credentials`. Deployment succeeded immediately without modifying or exposing credentials.

---

## 4. Academic Reflection

Building this Serverless Payroll Management API provided deep practical experience in transitioning traditional PHP backend architectures into modern cloud-native serverless environments.

1. **PHP on Serverless with Bref:** Before this project, PHP was predominantly associated with traditional LAMP/LEMP stacks (Apache/Nginx on long-running EC2 instances or virtual private servers). Implementing Bref demonstrated how PHP can run efficiently on AWS Lambda using custom runtimes. The FPM runtime (`php-82-fpm`) provides high performance and familiar FastCGI semantics, allowing existing HTTP routing libraries like Bramus Router to function seamlessly.

2. **Data Processing Without Databases:** Processing flat CSV datasets rather than querying an SQL database required careful software engineering. Designing `CsvRepository` with in-memory caching and column normalization highlighted the importance of handling edge cases: empty lines, missing values, malformed rows, and BOM markers. Additionally, calculating financial aggregates across 600 records reinforced the danger of IEEE 754 floating-point rounding errors; converting amounts into integer cents ensured exact, penny-accurate accounting balance ($4,981,004.63 - $849,478.70 = $4,131,525.93).

3. **Cloud Architecture & Least-Privilege Security:** Implementing the AWS infrastructure through Serverless Framework emphasized infrastructure-as-code principles. Rather than granting broad admin rights, the Lambda execution role was scoped strictly to `s3:PutObject` and `s3:GetObject` on the specific `payslips/*` path in a private, encrypted S3 bucket. This hands-on experience demonstrated how serverless APIs can achieve enterprise-grade security, extreme cost efficiency, and automated scalability.

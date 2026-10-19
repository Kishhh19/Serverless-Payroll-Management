# Academic Project Submission Report

## 1. Project Information

- **Project Title:** Serverless Payroll Management API Using PHP and AWS Lambda
- **Technologies Used:** PHP 8.2, Bref 2.4 (FPM Runtime), AWS Lambda, Amazon API Gateway (HTTP API v2), Amazon S3 (AES-256 Encrypted), Dompdf 3.1, Bramus Router 1.6, Composer 2.9, Serverless Framework 3.40.
- **Student / Author:** Mark Laurence Santos (`santos.marklaurence@student.auf.edu.ph`)
- **GitHub Repository URL:** https://github.com/Kishhh19/Serverless-Payroll-Management.git
- **AWS Account ID:** `408852558600`
- **AWS Region:** `ap-southeast-1` (Asia Pacific - Singapore)
- **S3 Bucket Name:** `payroll-payslips-408852558600-dev`
- **Date of Verification:** October 19, 2026

---

## 2. Endpoint Results & Verification

All four required API endpoints were implemented, verified with automated unit tests (36 tests passed) and full HTTP endpoint integration tests (34 tests passed):

### Endpoint A: List Employees
- **HTTP Method & Path:** `GET /api/employees`
- **Actual Request:**
  ```bash
  curl -i "http://127.0.0.1:8888/api/employees"
  ```
- **Actual HTTP Status:** `200 OK`
- **Test Outcome:** Returned all 500 employee records formatted as JSON, preserving actual dataset fields (`employee_id`, `id`, `full_name`, `email`, `position_id`, `department_id`, `manager_id`) and enriched with department names and position titles.
- **Screenshot Filename:** `screenshots/01_employee_endpoint.png`

---

### Endpoint B: Payroll Summary Report
- **HTTP Method & Path:** `GET /api/reports/payroll`
- **Actual Request:**
  ```bash
  curl -i "http://127.0.0.1:8888/api/reports/payroll"
  ```
- **Actual HTTP Status:** `200 OK`
- **Test Outcome:** Accurately calculated aggregate metrics across all 600 payroll records using decimal-safe integer cents arithmetic:
  - `total_payroll_records`: `600`
  - `total_gross_pay`: `$4,981,004.63`
  - `total_deductions`: `$849,478.70`
  - `total_net_pay`: `$4,131,525.93`
  - Integrity: `$4,981,004.63 - $849,478.70 = $4,131,525.93` (Balanced to the exact cent).
- **Screenshot Filename:** `screenshots/02_payroll_summary_report.png`

---

### Endpoint C: Individual Payslip
- **HTTP Method & Path:** `GET /api/payslips/{payroll_id}`
- **Actual Request (Existing Record):**
  ```bash
  curl -i "http://127.0.0.1:8888/api/payslips/1"
  ```
- **Actual HTTP Status:** `200 OK`
- **Test Outcome:** Correctly matched payroll ID `1` to employee `319` ("Sam Dubois"), returning gross pay of `$14,131.54`, deductions of `$12,626.36`, and net pay of `$1,505.18`.
- **Actual Request (Non-Existent Record):**
  ```bash
  curl -i "http://127.0.0.1:8888/api/payslips/99999"
  ```
- **Actual HTTP Status:** `404 Not Found` (with consistent JSON error response).
- **Screenshot Filename:** `screenshots/03_individual_payslip.png`

---

### Endpoint D: Generate and Upload PDF Payslip
- **HTTP Method & Path:** `POST /api/payslips/{payroll_id}/generate`
- **Actual Request:**
  ```bash
  curl -i -X POST "http://127.0.0.1:8888/api/payslips/1/generate"
  ```
- **Actual HTTP Status:** `200 OK` (when uploaded to S3) / `404 Not Found` (when payroll ID does not exist)
- **Test Outcome:** Generated a valid, high-contrast PDF document (size: 5,297 bytes) with official styling via Dompdf in memory, and formatted the S3 object key `payslips/1.pdf` for secure upload to `payroll-payslips-408852558600-dev`.
- **Screenshot Filename:** `screenshots/04_pdf_generation_upload.png`
- **Generated PDF Document:** `screenshots/sample_payslip_1.pdf`

---

## 3. Issues Encountered and Resolutions

### Issue 1: Serverless Variable Resolution Without Active Credentials
- **Error Encountered:** During initial validation of `serverless.yml` using `npx serverless@3 print --stage dev`, Serverless Framework aborted with:
  `Cannot resolve variable at "custom.bucketName": AWS provider credentials not found. Learn how to set up AWS provider credentials in our docs here: http://slss.io/aws-creds-setup`.
- **Root Cause:** The bucket name originally used `${aws:accountId}`, which requires the Serverless CLI to actively make an AWS STS `GetCallerIdentity` API call during variable compilation. Without active session credentials in the local shell, offline compilation failed.
- **Resolution:** Decoupled the dynamic AWS STS resolution by incorporating the designated account ID and stage directly with fallback override support:
  `${param:bucket, 'payroll-payslips-408852558600-${sls:stage, "dev"}'}`.
  This enabled immediate offline configuration validation, deterministic S3 bucket naming, and seamless CI/CD execution while allowing deployment-time parameter overrides if needed.

### Issue 2: Dompdf Temporary and Font Storage in AWS Lambda
- **Error Encountered:** In AWS Lambda execution environments, all filesystem paths except `/tmp` are strictly read-only. Standard Dompdf installations attempt to write temporary font metric caches to `vendor/dompdf/dompdf/lib/fonts/` or the document root, causing fatal write permission errors during PDF rendering.
- **Root Cause:** Dompdf default configuration assumes a persistent, writable local filesystem.
- **Resolution:** In `src/PdfGenerator.php`, explicitly configured Dompdf options to point `tempDir`, `fontDir`, and `fontCache` to `sys_get_temp_dir()`, which safely maps to `/tmp` in the Lambda execution environment. Disabled remote resource fetching (`isRemoteEnabled = false`) to enhance execution security and speed.

---

## 4. Academic Reflection

Building this Serverless Payroll Management API provided deep practical experience in transitioning traditional PHP backend architectures into modern cloud-native serverless environments.

1. **PHP on Serverless with Bref:** Before this project, PHP was predominantly associated with traditional LAMP/LEMP stacks (Apache/Nginx on long-running EC2 instances or virtual private servers). Implementing Bref demonstrated how PHP can run efficiently on AWS Lambda using custom runtimes. The FPM runtime (`php-82-fpm`) provides high performance and familiar FastCGI semantics, allowing existing HTTP routing libraries like Bramus Router to function seamlessly.

2. **Data Processing Without Databases:** Processing flat CSV datasets rather than querying an SQL database required careful software engineering. Designing `CsvRepository` with in-memory caching and column normalization highlighted the importance of handling edge cases: empty lines, missing values, malformed rows, and BOM markers. Additionally, calculating financial aggregates across 600 records reinforced the danger of IEEE 754 floating-point rounding errors; converting amounts into integer cents ensured exact, penny-accurate accounting balance ($4,981,004.63 - $849,478.70 = $4,131,525.93).

3. **Cloud Architecture & Least-Privilege Security:** Implementing the AWS infrastructure through Serverless Framework emphasized infrastructure-as-code principles. Rather than granting broad admin rights, the Lambda execution role was scoped strictly to `s3:PutObject` and `s3:GetObject` on the specific `payslips/*` path in a private, encrypted S3 bucket. This hands-on experience demonstrated how serverless APIs can achieve enterprise-grade security, extreme cost efficiency, and automated scalability.

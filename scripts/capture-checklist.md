# Academic Project Screenshot Capture Checklist & Instructions

This document provides exact steps to capture genuine screenshots for the academic submission of the **Serverless Payroll Management API Using PHP and AWS Lambda**.

All screenshots must be saved to the `screenshots/` directory in PNG format.

---

### Screenshot 1: Employee Endpoint Response
- **File Name:** `screenshots/01_employee_endpoint.png`
- **Target URL:** `GET {API_BASE_URL}/api/employees`
- **Tool:** Browser, Postman, or Terminal (`curl -i "{API_BASE_URL}/api/employees"`)
- **Required Details Visible:**
  - HTTP 200 OK status.
  - `Content-Type: application/json`.
  - JSON payload with `"success": true` and array of employee objects containing real dataset records (`employee_id`, `full_name`, `department_id`, etc.).

---

### Screenshot 2: Payroll Summary Report Endpoint
- **File Name:** `screenshots/02_payroll_summary_report.png`
- **Target URL:** `GET {API_BASE_URL}/api/reports/payroll`
- **Tool:** Browser, Postman, or Terminal (`curl -i "{API_BASE_URL}/api/reports/payroll"`)
- **Required Details Visible:**
  - HTTP 200 OK status.
  - `"total_payroll_records": 600`
  - `"total_gross_pay": 4981004.63`
  - `"total_deductions": 849478.70`
  - `"total_net_pay": 4131525.93`

---

### Screenshot 3: Individual Payslip Endpoint
- **File Name:** `screenshots/03_individual_payslip.png`
- **Target URL:** `GET {API_BASE_URL}/api/payslips/1`
- **Tool:** Browser, Postman, or Terminal (`curl -i "{API_BASE_URL}/api/payslips/1"`)
- **Required Details Visible:**
  - HTTP 200 OK status.
  - `"payroll_id": 1`
  - Associated employee name (`Sam Dubois`) and ID (`319`).
  - `"gross_pay": 14131.54`, `"deductions": 12626.36`, `"net_pay": 1505.18`.

---

### Screenshot 4: PDF Generation and Upload Response
- **File Name:** `screenshots/04_pdf_generation_upload.png`
- **Target URL:** `POST {API_BASE_URL}/api/payslips/1/generate`
- **Tool:** Postman or Terminal (`curl -i -X POST "{API_BASE_URL}/api/payslips/1/generate"`)
- **Required Details Visible:**
  - HTTP 200 OK status.
  - `"success": true`
  - `"message": "Payslip generated and uploaded."`
  - `"s3_object_key": "payslips/1.pdf"`

---

### Screenshot 5: Generated PDF Payslip Preview
- **File Name:** `screenshots/05_generated_pdf_preview.png`
- **Source:** Generated PDF payslip (e.g., opened in Chrome / Acrobat / PDF reader).
- **Required Details Visible:**
  - Header: "Serverless Payroll API - Official Statement of Earnings and Deductions".
  - Metadata: Payroll ID #1, Employee ID #319, Employee Name (Sam Dubois).
  - Financial Table: Gross regular pay, statutory deductions, net pay ($1,505.18).
  - Professional layout formatted by Dompdf.

---

### Screenshot 6: AWS S3 Console Bucket Contents
- **File Name:** `screenshots/06_aws_s3_bucket.png`
- **Source:** AWS Management Console -> Amazon S3 -> Buckets -> `payroll-payslips-408852558600-dev` -> `payslips/`
- **Required Details Visible:**
  - Bucket name and breadcrumbs.
  - Object `1.pdf` with size (approx ~7KB) and last modified timestamp.
  - Storage class (Standard) and encryption status.

---

### Screenshot 7: AWS Lambda / API Gateway Console
- **File Name:** `screenshots/07_aws_lambda_gateway.png`
- **Source:** AWS Management Console -> AWS Lambda -> Functions -> `serverless-payroll-api-dev-api`
- **Required Details Visible:**
  - Function name, runtime (Custom Runtime `provided.al2`), Bref PHP layer.
  - Attached API Gateway HTTP API trigger with deployed URL.

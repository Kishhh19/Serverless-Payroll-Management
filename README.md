# Serverless Payroll Management API Using PHP and AWS Lambda

An academic serverless RESTful API built with **PHP 8.2**, **Bref**, **AWS Lambda**, **Amazon API Gateway**, and **Amazon S3**. The system processes employee records and payroll transactions directly from official CSV datasets, computes aggregate payroll metrics with decimal safety, serves individual payslip queries, generates downloadable PDF payslips using **Dompdf**, and securely stores payslips in an encrypted Amazon S3 bucket.

---

## Table of Contents
1. [Architecture Overview](#architecture-overview)
2. [Technology Stack](#technology-stack)
3. [Project Structure](#project-structure)
4. [Dataset Details & Source](#dataset-details--source)
5. [Prerequisites](#prerequisites)
6. [Local Installation & Setup](#local-installation--setup)
7. [Running Local Tests](#running-local-tests)
8. [AWS Infrastructure & Security](#aws-infrastructure--security)
9. [Deployment Instructions](#deployment-instructions)
10. [API Endpoint Documentation](#api-endpoint-documentation)
11. [Error Handling & Status Codes](#error-handling--status-codes)
12. [License & Redistribution](#license--redistribution)

---

## 1. Architecture Overview

```
                                  +-----------------------------------------------+
                                  |                 AWS CLOUD                     |
                                  |                                               |
[ HTTP Client / Browser ] ------> |  [ Amazon API Gateway (HTTP API v2) ]        |
                                  |                         |                     |
                                  |                         v                     |
                                  |  [ AWS Lambda Function: PHP 8.2 FPM (Bref) ]  |
                                  |       |             |             |           |
                                  |       v             v             v           |
                                  |  [ CSV Data ]  [ Dompdf ]    [ S3 Client ]    |
                                  |  (Read-only)   (In-Memory)        |           |
                                  |                                   v           |
                                  |                   [ Amazon S3 Bucket ]        |
                                  |                   (Encrypted, Private)        |
                                  +-----------------------------------------------+
```

The application operates completely serverlessly:
- **Compute:** AWS Lambda running PHP 8.2 via Bref's FastCGI Process Manager (`php-82-fpm`) custom layer.
- **API Routing:** Amazon API Gateway HTTP API directs incoming traffic directly to `public/index.php`.
- **Data Persistence:** Read-only CSV files packaged with the Lambda application; no external database engine required.
- **Document Generation:** In-memory HTML-to-PDF rendering using Dompdf with Lambda `/tmp` font cache.
- **Document Storage:** Payslips uploaded to private, server-side encrypted Amazon S3 buckets via AWS SDK for PHP.

---

## 2. Technology Stack

- **PHP 8.2:** Modern PHP runtime with strict typing.
- **Bref 2.x:** Serverless PHP runtime layers and integration for AWS Lambda.
- **Serverless Framework (v3):** Infrastructure as Code (IaC) deployment orchestration.
- **AWS Lambda:** Serverless execution environment with least-privilege IAM execution role.
- **Amazon API Gateway (HTTP API):** Low-latency, cost-effective HTTP ingress.
- **Amazon S3:** Private object storage with AES-256 server-side encryption.
- **Dompdf 3.x:** HTML5 to PDF converter.
- **AWS SDK for PHP 3.x:** Official AWS client for Amazon S3 integration.
- **Bramus Router 1.6:** Fast, lightweight HTTP request router.
- **Composer 2.x:** Dependency management.

---

## 3. Project Structure

```
payroll-api/
├── public/
│   └── index.php                # Application entry point & FastCGI handler
├── src/
│   ├── CsvRepository.php        # Robust CSV reader using fgetcsv with header mapping
│   ├── EmployeeService.php      # Employee retrieval and department/position enrichment
│   ├── PayrollService.php       # Aggregate payroll calculations with decimal-safe integer cents
│   ├── PayslipService.php       # Payslip record assembly and employee association
│   ├── PdfGenerator.php         # In-memory PDF document generation with Dompdf
│   ├── Response.php             # Standardized JSON response and error formatter
│   ├── Router.php               # Bramus router definitions, 404 and 405 handlers
│   └── S3Storage.php            # AWS SDK S3 client for PDF upload and presigned URLs
├── data/
│   ├── departments.csv          # Department definitions (10 rows)
│   ├── employees.csv            # Employee records (500 rows)
│   ├── payroll.csv              # Monthly payroll runs (600 rows)
│   └── positions.csv            # Job positions and salary ranges (25 rows)
├── templates/
│   └── payslip.php              # Professional HTML payslip template
├── tests/
│   ├── test_payroll.php         # Unit & edge case test suite (36 tests)
│   └── test_api.php             # Full HTTP endpoint integration test suite (34 tests)
├── scripts/
│   ├── setup.sh                 # Environment setup and dataset download script
│   ├── test-api.sh              # Bash automated curl test runner
│   └── capture-checklist.md     # Screenshot checklist for submission
├── screenshots/
│   └── sample_payslip_1.pdf     # Real verified generated PDF payslip
├── composer.json                # Composer dependencies and PSR-4 autoloading
├── composer.lock                # Locked dependency tree
├── serverless.yml               # Serverless Framework configuration and IaC
├── .env.example                 # Environment configuration placeholders
├── .gitignore                   # Excludes vendor, serverless artifacts, and secrets
├── README.md                    # Project documentation
├── DEPLOYMENT.md                # Infrastructure deployment and verification guide
└── SUBMISSION.md                # Academic submission report and student reflection
```

---

## 4. Dataset Details & Source

The dataset is sourced from the official reference:
[https://databasesample.com/datasets/employees-and-payroll](https://databasesample.com/datasets/employees-and-payroll) (Licensed under [Creative Commons CC0 1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/)).

### Data Schema:
- **`employees.csv`:** `id`, `full_name`, `email`, `position_id`, `department_id`, `manager_id`
- **`payroll.csv`:** `id`, `employee_id`, `pay_period_start`, `pay_period_end`, `gross_pay`, `net_pay`
- **`departments.csv`:** `id`, `name`, `location`
- **`positions.csv`:** `id`, `title`, `salary_range`

### Verified Aggregate Totals (600 Payroll Records):
- **Total Gross Pay:** `$4,981,004.63`
- **Total Deductions:** `$849,478.70` (calculated as `gross_pay - net_pay`)
- **Total Net Pay:** `$4,131,525.93`
- **Integrity Equation:** `$4,981,004.63 - $849,478.70 = $4,131,525.93` (Exact match)

---

## 5. Prerequisites

- **PHP:** `^8.2` with extensions `mbstring`, `curl`, `json`, `gd` (or `imagick`), `xml`, `fileinfo`.
- **Composer:** `^2.2`
- **Node.js:** `^18.x` or later (for Serverless Framework CLI).
- **AWS Account:** With permissions to provision S3, Lambda, IAM, and API Gateway.

---

## 6. Local Installation & Setup

1. **Clone or navigate to the project directory:**
   ```bash
   cd payroll-api
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install --no-interaction --optimize-autoloader
   ```

3. **Verify dataset files:**
   Run `scripts/setup.sh` or verify that `data/employees.csv` and `data/payroll.csv` exist in the `data/` folder.

4. **Prepare environment configuration:**
   ```bash
   cp .env.example .env
   ```

---

## 7. Running Local Tests

The project includes two independent test suites:

### A. Unit & Service Tests (36 Tests)
Validates CSV reading, column normalization, invalid number handling, empty files, financial rounding, and PDF generation:
```bash
php tests/test_payroll.php
```

### B. Full HTTP API Integration Tests (34 Tests)
Spawns a local PHP test server on `127.0.0.1:8888` and tests real HTTP requests, headers, status codes (200, 400, 404, 405), and routing:
```bash
php tests/test_api.php
```

---

## 8. AWS Infrastructure & Security

### Least-Privilege IAM Policy
The Lambda execution role permissions are scoped strictly to the required operations and resource path:
```yaml
iam:
  role:
    statements:
      - Effect: Allow
        Action:
          - s3:PutObject
          - s3:GetObject
        Resource:
          - 'arn:aws:s3:::payroll-payslips-408852558600-dev/payslips/*'
```

### Amazon S3 Bucket Security
The bucket is created with:
- Public access blocked (`BlockPublicAcls`, `BlockPublicPolicy`, `IgnorePublicAcls`, `RestrictPublicBuckets`).
- Default server-side encryption enabled using AWS-managed AES-256 keys.

---

## 9. Deployment Instructions

1. **Verify Serverless Framework configuration:**
   ```bash
   npx serverless@3 print --stage dev
   ```

2. **Deploy to AWS:**
   ```bash
   npx serverless@3 deploy --stage dev
   ```

3. **Obtain API Gateway Base URL:**
   Serverless prints the live endpoint upon completion:
   ```
   endpoints:
     httpApi: https://<api-id>.execute-api.ap-southeast-1.amazonaws.com
   ```

4. **Test Live AWS Endpoints:**
   ```bash
   bash scripts/test-api.sh https://<api-id>.execute-api.ap-southeast-1.amazonaws.com
   ```

See [DEPLOYMENT.md](DEPLOYMENT.md) for full deployment details and troubleshooting.

---

## 10. API Endpoint Documentation

### Endpoint A: List Employees
- **Method:** `GET`
- **Path:** `/api/employees`
- **Description:** Returns all 500 employee records enriched with position and department names.
- **Sample Request:**
  ```bash
  curl -i "https://<api-id>.execute-api.ap-southeast-1.amazonaws.com/api/employees"
  ```
- **Response `200 OK`:**
  ```json
  {
    "success": true,
    "data": [
      {
        "employee_id": 1,
        "id": 1,
        "full_name": "Sam Dubois",
        "email": "sam.dubois@example.net",
        "position_id": 1,
        "position_title": "developer engineer",
        "department_id": 8,
        "department_name": "production",
        "manager_id": null
      }
    ]
  }
  ```

---

### Endpoint B: Payroll Summary Report
- **Method:** `GET`
- **Path:** `/api/reports/payroll`
- **Description:** Calculates total records, gross pay, deductions, and net pay across all payroll runs.
- **Sample Request:**
  ```bash
  curl -i "https://<api-id>.execute-api.ap-southeast-1.amazonaws.com/api/reports/payroll"
  ```
- **Response `200 OK`:**
  ```json
  {
    "success": true,
    "data": {
      "total_payroll_records": 600,
      "total_gross_pay": 4981004.63,
      "total_deductions": 849478.70,
      "total_net_pay": 4131525.93
    }
  }
  ```

---

### Endpoint C: Individual Payslip
- **Method:** `GET`
- **Path:** `/api/payslips/{payroll_id}`
- **Description:** Fetches a single payroll record and its associated employee details.
- **Sample Request:**
  ```bash
  curl -i "https://<api-id>.execute-api.ap-southeast-1.amazonaws.com/api/payslips/1"
  ```
- **Response `200 OK`:**
  ```json
  {
    "success": true,
    "data": {
      "payroll_id": 1,
      "employee_id": 319,
      "employee_name": "Sam Dubois",
      "department": "production",
      "position": "developer engineer",
      "pay_period_start": "2023-10-14",
      "pay_period_end": "2023-05-06",
      "gross_pay": 14131.54,
      "deductions": 12626.36,
      "net_pay": 1505.18
    }
  }
  ```
- **Response `404 Not Found` (when payroll ID does not exist):**
  ```json
  {
    "success": false,
    "error": "Payroll record #99999 not found.",
    "status_code": 404
  }
  ```

---

### Endpoint D: Generate and Upload PDF Payslip
- **Method:** `POST`
- **Path:** `/api/payslips/{payroll_id}/generate`
- **Description:** Generates a professional PDF payslip using Dompdf in memory and uploads it to Amazon S3 under `payslips/{payroll_id}.pdf`.
- **Sample Request:**
  ```bash
  curl -i -X POST "https://<api-id>.execute-api.ap-southeast-1.amazonaws.com/api/payslips/1/generate"
  ```
- **Response `200 OK`:**
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

---

## 11. Error Handling & Status Codes

- `200 OK`: Request succeeded.
- `400 Bad Request`: Malformed or non-numeric parameter (e.g., `GET /api/payslips/abc`).
- `404 Not Found`: Unknown endpoint route or non-existent payroll identifier.
- `405 Method Not Allowed`: HTTP method not supported on the target endpoint (e.g., `POST /api/employees`).
- `500 Internal Server Error`: Unhandled application exception; safe generic JSON is returned without leaking stack traces or credentials.

---

## 12. License & Redistribution

- **Dataset License:** Creative Commons CC0 1.0 Universal (Public Domain).
- **Application Code:** Academic MIT License.

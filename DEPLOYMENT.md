# AWS Deployment and Infrastructure Guide

This guide describes the complete AWS deployment workflow, infrastructure architecture, least-privilege IAM configuration, and verification steps for the **Serverless Payroll Management API Using PHP and AWS Lambda**.

---

## 1. AWS Account & Infrastructure Details

| Resource | Value / Configuration |
|---|---|
| **AWS Account ID** | `408852558600` |
| **AWS Region** | `ap-southeast-1` (Asia Pacific - Singapore) |
| **Stage** | `dev` |
| **Service Name** | `serverless-payroll-api` |
| **Lambda Function** | `serverless-payroll-api-dev-api` |
| **Lambda Runtime** | `provided.al2` (Bref PHP 8.2 FPM Layer) |
| **Bref Layer ARN** | `arn:aws:lambda:ap-southeast-1:534081306603:layer:php-82-fpm:114` |
| **Memory Allocation** | `512 MB` |
| **Timeout** | `28 seconds` |
| **S3 Bucket Name** | `payroll-payslips-408852558600-dev` |
| **S3 Prefix** | `payslips/` |
| **S3 Encryption** | AES-256 Server-Side Encryption (`AES256`) |
| **S3 Public Access** | Block All Public Access Enabled |

---

## 2. Infrastructure as Code (`serverless.yml`)

The Serverless Framework configuration provisions the full stack declaratively:

1. **Amazon API Gateway (HTTP API v2):** Maps all routes (`*`) to the PHP entry point (`public/index.php`).
2. **AWS Lambda:** Hosts the PHP runtime with FastCGI Process Manager for high throughput.
3. **AWS IAM Execution Role:** Uses least-privilege permissions, granting only `s3:PutObject` and `s3:GetObject` on the `payslips/*` path.
4. **Amazon S3 Bucket:** Managed via CloudFormation resource definition (`PayslipsS3Bucket`) with default encryption and public access blocks.

---

## 3. Step-by-Step Deployment Instructions

### Step 1: Install Dependencies
Ensure Composer dependencies and Serverless Framework are ready:
```bash
composer install --no-dev --optimize-autoloader
```

### Step 2: Configure AWS Credentials
Ensure AWS credentials for Account `408852558600` are configured in your environment using standard AWS credential mechanisms:

**Option A: Using Environment Variables**
```bash
export AWS_ACCESS_KEY_ID="<your-access-key>"
export AWS_SECRET_ACCESS_KEY="<your-secret-key>"
export AWS_DEFAULT_REGION="ap-southeast-1"
```

**Option B: Using AWS CLI Profile**
```bash
aws configure
# AWS Access Key ID: <your-key>
# AWS Secret Access Key: <your-secret>
# Default region name: ap-southeast-1
```

### Step 3: Validate the CloudFormation Template
Test that all variables resolve and the configuration compiles without errors:
```bash
npx serverless@3 print --stage dev
```

### Step 4: Deploy the Application
Deploy the CloudFormation stack to AWS:
```bash
npx serverless@3 deploy --stage dev --region ap-southeast-1
```

Upon successful deployment, Serverless displays:
```text
Deploying serverless-payroll-api to stage dev (ap-southeast-1)

✔ Service deployed to stack serverless-payroll-api-dev (92s)

endpoint: httpApi: https://<api-id>.execute-api.ap-southeast-1.amazonaws.com
functions:
  api: serverless-payroll-api-dev-api (512 MB)
```

---

## 4. Post-Deployment Verification

### 1. Verify CloudFormation Stack
```bash
aws cloudformation describe-stacks \
  --stack-name serverless-payroll-api-dev \
  --query "Stacks[0].StackStatus" \
  --region ap-southeast-1
# Expected output: "CREATE_COMPLETE" or "UPDATE_COMPLETE"
```

### 2. Verify S3 Bucket & Security Settings
```bash
aws s3api get-public-access-block \
  --bucket payroll-payslips-408852558600-dev \
  --region ap-southeast-1
```

### 3. Verify Lambda Function & Handler
```bash
aws lambda get-function-configuration \
  --function-name serverless-payroll-api-dev-api \
  --region ap-southeast-1
```

### 4. Execute End-to-End Live API Tests
Run the automated test script against the deployed endpoint:
```bash
bash scripts/test-api.sh https://<api-id>.execute-api.ap-southeast-1.amazonaws.com
```

### 5. Verify Generated S3 Payslip Object
```bash
aws s3 ls s3://payroll-payslips-408852558600-dev/payslips/ --region ap-southeast-1
```

---

## 5. Troubleshooting & CloudWatch Logs

### Monitoring CloudWatch Logs
Tail live logs from the deployed Lambda function:
```bash
npx serverless@3 logs -f api --tail
```

### Common Issues & Resolutions

| Issue | Root Cause | Solution |
|---|---|---|
| **HTTP 500 on PDF Generation** | Dompdf cannot write fonts to read-only Lambda root | Ensure `PdfGenerator` sets `tempDir` and `fontDir` to `sys_get_temp_dir()` (`/tmp`). |
| **S3 Access Denied (403)** | IAM execution role missing `s3:PutObject` permission | Verify `serverless.yml` IAM statement targets `arn:aws:s3:::payroll-payslips-408852558600-dev/payslips/*`. |
| **HTTP 502 Bad Gateway** | Lambda execution timeout or memory exhaustion | Keep timeout at 28s and memory at 512 MB to allow comfortable PDF rasterization. |
| **Bref Layer Region Mismatch** | Deploying to a region without matching Bref layer | Bref automatically resolves layer ARNs for all standard regions. Use `ap-southeast-1`. |

---

## 6. Teardown & Resource Cleanup

To remove all provisioned AWS resources after academic review and avoid unexpected charges:

1. **Empty the S3 Bucket:**
   ```bash
   aws s3 rm s3://payroll-payslips-408852558600-dev --recursive
   ```

2. **Remove CloudFormation Stack via Serverless:**
   ```bash
   npx serverless@3 remove --stage dev --region ap-southeast-1
   ```

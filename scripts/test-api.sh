#!/usr/bin/env bash
set -euo pipefail

# Deployed URL or local test endpoint
BASE_URL="${1:-${API_BASE_URL:-http://127.0.0.1:8888}}"
echo "=========================================================="
echo " Testing API Endpoints on: $BASE_URL"
echo "=========================================================="

echo ""
echo "[Test 1] Health Check / Root"
curl -s -i "$BASE_URL/" | head -n 12

echo ""
echo "[Test 2] Endpoint A: GET /api/employees"
curl -s -i "$BASE_URL/api/employees" | head -n 25

echo ""
echo "[Test 3] Endpoint B: GET /api/reports/payroll"
curl -s -i "$BASE_URL/api/reports/payroll"

echo ""
echo ""
echo "[Test 4] Endpoint C: GET /api/payslips/1 (Existing record)"
curl -s -i "$BASE_URL/api/payslips/1"

echo ""
echo ""
echo "[Test 5] Endpoint C: GET /api/payslips/99999 (Non-existent record - expected 404)"
curl -s -i "$BASE_URL/api/payslips/99999"

echo ""
echo ""
echo "[Test 6] Endpoint D: POST /api/payslips/1/generate (Generate PDF & Upload to S3)"
curl -s -i -X POST "$BASE_URL/api/payslips/1/generate"

echo ""
echo ""
echo "[Test 7] Endpoint D: POST /api/payslips/99999/generate (Non-existent - expected 404)"
curl -s -i -X POST "$BASE_URL/api/payslips/99999/generate"

echo ""
echo ""
echo "[Test 8] Method Not Allowed: POST /api/employees (expected 405)"
curl -s -i -X POST "$BASE_URL/api/employees"

echo ""
echo ""
echo "[Test 9] Unknown Route: GET /api/unknown_resource (expected 404)"
curl -s -i "$BASE_URL/api/unknown_resource"

echo ""
echo "=========================================================="
echo " API Test Run Completed."
echo "=========================================================="

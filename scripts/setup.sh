#!/usr/bin/env bash
set -euo pipefail

echo "=========================================================="
echo " Setting up Serverless Payroll Management API"
echo "=========================================================="

# 1. Check PHP and Composer
command -v php >/dev/null 2>&1 || { echo >&2 "Error: PHP is required but not installed."; exit 1; }
command -v composer >/dev/null 2>&1 || { echo >&2 "Error: Composer is required but not installed."; exit 1; }

echo "PHP Version: $(php -v | head -n 1)"
echo "Composer Version: $(composer --version | head -n 1)"

# 2. Install PHP Dependencies
echo "Installing Composer dependencies..."
composer install --no-interaction --optimize-autoloader

# 3. Create data directory if not exists
mkdir -p data

# 4. Download Official CSV Datasets if missing
BASE_DATA_URL="https://database-sample-website.s3.eu-north-1.amazonaws.com/datasets/employees-and-payroll/v3/csv"

for file in employees.csv payroll.csv departments.csv positions.csv; do
    if [ ! -f "data/$file" ]; then
        echo "Downloading $file from official source..."
        curl -fsSL "$BASE_DATA_URL/$file" -o "data/$file"
    else
        echo "File data/$file already exists."
    fi
done

# 5. Environment configuration
if [ ! -f ".env" ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

echo ""
echo "Setup completed successfully!"
echo "Run local tests with: php tests/test_api.php"

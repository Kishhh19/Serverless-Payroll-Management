<?php

declare(strict_types=1);

namespace PayrollApi;

use RuntimeException;

class CsvRepository
{
    private string $dataDir;

    /**
     * @var array<string, array<int, array<string, string>>>
     */
    private array $cache = [];

    public function __construct(?string $dataDir = null)
    {
        $this->dataDir = $dataDir ?? (dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data');
    }

    /**
     * Read and parse a CSV file into an associative array using the header row as keys.
     *
     * @param string $filename Base filename inside data directory
     * @return array<int, array<string, string>>
     */
    public function readCsv(string $filename): array
    {
        if (isset($this->cache[$filename])) {
            return $this->cache[$filename];
        }

        $filepath = $this->dataDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new RuntimeException("Data file not found or unreadable: {$filename}");
        }

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Failed to open data file: {$filename}");
        }

        $records = [];
        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows or whitespace-only rows
            if ($row === [null] || empty($row)) {
                continue;
            }

            // Remove UTF-8 BOM if present on the first cell
            if ($headers === null) {
                if (isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);
                }
                $headers = array_map('trim', $row);
                continue;
            }

            // Handle malformed rows where column count does not match headers
            if (count($row) !== count($headers)) {
                // If row has fewer columns, pad with empty strings; if more, slice
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), '');
                } else {
                    $row = array_slice($row, 0, count($headers));
                }
            }

            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = trim((string)($row[$index] ?? ''));
            }

            $records[] = $record;
        }

        fclose($handle);

        $this->cache[$filename] = $records;
        return $records;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getEmployees(): array
    {
        return $this->readCsv('employees.csv');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getPayrollRecords(): array
    {
        return $this->readCsv('payroll.csv');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getDepartments(): array
    {
        return $this->readCsv('departments.csv');
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getPositions(): array
    {
        return $this->readCsv('positions.csv');
    }

    /**
     * Find an employee by ID.
     *
     * @param int $id
     * @return array<string, string>|null
     */
    public function findEmployeeById(int $id): ?array
    {
        foreach ($this->getEmployees() as $employee) {
            if ((int)($employee['id'] ?? 0) === $id) {
                return $employee;
            }
        }
        return null;
    }

    /**
     * Find a payroll record by ID.
     *
     * @param int $id
     * @return array<string, string>|null
     */
    public function findPayrollById(int $id): ?array
    {
        foreach ($this->getPayrollRecords() as $payroll) {
            if ((int)($payroll['id'] ?? 0) === $id) {
                return $payroll;
            }
        }
        return null;
    }

    /**
     * Find a department by ID.
     *
     * @param int $id
     * @return array<string, string>|null
     */
    public function findDepartmentById(int $id): ?array
    {
        foreach ($this->getDepartments() as $dept) {
            if ((int)($dept['id'] ?? 0) === $id) {
                return $dept;
            }
        }
        return null;
    }

    /**
     * Find a position by ID.
     *
     * @param int $id
     * @return array<string, string>|null
     */
    public function findPositionById(int $id): ?array
    {
        foreach ($this->getPositions() as $position) {
            if ((int)($position['id'] ?? 0) === $id) {
                return $position;
            }
        }
        return null;
    }
}

<?php

declare(strict_types=1);

namespace PayrollApi;

class EmployeeService
{
    private CsvRepository $repository;

    public function __construct(?CsvRepository $repository = null)
    {
        $this->repository = $repository ?? new CsvRepository();
    }

    /**
     * Retrieve all employee records with their actual fields and mapped identifiers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllEmployees(): array
    {
        $rawEmployees = $this->repository->getEmployees();
        $departments = [];
        $positions = [];

        // Pre-index departments and positions for fast lookup
        foreach ($this->repository->getDepartments() as $dept) {
            $departments[(int)($dept['id'] ?? 0)] = $dept['name'] ?? '';
        }
        foreach ($this->repository->getPositions() as $pos) {
            $positions[(int)($pos['id'] ?? 0)] = $pos['title'] ?? '';
        }

        $employees = [];
        foreach ($rawEmployees as $emp) {
            $id = (int)($emp['id'] ?? 0);
            $deptId = (int)($emp['department_id'] ?? 0);
            $posId = (int)($emp['position_id'] ?? 0);

            $employees[] = [
                'employee_id' => $id,
                'id' => $id,
                'full_name' => (string)($emp['full_name'] ?? ''),
                'email' => (string)($emp['email'] ?? ''),
                'position_id' => $posId,
                'position_title' => $positions[$posId] ?? null,
                'department_id' => $deptId,
                'department_name' => $departments[$deptId] ?? null,
                'manager_id' => isset($emp['manager_id']) && $emp['manager_id'] !== '' ? (int)$emp['manager_id'] : null,
            ];
        }

        return $employees;
    }

    /**
     * Retrieve a single employee by ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function getEmployeeById(int $id): ?array
    {
        $emp = $this->repository->findEmployeeById($id);
        if ($emp === null) {
            return null;
        }

        $deptId = (int)($emp['department_id'] ?? 0);
        $posId = (int)($emp['position_id'] ?? 0);

        $dept = $this->repository->findDepartmentById($deptId);
        $pos = $this->repository->findPositionById($posId);

        return [
            'employee_id' => (int)$emp['id'],
            'id' => (int)$emp['id'],
            'full_name' => (string)($emp['full_name'] ?? ''),
            'email' => (string)($emp['email'] ?? ''),
            'position_id' => $posId,
            'position_title' => $pos['title'] ?? null,
            'department_id' => $deptId,
            'department_name' => $dept['name'] ?? null,
            'manager_id' => isset($emp['manager_id']) && $emp['manager_id'] !== '' ? (int)$emp['manager_id'] : null,
        ];
    }
}

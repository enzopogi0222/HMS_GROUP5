<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class DepartmentService
{
    protected $db;

    public function __construct(ConnectionInterface $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function getDepartments(): array
    {
        if (!$this->db->tableExists('department')) {
            return [];
        }

        $builder = $this->db->table('department');
        
        // Select all available columns
        $selectColumns = ['department_id', 'name'];
        
        // Add optional columns if they exist
        if ($this->fieldExists('code', 'department')) {
            $selectColumns[] = 'code';
        }
        if ($this->fieldExists('type', 'department')) {
            $selectColumns[] = 'type';
        }
        if ($this->fieldExists('floor', 'department')) {
            $selectColumns[] = 'floor';
        }
        if ($this->fieldExists('department_head_id', 'department')) {
            $selectColumns[] = 'department_head_id';
        }
        if ($this->fieldExists('contact_number', 'department')) {
            $selectColumns[] = 'contact_number';
        }
        if ($this->fieldExists('description', 'department')) {
            $selectColumns[] = 'description';
        }
        if ($this->fieldExists('status', 'department')) {
            $selectColumns[] = 'status';
        }
        if ($this->fieldExists('created_at', 'department')) {
            $selectColumns[] = 'created_at';
        }
        if ($this->fieldExists('updated_at', 'department')) {
            $selectColumns[] = 'updated_at';
        }

        return $builder->select($selectColumns)
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getDepartmentStats(): array
    {
        if (!$this->db->tableExists('department')) {
            return ['total_departments' => 0, 'with_heads' => 0, 'without_heads' => 0, 'with_specialties' => 0];
        }

        $totalDepartments = (int) $this->db->table('department')->countAllResults();
        $withHeads = $this->countDepartmentsWithAssignedStaff();
        $withSpecialties = $this->countDepartmentsWithSpecialties();

        return [
            'total_departments' => $totalDepartments,
            'with_heads' => $withHeads,
            'without_heads' => max(0, $totalDepartments - $withHeads),
            'with_specialties' => $withSpecialties,
        ];
    }

    private function countDepartmentsWithAssignedStaff(): int
    {
        if (!$this->db->tableExists('staff') || !$this->fieldExists('department_id', 'staff')) {
            return 0;
        }

        $rows = $this->db->table('staff')
            ->select('department_id')
            ->where('department_id IS NOT NULL', null, false)
            ->groupBy('department_id')
            ->get()
            ->getResultArray();

        return count($rows);
    }

    private function countDepartmentsWithSpecialties(): int
    {
        $table = $this->resolveSpecialtyTable();
        if (!$table || !$this->fieldExists('department_id', $table)) {
            return 0;
        }

        $rows = $this->db->table($table)
            ->select('department_id')
            ->where('department_id IS NOT NULL', null, false)
            ->groupBy('department_id')
            ->get()
            ->getResultArray();

        return count($rows);
    }

    public function getPotentialDepartmentHeads(): array
    {
        if (!$this->db->tableExists('staff')) {
            return [];
        }

        $doctorTable = $this->db->tableExists('doctor') ? 'doctor' : null;
        $builder = $this->db->table('staff s')->select($this->getStaffSelectColumns());

        if ($doctorTable) {
            $builder->join('doctor d', 'd.staff_id = s.staff_id', 'inner')
                ->where('d.status', 'Active');
            if ($this->fieldExists('specialization', 'doctor')) {
                $builder->select('d.specialization');
            }
        } else {
            $builder->where('s.role', 'doctor');
        }

        $builder->orderBy('s.first_name', 'ASC');

        try {
            $rows = $builder->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to fetch department heads: ' . $e->getMessage());
            if (!$this->db->tableExists('staff')) {
                return [];
            }
            $rows = $this->db->table('staff')
                ->select('staff_id, first_name, last_name, position, role')
                ->where('role', 'doctor')
                ->orderBy('first_name', 'ASC')
                ->get()
                ->getResultArray();
        }

        return array_map(static function (array $row) {
            $fullName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            return [
                'staff_id' => $row['staff_id'],
                'full_name' => $fullName !== '' ? $fullName : 'Staff #' . $row['staff_id'],
                'position' => $row['position'] ?? ($row['role'] ?? null),
                'specialization' => $row['specialization'] ?? null,
            ];
        }, $rows);
    }

    private function getStaffSelectColumns(): array
    {
        $columns = ['s.staff_id', 's.first_name', 's.last_name'];
        if ($this->fieldExists('position', 'staff')) {
            $columns[] = 's.position';
        }
        if ($this->fieldExists('role', 'staff')) {
            $columns[] = 's.role';
        }
        return $columns;
    }

    public function getAvailableSpecialties(): array
    {
        $table = null;
        $nameColumn = null;

        if ($this->db->tableExists('specialty')) {
            $table = 'specialty';
            $nameColumn = $this->fieldExists('name', $table) ? 'name' : ($this->fieldExists('specialty_name', $table) ? 'specialty_name' : null);
        } elseif ($this->db->tableExists('specialties')) {
            $table = 'specialties';
            $nameColumn = $this->fieldExists('name', $table) ? 'name' : ($this->fieldExists('specialty_name', $table) ? 'specialty_name' : null);
        } elseif ($this->db->tableExists('department_specialties')) {
            $table = 'department_specialties';
            $nameColumn = $this->fieldExists('name', $table) ? 'name' : ($this->fieldExists('specialty_name', $table) ? 'specialty_name' : null);
        }

        if (!$table || !$nameColumn) {
            return ['Emergency Medicine', 'Cardiology', 'Pediatrics', 'General Surgery'];
        }

        $results = $this->db->table($table)
            ->select($nameColumn . ' as name')
            ->groupBy($nameColumn)
            ->orderBy($nameColumn, 'ASC')
            ->get()
            ->getResultArray();

        if (empty($results)) {
            return ['Emergency Medicine', 'Cardiology', 'Pediatrics', 'General Surgery'];
        }

        return array_map(static fn ($row) => $row['name'], $results);
    }

    private function resolveSpecialtyTable(): ?string
    {
        foreach (['department_specialty', 'department_specialties', 'specialty_department'] as $table) {
            if ($this->db->tableExists($table)) {
                return $table;
            }
        }
        return null;
    }

    private function fieldExists(string $field, string $table): bool
    {
        try {
            return $this->db->fieldExists($field, $table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}


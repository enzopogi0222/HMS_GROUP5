<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Departments extends BaseController
{
    public function create()
    {
        try {
            // Allow POST for create and OPTIONS for preflight
            $method = strtolower($this->request->getMethod());
            if ($method === 'options') {
                return $this->response->setStatusCode(200);
            }
            if ($method !== 'post') {
                return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'Method not allowed']);
            }

            // Support both JSON and form-data submissions safely
            $input = [];
            $contentType = $this->request->getHeaderLine('Content-Type');
            if ($contentType && stripos($contentType, 'application/json') !== false) {
                // Only attempt JSON parsing when Content-Type is JSON
                $rawBody = (string) $this->request->getBody();
                if ($rawBody !== '') {
                    $decoded = json_decode($rawBody, true);
                    if (is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            }
            if (empty($input)) {
                // Fallback to form fields (e.g., multipart/form-data)
                $input = $this->request->getPost();
            }
            $name = trim(preg_replace('/\s+/', ' ', (string)($input['name'] ?? '')));
            $description = $this->sanitizeString($input['description'] ?? null);
            $code = $this->sanitizeString($input['code'] ?? null, 20);
            $floor = $this->sanitizeString($input['floor'] ?? null, 50);
            $building = $this->sanitizeString($input['building'] ?? null, 100);
            $contactNumber = $this->sanitizeString($input['contact_number'] ?? null, 20);
            $departmentHeadId = $this->parseNullableInt($input['department_head'] ?? null);
            $status = $this->normalizeStatus($input['status'] ?? null);
            $departmentType = $this->normalizeDepartmentType($input['department_type'] ?? null);

            $departmentCategory = strtolower(trim((string) ($input['department_category'] ?? '')));

            if ($departmentType === null && $departmentCategory !== '' && $name !== '') {
                $departmentType = $this->inferDepartmentTypeFromCategoryAndName($departmentCategory, $name);
            }

            $errors = [];
            if ($departmentCategory === '' || !in_array($departmentCategory, ['medical', 'non_medical'], true)) {
                $errors['department_category'] = 'Please select medical or non-medical department';
            }

            if ($name === '') {
                $errors['name'] = 'Department name is required';
            }

            if ($departmentType === null) {
                $errors['department_type'] = 'Department type is required';
            }

            if ($departmentType !== null && $departmentCategory !== '' && empty($errors['department_category'])) {
                $allowedTypesByCategory = [
                    'medical' => ['Clinical', 'Emergency', 'Diagnostic'],
                    'non_medical' => ['Administrative', 'Support'],
                ];
                $allowed = $allowedTypesByCategory[$departmentCategory] ?? [];
                if (!in_array($departmentType, $allowed, true)) {
                    $errors['department_type'] = 'Please select a valid department type';
                }
            }

            if (!empty($errors)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $errors,
                ]);
            }

            $db = \Config\Database::connect();

            // Resolve table name dynamically: prefer 'department', support common variants
            $table = null;
            if ($db->tableExists('department')) {
                $table = 'department';
            } elseif ($db->tableExists('deaprtment')) { // handle misspelling
                $table = 'deaprtment';
            } elseif ($db->tableExists('departments')) {
                $table = 'departments';
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => "No department table found (looked for 'department', 'deaprtment', 'departments')."
                ]);
            }

            // Determine the correct name column in department table
            $nameColumn = null;
            if ($db->fieldExists('name', $table)) {
                $nameColumn = 'name';
            } elseif ($db->fieldExists('department_name', $table)) {
                $nameColumn = 'department_name';
            } elseif ($db->fieldExists('dept_name', $table)) {
                $nameColumn = 'dept_name';
            }
            if ($nameColumn === null) {
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Department table is missing a name column (expected "name" or "department_name").',
                ]);
            }

            // Check exists (case-insensitive) with proper quoting of value
            $exists = $db->table($table)
                ->where('LOWER(' . $nameColumn . ') = ' . $db->escape(strtolower($name)), null, false)
                ->get()->getRowArray();
            if ($exists) {
                if ($departmentCategory === 'non_medical' && $db->tableExists('non_medical_department')) {
                    $deptId = $exists['department_id'] ?? null;
                    if ($deptId !== null) {
                        $nonExists = $db->table('non_medical_department')
                            ->where('department_id', (int) $deptId)
                            ->get()->getRowArray();
                        if (!$nonExists) {
                            $nonPayload = [
                                'department_id' => (int) $deptId,
                            ];
                            $db->table('non_medical_department')->insert($nonPayload);
                        }
                    }
                }

                if ($departmentCategory === 'medical' && $db->tableExists('medical_department')) {
                    $deptId = $exists['department_id'] ?? null;
                    if ($deptId !== null) {
                        $medExists = $db->table('medical_department')
                            ->where('department_id', (int) $deptId)
                            ->get()->getRowArray();
                        if (!$medExists) {
                            $medPayload = [
                                'department_id' => (int) $deptId,
                            ];
                            $db->table('medical_department')->insert($medPayload);
                        }
                    }
                }

                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Department already exists',
                    'id' => $exists['department_id'] ?? null
                ]);
            }

            $builder = $db->table($table);

            // Try a series of inserts, progressively reducing columns
            $now = date('Y-m-d H:i:s');
            $payload = [$nameColumn => $name];

            $optionalColumns = [
                'code' => $code,
                'floor' => $floor,
                'building' => $building,
                'department_head_id' => $departmentHeadId,
                'contact_number' => $contactNumber,
                'description' => $description,
                'status' => $status,
                'type' => $departmentType,
            ];

            foreach ($optionalColumns as $column => $value) {
                if ($this->fieldExists($column, $table)) {
                    $payload[$column] = $value;
                }
            }

            if ($this->fieldExists('created_at', $table)) {
                $payload['created_at'] = $now;
            }
            if ($this->fieldExists('updated_at', $table)) {
                $payload['updated_at'] = $now;
            }

            $attempts = [
                $payload,
                [$nameColumn => $name],
            ];

            $ok = false;
            $insertedId = null;

            $db->transBegin();
            foreach ($attempts as $row) {
                $ok = $builder->insert($row);
                if ($ok) {
                    $insertedId = $db->insertID();
                    break;
                }
            }

            if (!$ok || $insertedId === null) {
                $db->transRollback();

                // Provide DB error for diagnostics
                $dbError = $db->error();
                log_message('error', 'Department insert failed: ' . ($dbError['message'] ?? 'unknown'));
                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Failed to create department',
                    'db_error' => $dbError,
                ]);
            }

            if ($departmentCategory === 'non_medical') {
                if (!$db->tableExists('non_medical_department')) {
                    $db->transRollback();
                    return $this->response->setStatusCode(500)->setJSON([
                        'status' => 'error',
                        'message' => "Non-medical department table is missing. Please run migrations.",
                    ]);
                }

                $nonPayload = [
                    'department_id' => (int) $insertedId,
                ];

                $nonBuilder = $db->table('non_medical_department');
                $nonOk = $nonBuilder->insert($nonPayload);
                if (!$nonOk) {
                    $dbError = $db->error();
                    $db->transRollback();
                    log_message('error', 'Non-medical department insert failed: ' . ($dbError['message'] ?? 'unknown'));
                    return $this->response->setStatusCode(500)->setJSON([
                        'status' => 'error',
                        'message' => 'Failed to create non-medical department record',
                        'db_error' => $dbError,
                    ]);
                }
            }

            if ($departmentCategory === 'medical') {
                if (!$db->tableExists('medical_department')) {
                    $db->transRollback();
                    return $this->response->setStatusCode(500)->setJSON([
                        'status' => 'error',
                        'message' => "Medical department table is missing. Please run migrations.",
                    ]);
                }

                $medPayload = [
                    'department_id' => (int) $insertedId,
                ];

                $medBuilder = $db->table('medical_department');
                $medOk = $medBuilder->insert($medPayload);
                if (!$medOk) {
                    $dbError = $db->error();
                    $db->transRollback();
                    log_message('error', 'Medical department insert failed: ' . ($dbError['message'] ?? 'unknown'));
                    return $this->response->setStatusCode(500)->setJSON([
                        'status' => 'error',
                        'message' => 'Failed to create medical department record',
                        'db_error' => $dbError,
                    ]);
                }
            }

            $db->transCommit();

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Department created',
                'id' => $insertedId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Departments::create error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Server error',
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function sanitizeString(?string $value, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/', ' ', strip_tags($value)));
        if ($clean === '') {
            return null;
        }

        if ($maxLength !== null) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }

    private function parseNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function normalizeStatus(?string $value): ?string
    {
        $allowed = ['Active', 'Inactive'];
        if ($value === null) {
            return null;
        }

        $normalized = ucfirst(strtolower(trim($value)));
        return in_array($normalized, $allowed, true) ? $normalized : null;
    }

    private function inferDepartmentTypeFromCategoryAndName(string $category, string $name): ?string
    {
        $category = strtolower(trim($category));
        $n = strtolower(trim($name));

        if (!in_array($category, ['medical', 'non_medical'], true) || $n === '') {
            return null;
        }

        if ($category === 'medical') {
            if (strpos($n, 'emergency') !== false || $n === 'er') {
                return 'Emergency';
            }

            if (
                strpos($n, 'radiology') !== false ||
                strpos($n, 'laboratory') !== false ||
                strpos($n, 'lab') !== false ||
                strpos($n, 'imaging') !== false ||
                strpos($n, 'x-ray') !== false ||
                strpos($n, 'xray') !== false
            ) {
                return 'Diagnostic';
            }

            return 'Clinical';
        }

        // non_medical
        if (
            strpos($n, 'maintenance') !== false ||
            strpos($n, 'housekeeping') !== false ||
            strpos($n, 'security') !== false ||
            strpos($n, 'inventory') !== false ||
            strpos($n, 'supply') !== false ||
            strpos($n, 'information technology') !== false ||
            $n === 'it'
        ) {
            return 'Support';
        }

        return 'Administrative';
    }

    private function normalizeDepartmentType(?string $value): ?string
    {
        $allowed = ['Clinical', 'Administrative', 'Emergency', 'Diagnostic', 'Support'];
        if ($value === null) {
            return null;
        }

        $normalized = ucfirst(strtolower(trim($value)));
        return in_array($normalized, $allowed, true) ? $normalized : null;
    }

    private function fieldExists(string $field, string $table): bool
    {
        try {
            return \Config\Database::connect()->fieldExists($field, $table);
        } catch (\Throwable $e) {
            log_message('warning', 'fieldExists check failed: ' . $e->getMessage());
            return false;
        }
    }
}

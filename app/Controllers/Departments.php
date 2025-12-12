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

            if ($name === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Department name is required',
                    'errors' => ['name' => 'Department name is required']
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
            foreach ($attempts as $row) {
                $ok = $builder->insert($row);
                if ($ok) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => 'Department created',
                        'id' => $db->insertID(),
                    ]);
                }
            }

            // Provide DB error for diagnostics
            $dbError = $db->error();
            log_message('error', 'Department insert failed: ' . ($dbError['message'] ?? 'unknown'));
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to create department',
                'db_error' => $dbError,
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

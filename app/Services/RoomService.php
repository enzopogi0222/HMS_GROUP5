<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;

class RoomService
{
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function getRoomStats(): array
    {
        if (!$this->db->tableExists('room')) {
            return ['total_rooms' => 0, 'occupied_rooms' => 0, 'available_rooms' => 0, 'maintenance_rooms' => 0];
        }

        $builder = $this->db->table('room');
        return [
            'total_rooms' => (int) $builder->countAllResults(),
            'occupied_rooms' => (int) (clone $builder)->where('status', 'occupied')->countAllResults(),
            'available_rooms' => (int) (clone $builder)->where('status', 'available')->countAllResults(),
            'maintenance_rooms' => (int) (clone $builder)->where('status', 'maintenance')->countAllResults(),
        ];
    }

    public function getRooms(): array
    {
        if (! $this->db->tableExists('room')) {
            return [];
        }

        $builder = $this->db->table('room r')
            ->select([
                'r.room_id', 'r.room_number', 'r.room_type', 'r.room_type_id',
                'r.floor_number', 'r.department_id', 'r.accommodation_type',
                'r.status', 'r.bed_capacity', 'r.bed_names',
            ])
            ->orderBy('r.room_number', 'ASC');

        if ($this->db->tableExists('room_type')) {
            $builder->select('rt.type_name')->join('room_type rt', 'rt.room_type_id = r.room_type_id', 'left');
        }
        if ($this->db->tableExists('department')) {
            $builder->select('d.name as department_name')->join('department d', 'd.department_id = r.department_id', 'left');
        }

        return $builder->get()->getResultArray();
    }

    public function getRoomById(int $roomId): ?array
    {
        if ($roomId <= 0 || ! $this->db->tableExists('room')) {
            return null;
        }

        $builder = $this->db->table('room r')
            ->select([
                'r.room_id', 'r.room_number', 'r.room_type', 'r.room_type_id',
                'r.floor_number', 'r.department_id', 'r.accommodation_type',
                'r.status', 'r.bed_capacity', 'r.bed_names',
                'r.created_at', 'r.updated_at',
            ])
            ->where('r.room_id', $roomId);

        if ($this->db->tableExists('room_type')) {
            $builder->select('rt.type_name, rt.description as room_type_description')
                ->join('room_type rt', 'rt.room_type_id = r.room_type_id', 'left');
        }
        if ($this->db->tableExists('department')) {
            $builder->select('d.name as department_name, d.code as department_code')
                ->join('department d', 'd.department_id = r.department_id', 'left');
        }

        $room = $builder->get()->getRowArray();

        // Parse bed_names JSON if it exists
        if ($room && !empty($room['bed_names'])) {
            $decoded = json_decode($room['bed_names'], true);
            if (is_array($decoded)) {
                $room['bed_names'] = $decoded;
            }
        }

        return $room ?: null;
    }

    public function createRoom(array $input): array
    {
        $builder = $this->db->table('room');

        try {
            // Validate required fields
            if (empty($input['room_number'] ?? '')) {
                return ['success' => false, 'message' => 'Room number is required'];
            }

            $roomTypeId = $this->resolveRoomTypeId($input);
            $data       = $this->mapRoomPayload($input, $roomTypeId);

            // Validate room_number is not empty after mapping
            if (empty($data['room_number'] ?? '')) {
                return ['success' => false, 'message' => 'Room number cannot be empty'];
            }

            // Check for duplicate room_number before starting transaction
            $existing = $builder->where('room_number', $data['room_number'])->countAllResults();
            if ($existing > 0) {
                return ['success' => false, 'message' => 'Room number already exists: ' . $data['room_number']];
            }

            $this->db->transStart();

            $builder->insert($data);
            $roomId = (int) $this->db->insertID();

            if ($roomId <= 0) {
                $this->db->transRollback();
                return ['success' => false, 'message' => 'Failed to insert room - no ID returned'];
            }

            if ($roomId > 0) {
                $this->syncBedsForRoom($roomId, $data['bed_capacity'] ?? 0, $data['bed_names'] ?? null);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                $error = $this->db->error();
                $errorMessage = $error['message'] ?? 'Unknown database error';
                log_message('error', 'RoomService::createRoom transaction failed: ' . $errorMessage);
                throw new \RuntimeException('Failed to create room: ' . $errorMessage);
            }

            return ['success' => true, 'message' => 'Room added successfully', 'id' => $roomId];
        } catch (\Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            $error = $this->db->error();
            $dbError = $error['message'] ?? '';
            $errorMessage = $e->getMessage();
            if ($dbError && $dbError !== '') {
                $errorMessage .= ' (DB: ' . $dbError . ')';
            }
            log_message('error', 'RoomService::createRoom failed: ' . $errorMessage);
            return ['success' => false, 'message' => 'Could not create room: ' . $errorMessage];
        }
    }

    public function dischargeRoom(int $roomId, ?int $staffId = null): array
    {
        if ($roomId <= 0) {
            return ['success' => false, 'message' => 'Invalid room ID'];
        }

        if (!$this->db->tableExists('room')) {
            return ['success' => false, 'message' => 'Room table is missing'];
        }

        // Check both room_assignment and inpatient_room_assignments tables
        $assignment = null;
        $assignmentType = null;

        // First check room_assignment table
        if ($this->db->tableExists('room_assignment')) {
            $assignment = $this->db->table('room_assignment')
                ->where('room_id', $roomId)
                ->where('status', 'active')
                ->orderBy('assignment_id', 'DESC')
                ->get()
                ->getRowArray();
            
            if ($assignment) {
                $assignmentType = 'room_assignment';
            }
        }

        // If not found, check inpatient_room_assignments table
        if (!$assignment && $this->db->tableExists('inpatient_room_assignments')) {
            $inpatientBuilder = $this->db->table('inpatient_room_assignments ira')
                ->select('ira.*, ia.patient_id, ia.admission_id')
                ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'inner')
                ->where('ira.room_id', $roomId);
            
            // Check for discharge column - try different possible column names
            if ($this->db->fieldExists('discharge_datetime', 'inpatient_admissions')) {
                $inpatientBuilder->where('ia.discharge_datetime IS NULL', null, false);
            } elseif ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                $inpatientBuilder->groupStart()
                    ->where('ia.discharge_date IS NULL', null, false)
                    ->orWhere('ia.discharge_date', '')
                ->groupEnd();
            } elseif ($this->db->fieldExists('status', 'inpatient_admissions')) {
                $inpatientBuilder->where('ia.status', 'active');
            }
            // If no discharge/status column exists, just get the most recent (assume active)
            
            $inpatientAssignment = $inpatientBuilder
                ->orderBy('ira.room_assignment_id', 'DESC')
                ->get()
                ->getRowArray();
            
            if ($inpatientAssignment) {
                $assignment = $inpatientAssignment;
                $assignmentType = 'inpatient_room_assignments';
            }
        }

        if (!$assignment) {
            return ['success' => false, 'message' => 'No active room assignment found for this room'];
        }

        $now = new \DateTime();
        
        // Handle room_assignment table
        if ($assignmentType === 'room_assignment') {
            try {
                $dateIn = new \DateTime($assignment['date_in']);
            } catch (\Throwable $e) {
                $dateIn = clone $now;
            }

            $interval = $dateIn->diff($now);
            $totalDays = max(1, (int) $interval->days);
            $totalHours = $totalDays * 24 + (int) $interval->h + (int) floor($interval->i / 60);

            $updatePayload = [
                'date_out'    => $now->format('Y-m-d H:i:s'),
                'total_days'  => $totalDays,
                'total_hours' => $totalHours,
                'status'      => 'completed',
            ];

            if ($this->db->fieldExists('updated_at', 'room_assignment')) {
                $updatePayload['updated_at'] = $now->format('Y-m-d H:i:s');
            }

            try {
                $this->db->transStart();

                $this->db->table('room_assignment')
                    ->where('assignment_id', $assignment['assignment_id'])
                    ->update($updatePayload);

                $this->db->table('room')
                    ->where('room_id', $roomId)
                    ->update(['status' => 'available']);

                $this->db->transComplete();

                if ($this->db->transStatus() === false) {
                    throw new \RuntimeException('Failed to discharge room in transaction');
                }

                return [
                    'success' => true,
                    'message' => 'Room discharged successfully',
                    'assignment_id' => (int) $assignment['assignment_id'],
                    'patient_id' => (int) ($assignment['patient_id'] ?? 0),
                    'admission_id' => isset($assignment['admission_id']) ? (int) $assignment['admission_id'] : null,
                ];
            } catch (\Throwable $e) {
                $this->db->transRollback();
                log_message('error', 'RoomService::dischargeRoom failed: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Could not discharge room: ' . $e->getMessage()];
            }
        }

        // Handle inpatient_room_assignments - just update room status
        // The actual discharge should be handled through admission discharge
        if ($assignmentType === 'inpatient_room_assignments') {
            try {
                $this->db->table('room')
                    ->where('room_id', $roomId)
                    ->update(['status' => 'available']);

                return [
                    'success' => true,
                    'message' => 'Room status updated to available',
                    'assignment_id' => (int) ($assignment['room_assignment_id'] ?? 0),
                    'patient_id' => (int) ($assignment['patient_id'] ?? 0),
                    'admission_id' => isset($assignment['admission_id']) ? (int) $assignment['admission_id'] : null,
                ];
            } catch (\Throwable $e) {
                log_message('error', 'RoomService::dischargeRoom (inpatient) failed: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Could not update room status: ' . $e->getMessage()];
            }
        }

        return ['success' => false, 'message' => 'Unknown assignment type'];
    }

    /**
     * Update room status to available when patient is discharged
     * This is called when an inpatient admission is discharged
     */
    public function freeRoomForAdmission(int $admissionId): bool
    {
        if (!$this->db->tableExists('inpatient_room_assignments') || !$this->db->tableExists('room')) {
            return false;
        }

        try {
            // Get room assignments for this admission
            $assignments = $this->db->table('inpatient_room_assignments')
                ->where('admission_id', $admissionId)
                ->where('room_id IS NOT NULL', null, false)
                ->get()
                ->getResultArray();

            foreach ($assignments as $assignment) {
                $roomId = (int) ($assignment['room_id'] ?? 0);
                if ($roomId > 0) {
                    // Check if there are other active assignments for this room
                    $otherBuilder = $this->db->table('inpatient_room_assignments ira')
                        ->select('ira.room_assignment_id')
                        ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'inner')
                        ->where('ira.room_id', $roomId)
                        ->where('ira.room_assignment_id !=', $assignment['room_assignment_id']);
                    
                    // Check for discharge column - try different possible column names
                    if ($this->db->fieldExists('discharge_datetime', 'inpatient_admissions')) {
                        $otherBuilder->where('ia.discharge_datetime IS NULL', null, false);
                    } elseif ($this->db->fieldExists('discharge_date', 'inpatient_admissions')) {
                        $otherBuilder->groupStart()
                            ->where('ia.discharge_date IS NULL', null, false)
                            ->orWhere('ia.discharge_date', '')
                        ->groupEnd();
                    } elseif ($this->db->fieldExists('status', 'inpatient_admissions')) {
                        $otherBuilder->where('ia.status', 'active');
                    }
                    // If no discharge/status column exists, check all assignments
                    
                    $otherAssignments = $otherBuilder->countAllResults();

                    // Only set to available if no other active assignments
                    if ($otherAssignments === 0) {
                        $this->db->table('room')
                            ->where('room_id', $roomId)
                            ->update(['status' => 'available']);
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            log_message('error', 'RoomService::freeRoomForAdmission failed: ' . $e->getMessage());
            return false;
        }
    }

    public function updateRoom(int $roomId, array $input): array
    {
        if ($roomId <= 0) {
            return ['success' => false, 'message' => 'Invalid room ID provided'];
        }

        try {
            $roomTypeId = $this->resolveRoomTypeId($input);
            $data       = $this->mapRoomPayload($input, $roomTypeId);

            $this->db->transStart();

            $updated = $this->db->table('room')
                ->where('room_id', $roomId)
                ->update($data);

            if ($updated) {
                $this->syncBedsForRoom($roomId, $data['bed_capacity'] ?? 0, $data['bed_names'] ?? null);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false || ! $updated) {
                throw new \RuntimeException('Failed to update room and beds');
            }

            return ['success' => true, 'message' => 'Room updated successfully'];
        } catch (\Throwable $e) {
            log_message('error', 'RoomService::updateRoom failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not update room: ' . $e->getMessage()];
        }
    }

    public function deleteRoom(int $roomId): array
    {
        if ($roomId <= 0) {
            return ['success' => false, 'message' => 'Invalid room ID provided'];
        }

        try {
            $this->db->table('room')->where('room_id', $roomId)->delete();
            $deleted = $this->db->affectedRows() > 0;

            return $deleted
                ? ['success' => true, 'message' => 'Room deleted successfully']
                : ['success' => false, 'message' => 'Room not found or already deleted'];
        } catch (\Throwable $e) {
            log_message('error', 'RoomService::deleteRoom failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not delete room: ' . $e->getMessage()];
        }
    }

    private function mapRoomPayload(array $input, ?int $roomTypeId = null): array
    {
        $rawBedNames = $input['bed_names'] ?? [];
        $normalizedBedNames = [];

        if (is_array($rawBedNames)) {
            foreach ($rawBedNames as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $normalizedBedNames[] = $name;
                }
            }
        } elseif (is_string($rawBedNames) && $rawBedNames !== '') {
            // Fallback if a single string was submitted instead of an array
            $normalizedBedNames[] = trim($rawBedNames);
        }

        $bedNamesValue = $normalizedBedNames ? json_encode($normalizedBedNames) : null;

        return [
            'room_number' => trim($input['room_number'] ?? ''),
            'room_type' => trim($input['room_name'] ?? ''),
            'room_type_id' => $roomTypeId,
            'floor_number' => trim($input['floor_number'] ?? ''),
            'department_id' => !empty($input['department_id']) ? (int) $input['department_id'] : null,
            'accommodation_type' => $this->sanitizeString($input['accommodation_type'] ?? null),
            'bed_capacity' => !empty($input['bed_capacity']) ? (int) $input['bed_capacity'] : 1,
            'bed_names' => $bedNamesValue,
            'status' => $input['status'] ?? 'available',
        ];
    }

    /**
     * Normalize beds for a given room into the `bed` table based on capacity and names.
     */
    private function syncBedsForRoom(int $roomId, int $capacity, ?string $bedNamesJson = null): void
    {
        if ($roomId <= 0 || ! $this->db->tableExists('bed')) {
            return;
        }

        $capacity = max(1, (int) $capacity);

        $bedNames = [];
        if ($bedNamesJson) {
            $decoded = json_decode($bedNamesJson, true) ?? [];
            if (is_array($decoded)) {
                $bedNames = array_values(array_filter(array_map('strval', $decoded), static fn($n) => trim($n) !== ''));
            }
        }

        // Remove existing beds for this room and recreate based on current definition.
        $bedTable = $this->db->table('bed');
        $bedTable->where('room_id', $roomId)->delete();

        $rows = [];
        for ($i = 0; $i < $capacity; $i++) {
            $label = $bedNames[$i] ?? 'Bed ' . ($i + 1);
            $rows[] = [
                'room_id'            => $roomId,
                'bed_number'         => $label,
                'status'             => 'available',
                'assigned_patient_id'=> null,
            ];
        }

        if (! empty($rows)) {
            $bedTable->insertBatch($rows);
        }
    }

    private function resolveRoomTypeId(array $input): ?int
    {
        if (!empty($input['room_type_id'])) {
            $typeId = (int) $input['room_type_id'];
            // Validate that the room_type_id exists if provided
            if ($typeId > 0 && $this->db->tableExists('room_type')) {
                $exists = $this->db->table('room_type')
                    ->where('room_type_id', $typeId)
                    ->countAllResults() > 0;
                if (!$exists) {
                    throw new \RuntimeException('Invalid room_type_id: ' . $typeId . ' does not exist');
                }
            }
            return $typeId;
        }

        $customType = trim($input['custom_room_type'] ?? '');
        if ($customType === '') {
            return null;
        }

        if (! $this->db->tableExists('room_type')) {
            throw new \RuntimeException('Room type table does not exist. Please run migrations first.');
        }

        $roomTypeTable = $this->db->table('room_type');
        $existing = $roomTypeTable
            ->select('room_type_id')
            ->where('type_name', $customType)
            ->get()
            ->getRowArray();

        if ($existing) {
            return (int) $existing['room_type_id'];
        }

        // Create new room type
        try {
            $payload = $this->buildRoomTypePayload($customType, $input);
            $roomTypeTable->insert($payload);
            $newTypeId = (int) $this->db->insertID();
            
            if ($newTypeId <= 0) {
                $error = $this->db->error();
                $errorMsg = $error['message'] ?? 'Unknown error';
                throw new \RuntimeException('Failed to create room type: ' . $errorMsg);
            }
            
            return $newTypeId;
        } catch (\Throwable $e) {
            $error = $this->db->error();
            $dbError = $error['message'] ?? '';
            if (strpos($dbError, 'Duplicate entry') !== false || strpos($dbError, 'UNIQUE constraint') !== false) {
                // Room type was created between check and insert, try to get it again
                $existing = $roomTypeTable
                    ->select('room_type_id')
                    ->where('type_name', $customType)
                    ->get()
                    ->getRowArray();
                if ($existing) {
                    return (int) $existing['room_type_id'];
                }
            }
            throw new \RuntimeException('Failed to create room type "' . $customType . '": ' . ($dbError ?: $e->getMessage()));
        }
    }

    private function buildRoomTypePayload(string $typeName, array $input): array
    {
        $notes = trim($input['notes'] ?? '');
        $accommodationType = $this->sanitizeString($input['accommodation_type'] ?? null, 100);

        return [
            'type_name' => $typeName,
            'description' => $notes ?: null,
            'accommodation_type' => $accommodationType,
        ];
    }

    private function sanitizeDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function sanitizeString(?string $value, ?int $maxLength = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(strip_tags($value));
        if ($clean === '') {
            return null;
        }

        if ($maxLength !== null) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return $clean;
    }
}

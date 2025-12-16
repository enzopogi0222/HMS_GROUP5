<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\RoomService;
use App\Services\FinancialService;
use CodeIgniter\Database\ConnectionInterface;

class RoomManagement extends BaseController
{
    protected RoomService $roomService;
    protected FinancialService $financialService;
    protected ConnectionInterface $db;

    public function __construct()
    {
        $this->roomService = new RoomService();
        $this->financialService = new FinancialService();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $rooms = [];
        $roomInventory = [];
        if ($this->db->tableExists('room')) {
            $rooms = $this->roomService->getRooms();
        }

        // Build room inventory by room type (similar to patient management)
        foreach ($rooms as $room) {
            $typeId = (int) ($room['room_type_id'] ?? 0);
            if (!$typeId) {
                continue;
            }

            $bedNames = [];
            if (!empty($room['bed_names'])) {
                $decoded = json_decode((string) $room['bed_names'], true);
                if (is_array($decoded)) {
                    $bedNames = array_values($decoded);
                }
            }

            $roomInventory[$typeId][] = [
                'room_id'       => (int) ($room['room_id'] ?? 0),
                'room_number'   => (string) ($room['room_number'] ?? ''),
                'room_name'     => (string) ($room['type_name'] ?? ''),
                'floor_number'  => (string) ($room['floor_number'] ?? ''),
                'department_id' => (int) ($room['department_id'] ?? 0),
                'status'        => (string) ($room['status'] ?? ''),
                'bed_capacity'  => (int) ($room['bed_capacity'] ?? 0),
                'bed_names'     => $bedNames,
            ];
        }

        return view('unified/room-management', [
            'title' => 'Room Management',
            'roomStats' => $this->roomService->getRoomStats(),
            'roomTypes' => $this->getRoomTypes(),
            'departments' => $this->getDepartments(),
            'roomTypeMetadata' => [],
            'roomInventory' => $roomInventory,
        ]);
    }

    private function getRoomTypes(): array
    {
        if (!$this->db->tableExists('room_type')) {
            return [];
        }
        $builder = $this->db->table('room_type')
            ->select('room_type_id, type_name');
        
        if ($this->db->fieldExists('base_daily_rate', 'room_type')) {
            $builder->select('base_daily_rate');
        }
        
        return $builder->orderBy('type_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getDepartments(): array
    {
        if (!$this->db->tableExists('department')) {
            return [];
        }
        if ($this->db->tableExists('medical_department')) {
            return $this->db->table('department d')
                ->select('d.department_id, d.name, d.floor')
                ->join('medical_department md', 'md.department_id = d.department_id', 'inner')
                ->orderBy('d.name', 'ASC')
                ->get()
                ->getResultArray();
        }

        $builder = $this->db->table('department')
            ->select('department_id, name, floor');

        if ($this->db->fieldExists('type', 'department')) {
            $builder->whereIn('type', ['Medical', 'medical', 'MEDICAL']);
        }

        return $builder
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getRoomsAPI()
    {
        return $this->jsonResponse(['status' => 'success', 'data' => $this->roomService->getRooms()]);
    }

    public function getRoomTypesAPI()
    {
        return $this->jsonResponse($this->appendCsrfHash([
            'success' => true,
            'status'  => 'success',
            'data'    => $this->getRoomTypes(),
        ]));
    }

    public function getRoom(int $roomId)
    {
        $room = $this->roomService->getRoomById($roomId);
        
        if (!$room) {
            return $this->jsonResponse($this->appendCsrfHash([
                'success' => false,
                'message' => 'Room not found'
            ]), 404);
        }

        return $this->jsonResponse($this->appendCsrfHash([
            'success' => true,
            'status' => 'success',
            'data' => $room
        ]));
    }

    public function createRoom()
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse(['status' => 'error', 'message' => 'Method not allowed'], 405);
        }

        $result = $this->roomService->createRoom($this->getRequestData());
        return $this->jsonResponse($this->appendCsrfHash($result), $result['success'] ? 200 : 400);
    }

    public function dischargeRoom()
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Method not allowed']), 405);
        }

        $input = $this->getRequestData();
        if (!$this->validateCsrf($input)) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Invalid CSRF token']), 403);
        }

        $roomId = (int) ($input['room_id'] ?? 0);
        $staffId = (int) (session()->get('staff_id') ?? 0);
        $dischargeResult = $this->roomService->dischargeRoom($roomId, $staffId);

        if (!$dischargeResult['success']) {
            return $this->jsonResponse($this->appendCsrfHash($dischargeResult), 400);
        }

        $assignmentId = (int) ($dischargeResult['assignment_id'] ?? 0);
        $patientId    = (int) ($dischargeResult['patient_id'] ?? 0);
        $admissionId  = $dischargeResult['admission_id'] ?? null;
        $assignmentType = $dischargeResult['assignment_type'] ?? 'room_assignment';

        $billingMessage = null;
        if ($assignmentId > 0 && $patientId > 0) {
            $account = $this->financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);

            if ($account && ! empty($account['billing_id'])) {
                $billingId = (int) $account['billing_id'];
                
                // Use the appropriate billing method based on assignment type
                if ($assignmentType === 'inpatient_room_assignments') {
                    // Calculate days stayed for inpatient assignment
                    $daysStayed = 1; // Default to 1 day, can be calculated from assigned_at if needed
                    $billingResult = $this->financialService->addItemFromInpatientRoomAssignment($billingId, $assignmentId, null, $staffId, $daysStayed);
                } else {
                    // Use regular room_assignment table
                    $billingResult = $this->financialService->addItemFromRoomAssignment($billingId, $assignmentId, null, $staffId);
                }

                if (! empty($billingResult['success'])) {
                    $billingMessage = 'Room stay added to billing account.';
                } else {
                    $billingMessage = $billingResult['message'] ?? 'Room discharged but could not add to billing.';
                }
            } else {
                $billingMessage = 'Room discharged but no billing account could be created.';
            }
        }

        $payload = $dischargeResult;
        if ($billingMessage) {
            $payload['billing_message'] = $billingMessage;
        }

        return $this->jsonResponse($this->appendCsrfHash($payload));
    }

    public function updateRoom(int $roomId)
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Method not allowed']), 405);
        }

        $result = $this->roomService->updateRoom($roomId, $this->getRequestData());
        return $this->jsonResponse($this->appendCsrfHash($result), $result['success'] ? 200 : 400);
    }

    public function deleteRoom(int $roomId)
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Method not allowed']), 405);
        }

        $input = $this->getRequestData();
        if (!$this->validateCsrf($input)) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Invalid CSRF token']), 403);
        }

        $result = $this->roomService->deleteRoom($roomId);
        return $this->jsonResponse($this->appendCsrfHash($result), $result['success'] ? 200 : 400);
    }

    private function getRequestData(): array
    {
        $input = $this->request->getPost();
        if (empty($input)) {
            $jsonBody = $this->request->getJSON(true);
            $input = is_array($jsonBody) ? $jsonBody : [];
        }
        return $input;
    }

    private function validateCsrf(array $input): bool
    {
        $tokenName = csrf_token();
        return isset($input[$tokenName]) && $input[$tokenName] === csrf_hash();
    }

    private function jsonResponse(array $data, int $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON($data);
    }

    private function appendCsrfHash(array $payload): array
    {
        $payload['csrf_hash'] = csrf_hash();
        return $payload;
    }

    /**
     * Get available patients for room assignment
     */
    public function getPatientsForAssignment()
    {
        try {
            $patientService = new \App\Services\PatientService();
            $patients = $patientService->getPatientsByRole('admin', null); // Get all patients for admin

            // Filter: only include inpatients for room assignment
            $filteredPatients = array_filter($patients, function($p) {
                $type = strtolower(trim($p['patient_type'] ?? ''));
                return $type === 'inpatient';
            });

            // Format for dropdown
            $formattedPatients = array_map(function($p) {
                return [
                    'patient_id' => (int) ($p['patient_id'] ?? $p['id'] ?? 0),
                    'first_name' => $p['first_name'] ?? '',
                    'last_name' => $p['last_name'] ?? '',
                    'full_name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
                    'patient_type' => $p['patient_type'] ?? 'Outpatient',
                ];
            }, array_values($filteredPatients));

            return $this->jsonResponse($this->appendCsrfHash([
                'status' => 'success',
                'data' => $formattedPatients
            ]));
        } catch (\Throwable $e) {
            log_message('error', 'Failed to get patients for assignment: ' . $e->getMessage());
            return $this->jsonResponse($this->appendCsrfHash([
                'status' => 'error',
                'message' => 'Failed to load patients',
                'data' => []
            ]), 500);
        }
    }

    /**
     * Assign patient to room
     */
    public function assignRoom()
    {
        if (!$this->request->is('post')) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Method not allowed']), 405);
        }

        $input = $this->getRequestData();
        if (!$this->validateCsrf($input)) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Invalid CSRF token']), 403);
        }

        $patientId = (int) ($input['patient_id'] ?? 0);
        $departmentId = (int) ($input['department_id'] ?? 0);
        $roomType = $input['room_type'] ?? null;
        $floorNumber = $input['floor_number'] ?? null;
        $roomNumber = $input['room_number'] ?? null;
        $bedNumber = $input['bed_number'] ?? null;
        $assignedAt = $input['assigned_at'] ?? date('Y-m-d H:i:s');
        $dailyRate = $input['daily_rate'] ?? null;

        if (!$patientId) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Patient ID is required']), 400);
        }

        if (!$departmentId) {
            return $this->jsonResponse($this->appendCsrfHash([
                'success' => false,
                'message' => 'Department is required'
            ]), 400);
        }

        if (!$floorNumber || !$roomNumber || !$bedNumber) {
            return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Floor, room number, and bed number are required']), 400);
        }

        try {
            // Start transaction for data consistency
            $this->db->transStart();
            
            // Get room_id from room_number
            $room = $this->db->table('room')
                ->where('room_number', $roomNumber)
                ->where('floor_number', $floorNumber)
                ->get()
                ->getRowArray();

            if (!$room) {
                $this->db->transRollback();
                return $this->jsonResponse($this->appendCsrfHash(['success' => false, 'message' => 'Room not found']), 404);
            }

            $roomId = (int) $room['room_id'];
            
            // Check if the new room is available
            if (strtolower($room['status'] ?? '') === 'occupied') {
                // Check if it's the same patient trying to assign to the same room
                $currentAssignment = null;
                if ($this->db->tableExists('inpatient_room_assignments')) {
                    $currentAssignment = $this->db->table('inpatient_room_assignments ira')
                        ->select('ira.*, ia.patient_id')
                        ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'inner')
                        ->where('ira.room_id', $roomId)
                        ->where('ia.patient_id', $patientId)
                        ->get()
                        ->getRowArray();
                }
                
                if (!$currentAssignment) {
                    $this->db->transRollback();
                    return $this->jsonResponse($this->appendCsrfHash([
                        'success' => false, 
                        'message' => 'Room is already occupied by another patient'
                    ]), 400);
                }
            }

            // Check if patient already has an active room assignment and transfer them
            $oldRoomId = null;
            $oldBedNumber = null;
            if ($this->db->tableExists('inpatient_room_assignments')) {
                $existingAssignment = $this->db->table('inpatient_room_assignments ira')
                    ->select('ira.*, ia.patient_id, ia.admission_id')
                    ->join('inpatient_admissions ia', 'ia.admission_id = ira.admission_id', 'inner')
                    ->where('ia.patient_id', $patientId);
                
                if ($this->db->fieldExists('discharge_datetime', 'inpatient_admissions')) {
                    $existingAssignment->where('ia.discharge_datetime IS NULL', null, false);
                } elseif ($this->db->fieldExists('status', 'inpatient_admissions')) {
                    $existingAssignment->where('ia.status', 'active');
                }
                
                $existing = $existingAssignment->orderBy('ira.room_assignment_id', 'DESC')->get()->getRowArray();
                if ($existing) {
                    // Patient has an existing assignment - transfer them
                    $oldRoomId = (int) ($existing['room_id'] ?? 0);
                    $oldBedNumber = $existing['bed_number'] ?? null;
                    
                    // Only transfer if it's a different room
                    if ($oldRoomId > 0 && $oldRoomId !== $roomId) {
                        // Free the old room
                        $this->db->table('room')
                            ->where('room_id', $oldRoomId)
                            ->update(['status' => 'available']);
                        
                        // Free the old bed if bed table exists
                        if ($this->db->tableExists('bed') && $oldBedNumber) {
                            $this->db->table('bed')
                                ->where('room_id', $oldRoomId)
                                ->where('bed_number', $oldBedNumber)
                                ->update([
                                    'status' => 'available',
                                    'assigned_patient_id' => null
                                ]);
                        }
                    }
                }
            }
            
            // Also check room_assignment table for legacy assignments
            if ($this->db->tableExists('room_assignment')) {
                $legacyAssignment = $this->db->table('room_assignment')
                    ->where('patient_id', $patientId)
                    ->where('status', 'active')
                    ->orderBy('assignment_id', 'DESC')
                    ->get()
                    ->getRowArray();
                
                if ($legacyAssignment) {
                    $oldRoomId = (int) ($legacyAssignment['room_id'] ?? 0);
                    $oldBedId = (int) ($legacyAssignment['bed_id'] ?? 0);
                    
                    // Only transfer if it's a different room
                    if ($oldRoomId > 0 && $oldRoomId !== $roomId) {
                        // Free the old room
                        $this->db->table('room')
                            ->where('room_id', $oldRoomId)
                            ->update(['status' => 'available']);
                        
                        // Free the old bed if bed table exists
                        if ($this->db->tableExists('bed') && $oldBedId > 0) {
                            $this->db->table('bed')
                                ->where('bed_id', $oldBedId)
                                ->update([
                                    'status' => 'available',
                                    'assigned_patient_id' => null
                                ]);
                        }
                    }
                }
            }

            // Get or create admission record for this patient
            $admissionId = null;
            if ($this->db->tableExists('inpatient_admissions')) {
                $admission = $this->db->table('inpatient_admissions')
                    ->where('patient_id', $patientId);
                
                if ($this->db->fieldExists('discharge_datetime', 'inpatient_admissions')) {
                    $admission->where('discharge_datetime IS NULL', null, false);
                } elseif ($this->db->fieldExists('status', 'inpatient_admissions')) {
                    $admission->where('status', 'active');
                }
                
                $admission = $admission->orderBy('admission_id', 'DESC')->get()->getRowArray();
                
                if ($admission) {
                    $admissionId = (int) $admission['admission_id'];
                } else {
                    // Create a new admission record if none exists
                    $admissionData = [
                        'patient_id' => $patientId,
                        'admission_datetime' => $assignedAt,
                        'admission_type' => 'Scheduled',
                        'admitting_diagnosis' => 'Room assignment',
                    ];
                    
                    // Only include fields that exist in the table
                    $admissionColumns = $this->db->getFieldNames('inpatient_admissions');
                    $admissionData = array_intersect_key($admissionData, array_flip($admissionColumns));
                    
                    $this->db->table('inpatient_admissions')->insert($admissionData);
                    $admissionId = (int) $this->db->insertID();
                }
            }

            // Calculate daily rate if not provided
            if (!$dailyRate || $dailyRate === 'Auto-calculated' || $dailyRate === '0' || (float)$dailyRate <= 0) {
                $dailyRate = null;
                
                if ($this->db->tableExists('room_type') && $roomType) {
                    // Try to find by room_type_id first
                    $roomTypeData = $this->db->table('room_type')
                        ->where('room_type_id', (int)$roomType)
                        ->get()
                        ->getRowArray();
                    
                    // If not found by ID, try to get from the room's room_type_id
                    if (!$roomTypeData && isset($room['room_type_id'])) {
                        $roomTypeData = $this->db->table('room_type')
                            ->where('room_type_id', (int)$room['room_type_id'])
                            ->get()
                            ->getRowArray();
                    }
                    
                    if ($roomTypeData) {
                        // Check if base_daily_rate field exists
                        if ($this->db->fieldExists('base_daily_rate', 'room_type')) {
                            $dailyRate = (float)($roomTypeData['base_daily_rate'] ?? 0);
                        }
                    }
                }
                
                // If still no rate, try to get from room's room_type_id directly
                if ((!$dailyRate || $dailyRate <= 0) && isset($room['room_type_id'])) {
                    $roomTypeData = $this->db->table('room_type')
                        ->where('room_type_id', (int)$room['room_type_id'])
                        ->get()
                        ->getRowArray();
                    
                    if ($roomTypeData && $this->db->fieldExists('base_daily_rate', 'room_type')) {
                        $dailyRate = (float)($roomTypeData['base_daily_rate'] ?? 0);
                    }
                }
                
                // Final validation - if still no rate, try default rates based on room type name
                if (!$dailyRate || $dailyRate <= 0) {
                    $roomTypeName = $this->getRoomTypeName($roomType) ?? $room['room_type'] ?? '';
                    $defaultRates = [
                        'ward' => 1500.00,
                        'semi-private' => 2500.00,
                        'private' => 3500.00,
                        'icu' => 5000.00,
                        'isolation' => 3000.00,
                        'emergency' => 2000.00,
                        'consultation' => 0.00,
                    ];

                    $roomTypeLower = strtolower($roomTypeName);
                    foreach ($defaultRates as $type => $rate) {
                        if (strpos($roomTypeLower, $type) !== false) {
                            $dailyRate = $rate;
                            break;
                        }
                    }
                }

                // If still no rate, allow NULL and proceed (consistent with inpatient registration flow)
                if (!$dailyRate || $dailyRate <= 0) {
                    $dailyRate = null;
                }
            } else {
                $dailyRate = (float) $dailyRate;
            }

            // Normalize room_type to the inpatient_room_assignments ENUM values.
            // Room types like Emergency/Consultation should not break the insert.
            $resolvedRoomTypeName = null;
            if ($roomType) {
                $resolvedRoomTypeName = is_numeric($roomType)
                    ? $this->getRoomTypeName((int) $roomType)
                    : (string) $roomType;
            }

            if (!$resolvedRoomTypeName && isset($room['room_type_id'])) {
                $resolvedRoomTypeName = $this->getRoomTypeName((int) $room['room_type_id']);
            }

            $normalizedRoomType = null;
            if ($resolvedRoomTypeName) {
                $resolvedRoomTypeName = trim((string) $resolvedRoomTypeName);
                if (in_array($resolvedRoomTypeName, ['Ward', 'Semi-Private', 'Private', 'Isolation', 'ICU'], true)) {
                    $normalizedRoomType = $resolvedRoomTypeName;
                } else {
                    $roomTypeMap = [
                        'ward' => 'Ward',
                        'semi-private' => 'Semi-Private',
                        'semi_private' => 'Semi-Private',
                        'private' => 'Private',
                        'isolation' => 'Isolation',
                        'icu' => 'ICU',
                    ];
                    $normalizedRoomType = $roomTypeMap[strtolower($resolvedRoomTypeName)] ?? null;
                }
            }

            // Create room assignment
            if ($this->db->tableExists('inpatient_room_assignments')) {
                $assignmentData = [
                    'admission_id' => $admissionId,
                    'room_id' => $roomId,
                    'room_type' => $normalizedRoomType,
                    'floor_number' => $floorNumber,
                    'room_number' => $roomNumber,
                    'bed_number' => $bedNumber,
                    'daily_rate' => $dailyRate,
                    'assigned_at' => $assignedAt,
                ];

                $this->db->table('inpatient_room_assignments')->insert($assignmentData);

                // Also store in legacy room_assignment table (for reporting/compat)
                if ($this->db->tableExists('room_assignment')) {
                    $legacyBedId = null;
                    if ($this->db->tableExists('bed')) {
                        $bedRow = $this->db->table('bed')
                            ->select('bed_id')
                            ->where('room_id', $roomId)
                            ->where('bed_number', $bedNumber)
                            ->get()
                            ->getRowArray();
                        if ($bedRow && isset($bedRow['bed_id'])) {
                            $legacyBedId = (int) $bedRow['bed_id'];
                        }
                    }

                    $legacyPayload = [
                        'patient_id' => $patientId,
                        'room_id' => $roomId,
                        'bed_id' => $legacyBedId,
                        'admission_id' => $admissionId,
                        'date_in' => $assignedAt,
                        'date_out' => null,
                        'total_days' => null,
                        'status' => 'active',
                    ];

                    // 1 active row per admission_id (update if exists; insert otherwise)
                    $existingLegacyRow = $this->db->table('room_assignment')
                        ->select('assignment_id')
                        ->where('admission_id', $admissionId)
                        ->where('status', 'active')
                        ->orderBy('assignment_id', 'DESC')
                        ->get()
                        ->getRowArray();

                    if ($existingLegacyRow && isset($existingLegacyRow['assignment_id'])) {
                        $this->db->table('room_assignment')
                            ->where('assignment_id', (int) $existingLegacyRow['assignment_id'])
                            ->update($legacyPayload);
                    } else {
                        $this->db->table('room_assignment')->insert($legacyPayload);
                    }
                }
            }

            // Update room status to occupied
            $this->db->table('room')
                ->where('room_id', $roomId)
                ->update(['status' => 'occupied']);

            // Update bed status if bed table exists
            if ($this->db->tableExists('bed')) {
                $this->db->table('bed')
                    ->where('room_id', $roomId)
                    ->where('bed_number', $bedNumber)
                    ->update([
                        'status' => 'occupied',
                        'assigned_patient_id' => $patientId
                    ]);
            }

            // Complete transaction
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed during room assignment');
            }
            
            $message = 'Room assigned successfully';
            if ($oldRoomId && $oldRoomId !== $roomId) {
                $message = 'Patient transferred to new room successfully';
            }
            
            return $this->jsonResponse($this->appendCsrfHash([
                'success' => true,
                'message' => $message,
                'admission_id' => $admissionId,
                'room_id' => $roomId,
                'transferred_from_room_id' => $oldRoomId && $oldRoomId !== $roomId ? $oldRoomId : null
            ]));
        } catch (\Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            log_message('error', 'Failed to assign room: ' . $e->getMessage());
            return $this->jsonResponse($this->appendCsrfHash([
                'success' => false,
                'message' => 'Failed to assign room: ' . $e->getMessage()
            ]), 500);
        }
    }

    private function getRoomTypeName($roomTypeId): ?string
    {
        if (!$roomTypeId || !$this->db->tableExists('room_type')) {
            return null;
        }
        $type = $this->db->table('room_type')
            ->where('room_type_id', $roomTypeId)
            ->get()
            ->getRowArray();
        return $type['type_name'] ?? null;
    }
}

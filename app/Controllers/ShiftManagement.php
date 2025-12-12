<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class ShiftManagement extends BaseController
{
    protected $userRole;
    protected $staffId;

    /**
     * Initialize controller with session data
     */
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        try {
            $session = session();
            $this->userRole = $session->get('role') ?? $session->get('user_role') ?: 'admin';
            $this->staffId = $session->get('staff_id') ?? $session->get('id') ?? 1;
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::initController error: ' . $e->getMessage());
            $this->userRole = 'admin';
            $this->staffId = 1;
        }
    }

    /**
     * Main shift management view - role-based
     */
    public function index()
    {
        try {
            $pageConfig = $this->getPageConfig();
            $data = [
                'title' => $pageConfig['title'],
                'shifts' => $this->getMockShifts(),
                'stats' => $this->getMockStats(),
                'availableStaff' => $this->getMockAvailableStaff(),
                'userRole' => $this->userRole,
                'permissions' => $this->getUserPermissions(),
                'pageConfig' => $pageConfig,
                'departments' => $this->getDepartments(),
                'shiftTypes' => $this->getShiftTypes(),
                'roomsWards' => $this->getRoomsWards(),
                'availableDoctors' => $this->getAvailableDoctors()
            ];
            return view('unified/shift-management', $data);
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::index error: ' . $e->getMessage());
            return $this->response->setBody('<h1>Shift Management Error</h1><p>' . esc($e->getMessage()) . '</p><p><a href="' . base_url('dashboard') . '">Back to Dashboard</a></p>')->setStatusCode(500);
        }
    }

    /**
     * Get mock shifts data
     */
    private function getMockShifts()
    {
        return [
            [
                'id' => 1,
                'doctor_id' => 1,
                'doctor_name' => 'Dr. John Smith',
                'shift_date' => date('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'department' => 'Emergency',
                'shift_type' => 'Morning',
                'status' => 'scheduled',
                'room_ward' => 'ER-1',
                'notes' => 'Regular shift',
                'specialization' => 'Emergency Medicine'
            ],
            [
                'id' => 2,
                'doctor_id' => 2,
                'doctor_name' => 'Dr. Jane Doe',
                'shift_date' => date('Y-m-d', strtotime('+1 day')),
                'start_time' => '14:00',
                'end_time' => '22:00',
                'department' => 'ICU',
                'shift_type' => 'Afternoon',
                'status' => 'scheduled',
                'room_ward' => 'ICU-1',
                'notes' => 'ICU duty',
                'specialization' => 'Intensive Care'
            ],
            [
                'id' => 3,
                'doctor_id' => 3,
                'doctor_name' => 'Dr. Robert Johnson',
                'shift_date' => date('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '16:00',
                'department' => 'General',
                'shift_type' => 'Morning',
                'status' => 'active',
                'room_ward' => 'OPD-1',
                'notes' => 'General practice',
                'specialization' => 'General Medicine'
            ],
            [
                'id' => 4,
                'doctor_id' => 4,
                'doctor_name' => 'Dr. Sarah Williams',
                'shift_date' => date('Y-m-d', strtotime('+2 days')),
                'start_time' => '20:00',
                'end_time' => '04:00',
                'department' => 'Pediatrics',
                'shift_type' => 'Night',
                'status' => 'scheduled',
                'room_ward' => 'Pediatrics Ward',
                'notes' => 'Night duty',
                'specialization' => 'Pediatrics'
            ]
        ];
    }

    /**
     * Get mock stats data
     */
    private function getMockStats()
    {
        // Default fallback values (previous mock data)
        $fallback = [
            'total_shifts'      => 24,
            'scheduled_shifts'  => 18,
            'today_shifts'      => 6,
            'active_doctors'    => 8,
            'my_shifts'         => 4,
            'week_shifts'       => 12,
            'upcoming_shifts'   => 3,
            'department_shifts' => 6,
            'department'        => 'Emergency',
        ];

        try {
            $db = \Config\Database::connect();

            // Determine today's weekday (1 = Monday ... 7 = Sunday)
            $todayWeekday = (int) date('N');

            // Total active schedule entries (all doctors, all days)
            $totalSchedules = $db->table('staff_schedule')
                ->where('status', 'active')
                ->countAllResults();

            // Today's schedules (pattern for today's weekday)
            $todaySchedules = $db->table('staff_schedule')
                ->where('status', 'active')
                ->where('weekday', $todayWeekday)
                ->countAllResults();

            // Active doctors from doctor table
            $activeDoctors = $db->table('doctor')
                ->where('status', 'Active')
                ->countAllResults();

            // Current user's schedules (if staffId is set)
            $mySchedules = 0;
            if (!empty($this->staffId)) {
                $mySchedules = $db->table('staff_schedule')
                    ->where('status', 'active')
                    ->where('staff_id', $this->staffId)
                    ->countAllResults();
            }

            // For now, treat week_shifts as total active schedules,
            // and upcoming_shifts as today's schedules (pattern-based)
            $stats = [
                'total_shifts'      => $totalSchedules,
                'scheduled_shifts'  => $totalSchedules,
                'today_shifts'      => $todaySchedules,
                'active_doctors'    => $activeDoctors,
                'my_shifts'         => $mySchedules,
                'week_shifts'       => $totalSchedules,
                'upcoming_shifts'   => $todaySchedules,
                'department_shifts' => $todaySchedules,
                'department'        => 'All Departments',
            ];

            return $stats;

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getMockStats (real stats) error: ' . $e->getMessage());
            // On error, return the original mock stats so the UI still works
            return $fallback;
        }
    }

    /**
     * Get mock available staff
     */
    private function getMockAvailableStaff()
    {
        return [
            ['doctor_id' => 1, 'first_name' => 'John', 'last_name' => 'Smith', 'specialization' => 'Emergency Medicine'],
            ['doctor_id' => 2, 'first_name' => 'Jane', 'last_name' => 'Doe', 'specialization' => 'ICU'],
            ['doctor_id' => 3, 'first_name' => 'Robert', 'last_name' => 'Johnson', 'specialization' => 'General Surgery'],
            ['doctor_id' => 4, 'first_name' => 'Sarah', 'last_name' => 'Williams', 'specialization' => 'Pediatrics']
        ];
    }

    /**
     * Get shifts API - role-based filtering
     */
    public function getShiftsAPI()
    {
        try {
            $db = \Config\Database::connect();

            // For doctors and nurses viewing their own schedule, don't require doctor table join
            // This ensures schedules show even if doctor record is missing
            if (in_array($this->userRole, ['doctor', 'nurse']) && !empty($this->staffId)) {
                // Doctor/Nurse sees only their own schedules - use LEFT JOINs to ensure schedules show
                $builder = $db->table('staff_schedule ss')
                    ->select('ss.id, ss.staff_id, ss.weekday, ss.start_time, ss.end_time, ss.status, '
                        . "COALESCE(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')), 'Staff') AS doctor_name, "
                        . 'COALESCE(d.specialization, "") AS specialization')
                    ->join('staff s', 's.staff_id = ss.staff_id', 'left')
                    ->join('doctor d', 'd.staff_id = ss.staff_id', 'left')
                    ->where('ss.status', 'active')
                    ->where('ss.staff_id', $this->staffId);
            } else {
                // Admin/IT/Receptionist see all schedules, but only for doctors
                $builder = $db->table('staff_schedule ss')
                    ->select('ss.id, ss.staff_id, ss.weekday, ss.start_time, ss.end_time, ss.status, '
                        . "CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, '')) AS doctor_name, "
                        . 'COALESCE(d.specialization, "") AS specialization')
                    ->join('staff s', 's.staff_id = ss.staff_id', 'left')
                    ->join('doctor d', 'd.staff_id = ss.staff_id', 'inner')
                    ->where('ss.status', 'active');
            }

            // Apply filters from query parameters
            $filters = $this->request->getGet();
            if (!empty($filters['date'])) {
                // For date filtering, we'd need to check if the weekday matches
                // This is a simplified version - you might want to enhance this
            }
            if (!empty($filters['status'])) {
                $builder->where('ss.status', $filters['status']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $builder->groupStart()
                    ->like("CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))", $search)
                    ->orLike('d.specialization', $search)
                    ->groupEnd();
            }

            // Order by weekday and time for better display
            $builder->orderBy('ss.weekday', 'ASC')
                ->orderBy('ss.start_time', 'ASC');

            $result = $builder->get()->getResultArray();
            
            // Log for debugging
            log_message('debug', 'ShiftManagement::getShiftsAPI - User: ' . $this->userRole . ', Staff ID: ' . $this->staffId . ', Results: ' . count($result));
            if (count($result) > 0) {
                log_message('debug', 'ShiftManagement::getShiftsAPI - Sample result: ' . json_encode($result[0]));
            } else {
                // If no results for doctor, check if schedules exist at all
                if ($this->userRole === 'doctor' && !empty($this->staffId)) {
                    $totalSchedules = $db->table('staff_schedule')
                        ->where('staff_id', $this->staffId)
                        ->countAllResults();
                    log_message('debug', 'ShiftManagement::getShiftsAPI - Total schedules for staff_id ' . $this->staffId . ': ' . $totalSchedules);
                    
                    $activeSchedules = $db->table('staff_schedule')
                        ->where('staff_id', $this->staffId)
                        ->where('status', 'active')
                        ->countAllResults();
                    log_message('debug', 'ShiftManagement::getShiftsAPI - Active schedules for staff_id ' . $this->staffId . ': ' . $activeSchedules);
                }
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data'   => $result,
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getShiftsAPI error: ' . $e->getMessage());
            log_message('error', 'ShiftManagement::getShiftsAPI stack trace: ' . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to load schedule: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a new shift
     */
    public function create()
    {
        // Check permissions - only admin and it_staff can create schedules
        if (!in_array($this->userRole, ['admin', 'it_staff'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'You do not have permission to create schedules. Only administrators and IT staff can create schedules.',
                'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
            ]);
        }

        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();

            $staffId = $input['staff_id'] ?? $input['doctor_id'] ?? null;

            // Normalize weekdays: accept either weekdays[] array (preferred)
            // or a single weekday value from older forms.
            $weekdays = [];
            if (isset($input['weekdays']) && is_array($input['weekdays'])) {
                $weekdays = $input['weekdays'];
            } elseif (isset($input['weekday']) && $input['weekday'] !== '') {
                $weekdays = [$input['weekday']];
            }

            // Validate required fields
            if (empty($staffId)) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => "Field 'staff_id' (doctor) is required",
                    'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
                ]);
            }

            if (empty($weekdays) || empty($input['start_time']) || empty($input['end_time'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'At least one weekday and both start and end time are required',
                    'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
                ]);
            }

            $db = \Config\Database::connect();

            // Ensure staff_id belongs to a doctor by checking doctor table
            // (staff table may not have a role column in this installation)
            $isDoctor = $db->table('doctor')
                ->where('staff_id', $staffId)
                ->countAllResults() > 0;

            if (!$isDoctor) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Only doctors can have schedules',
                    'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
                ]);
            }

            // Prepare common schedule data (without weekday)
            $baseData = [
                'staff_id'       => (int) $staffId,
                'start_time'     => $input['start_time'] ?? null,
                'end_time'       => $input['end_time'] ?? null,
                'status'         => 'active',
                'effective_from' => $input['effective_from'] ?? null,
                'effective_to'   => $input['effective_to'] ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $createdIds = [];

            foreach ($weekdays as $weekday) {
                $weekdayInt = (int) $weekday;
                if ($weekdayInt < 1 || $weekdayInt > 7) {
                    continue;
                }

                // Skip if a schedule already exists for this doctor/day/time range
                $existing = $db->table('staff_schedule')
                    ->where('staff_id', (int) $staffId)
                    ->where('weekday', $weekdayInt)
                    ->where('start_time', $input['start_time'] ?? null)
                    ->where('end_time', $input['end_time'] ?? null)
                    ->where('status', 'active')
                    ->countAllResults();

                if ($existing > 0) {
                    continue;
                }

                $scheduleData = $baseData;
                $scheduleData['weekday'] = $weekdayInt;

                $result = $db->table('staff_schedule')->insert($scheduleData);
                if ($result) {
                    $createdIds[] = $db->insertID();
                }
            }

            if (!empty($createdIds)) {
                return $this->response->setJSON([
                    'status'  => 'success',
                    'message' => 'Schedule entry created successfully for selected day(s)',
                    'ids'     => $createdIds,
                    'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
                ]);
            }

            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'No new schedule entries were created. There may already be active schedules for the selected day(s) and slot.',
                'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::create (schedule) error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create schedule entry: ' . $e->getMessage(),
                'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
            ]);
        }
    }

    /**
     * Update a shift (handles multiple weekdays - can add, update, or remove weekdays)
     */
    public function update()
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            
            if (empty($input['id'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Shift ID is required',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            $id = (int) $input['id'];
            $db = \Config\Database::connect();

            // Get the base shift to determine staff_id, start_time, end_time, status
            $baseShift = $db->table('staff_schedule')
                ->where('id', $id)
                ->get()
                ->getRowArray();

            if (!$baseShift) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Shift not found',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            // Determine staff_id (from input or existing)
            $staffId = !empty($input['staff_id']) ? (int) $input['staff_id'] : 
                      (!empty($input['doctor_id']) ? (int) $input['doctor_id'] : (int) $baseShift['staff_id']);

            // Validate staff_id is a doctor if changed
            if ($staffId !== (int) $baseShift['staff_id']) {
                $isDoctor = $db->table('doctor')
                    ->where('staff_id', $staffId)
                    ->countAllResults() > 0;

                if (!$isDoctor) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'status'  => 'error',
                        'message' => 'Only doctors can have schedules',
                        'csrf'    => ['name' => csrf_token(), 'value' => csrf_hash()],
                    ]);
                }
            }

            // Get time and status values (from input or existing)
            $startTime = !empty($input['start_time']) ? $input['start_time'] : $baseShift['start_time'];
            $endTime = !empty($input['end_time']) ? $input['end_time'] : $baseShift['end_time'];
            $status = 'active';
            if (!empty($input['status'])) {
                $statusLower = strtolower($input['status']);
                $status = (in_array($statusLower, ['scheduled', 'active'], true)) ? 'active' : 'inactive';
            } else {
                $status = $baseShift['status'] ?? 'active';
            }

            // Handle weekdays array from form
            $submittedWeekdays = [];
            if (isset($input['weekdays']) && is_array($input['weekdays'])) {
                $submittedWeekdays = array_map('intval', $input['weekdays']);
                $submittedWeekdays = array_filter($submittedWeekdays, function($wd) { 
                    return $wd >= 1 && $wd <= 7; 
                });
            } elseif (isset($input['weekday']) && $input['weekday'] !== '') {
                $submittedWeekdays = [(int) $input['weekday']];
            }

            // Get original weekday IDs and weekdays for comparison
            $originalIds = [];
            $originalWeekdays = [];
            
            if (isset($input['original_ids']) && is_array($input['original_ids'])) {
                $originalIds = array_map('intval', $input['original_ids']);
            } else {
                // Fallback: find all related shifts with same staff_id, start_time, end_time, status
                $relatedShifts = $db->table('staff_schedule')
                    ->where('staff_id', $baseShift['staff_id'])
                    ->where('start_time', $baseShift['start_time'])
                    ->where('end_time', $baseShift['end_time'])
                    ->where('status', $baseShift['status'] ?? 'active')
                    ->get()
                    ->getResultArray();
                
                $originalIds = array_column($relatedShifts, 'id');
                $originalWeekdays = array_column($relatedShifts, 'weekday');
            }

            // Get original weekdays from input or from database using original IDs
            if (isset($input['original_weekdays']) && is_array($input['original_weekdays']) && !empty($input['original_weekdays'])) {
                $originalWeekdays = array_map('intval', $input['original_weekdays']);
            } elseif (!empty($originalIds)) {
                // Fetch weekdays from database using the original IDs
                $originalShifts = $db->table('staff_schedule')
                    ->whereIn('id', $originalIds)
                    ->get()
                    ->getResultArray();
                $originalWeekdays = array_column($originalShifts, 'weekday');
            } else {
                // Last fallback: use the base shift's weekday
                $originalWeekdays = [$baseShift['weekday']];
                $originalIds = [$baseShift['id']];
            }

            // Find weekdays to delete (in original but not in submitted)
            $weekdaysToDelete = array_diff($originalWeekdays, $submittedWeekdays);
            
            // Find weekdays to add (in submitted but not in original)
            $weekdaysToAdd = array_diff($submittedWeekdays, $originalWeekdays);
            
            // Find weekdays to update (in both, but time/status might have changed)
            $weekdaysToUpdate = array_intersect($originalWeekdays, $submittedWeekdays);

            $deletedCount = 0;
            $addedCount = 0;
            $updatedCount = 0;

            // Delete weekdays that were unchecked
            if (!empty($weekdaysToDelete) && !empty($originalIds)) {
                // Find IDs of shifts to delete by matching weekday
                $shiftsToDelete = $db->table('staff_schedule')
                    ->whereIn('id', $originalIds)
                    ->whereIn('weekday', $weekdaysToDelete)
                    ->get()
                    ->getResultArray();
                
                $idsToDelete = array_column($shiftsToDelete, 'id');
                
                if (!empty($idsToDelete)) {
                    $db->table('staff_schedule')
                        ->whereIn('id', $idsToDelete)
                        ->delete();
                    $deletedCount = $db->affectedRows();
                }
            }

            // Update existing weekdays (time/status changes)
            if (!empty($weekdaysToUpdate)) {
                $updateData = [];
                if ($staffId !== (int) $baseShift['staff_id']) {
                    $updateData['staff_id'] = $staffId;
                }
                if ($startTime !== $baseShift['start_time']) {
                    $updateData['start_time'] = $startTime;
                }
                if ($endTime !== $baseShift['end_time']) {
                    $updateData['end_time'] = $endTime;
                }
                if ($status !== ($baseShift['status'] ?? 'active')) {
                    $updateData['status'] = $status;
                }
                
                if (!empty($updateData)) {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Update all matching shifts
                    $shiftsToUpdate = $db->table('staff_schedule')
                        ->whereIn('id', $originalIds)
                        ->whereIn('weekday', $weekdaysToUpdate)
                        ->update($updateData);
                    
                    $updatedCount = $db->affectedRows();
                }
            }

            // Add new weekdays
            if (!empty($weekdaysToAdd)) {
                $baseData = [
                    'staff_id' => $staffId,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                foreach ($weekdaysToAdd as $weekday) {
                    // Check if schedule already exists to avoid duplicates
                    $existing = $db->table('staff_schedule')
                        ->where('staff_id', $staffId)
                        ->where('weekday', $weekday)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', $status)
                        ->countAllResults();

                    if ($existing === 0) {
                        $scheduleData = $baseData;
                        $scheduleData['weekday'] = $weekday;
                        $db->table('staff_schedule')->insert($scheduleData);
                        if ($db->affectedRows() > 0) {
                            $addedCount++;
                        }
                    }
                }
            }

            // Build response message
            $messages = [];
            if ($deletedCount > 0) {
                $messages[] = "Deleted {$deletedCount} weekday(s)";
            }
            if ($updatedCount > 0) {
                $messages[] = "Updated {$updatedCount} weekday(s)";
            }
            if ($addedCount > 0) {
                $messages[] = "Added {$addedCount} weekday(s)";
            }
            
            $message = !empty($messages) 
                ? 'Shift updated successfully. ' . implode(', ', $messages) . '.'
                : 'Shift updated successfully';

            return $this->response->setJSON([
                'status' => 'success',
                'message' => $message,
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::update error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to update shift: ' . $e->getMessage(),
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
        }
    }

    /**
     * Delete a shift or multiple shifts (all weekdays)
     */
    public function delete()
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $id = $input['id'] ?? null;

            if (!$id) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Shift ID is required',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            $db = \Config\Database::connect();

            // Handle both single ID and array of IDs (for deleting all weekdays)
            if (is_array($id)) {
                // Delete multiple shifts (all weekdays for a schedule)
                $ids = array_map('intval', $id);
                $ids = array_filter($ids, function($id) { return $id > 0; });
                
                if (empty($ids)) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'status' => 'error',
                        'message' => 'Invalid shift IDs provided',
                        'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                    ]);
                }

                $deletedCount = $db->table('staff_schedule')
                    ->whereIn('id', $ids)
                    ->delete();

                if ($deletedCount > 0) {
                    return $this->response->setJSON([
                        'status' => 'success',
                        'message' => "Successfully deleted {$deletedCount} schedule entry(ies)",
                        'deleted_count' => $deletedCount,
                        'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                    ]);
                }

                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'No shifts found or already deleted',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            } else {
                // Delete single shift (backward compatibility)
            $db->table('staff_schedule')
                ->where('id', (int) $id)
                ->delete();

            if ($db->affectedRows() > 0) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'message' => 'Shift deleted successfully',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            return $this->response->setStatusCode(404)->setJSON([
                'status' => 'error',
                'message' => 'Shift not found or already deleted',
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
            }

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::delete error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to delete shift',
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
        }
    }

    /**
     * Get a single shift
     */
    public function getShift($id)
    {
        try {
            $shifts = $this->getMockShifts();
            $shift = null;
            
            foreach ($shifts as $s) {
                if ($s['id'] == $id) {
                    $shift = $s;
                    break;
                }
            }
            
            if (!$shift) {
                return $this->response->setStatusCode(404)->setJSON([
                    'status' => 'error',
                    'message' => 'Shift not found'
                ]);
            }

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $shift
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getShift error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to load shift'
            ]);
        }
    }

    /**
     * Update shift status
     */
    public function updateStatus($id)
    {
        try {
            $input = $this->request->getJSON(true) ?? $this->request->getPost();
            $status = $input['status'] ?? null;

            if (!$status) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'message' => 'Status is required',
                    'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
                ]);
            }

            // Mock successful update
            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Shift status updated successfully',
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::updateStatus error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to update shift status',
                'csrf' => ['name' => csrf_token(), 'value' => csrf_hash()]
            ]);
        }
    }

    /**
     * Get available staff for shift assignment
     */
    public function getAvailableStaffAPI()
    {
        try {
            $staff = $this->getMockAvailableStaff();
            
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $staff
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getAvailableStaffAPI error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Failed to load available staff'
            ]);
        }
    }

    // Permission methods

    private function canViewShifts() { return true; }
    private function canCreateShift() { return in_array($this->userRole, ['admin', 'it_staff']); }
    private function canEditShift() { return in_array($this->userRole, ['admin', 'it_staff', 'doctor']); }
    private function canDeleteShift() { return $this->userRole === 'admin'; }

    // Helper methods

    private function getUserPermissions()
    {
        return [
            'canView' => $this->canViewShifts(),
            'canCreate' => $this->canCreateShift(),
            'canEdit' => $this->canEditShift(),
            'canDelete' => $this->canDeleteShift(),
            'canViewAll' => $this->userRole === 'admin',
            'canViewOwn' => true,
            'canViewDepartment' => true
        ];
    }

    private function getPageConfig()
    {
        return match($this->userRole) {
            'admin' => ['title' => 'Schedule Management', 'subtitle' => 'Manage all staff schedules and shifts', 'redirectUrl' => 'admin/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin'],
            'doctor' => ['title' => 'My Schedule', 'subtitle' => 'View and manage your work schedule', 'redirectUrl' => 'doctor/dashboard', 'showSidebar' => true, 'sidebarType' => 'doctor'],
            'nurse' => ['title' => 'My Schedule', 'subtitle' => 'View your work schedule', 'redirectUrl' => 'nurse/dashboard', 'showSidebar' => true, 'sidebarType' => 'nurse'],
            'receptionist' => ['title' => 'Schedule Overview', 'subtitle' => 'View staff schedules for coordination', 'redirectUrl' => 'receptionist/dashboard', 'showSidebar' => true, 'sidebarType' => 'receptionist'],
            'it_staff' => ['title' => 'Schedule Management', 'subtitle' => 'System administration of staff schedules', 'redirectUrl' => 'it-staff/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin'],
            default => ['title' => 'Schedule Management', 'subtitle' => 'Manage all staff schedules and shifts', 'redirectUrl' => 'admin/dashboard', 'showSidebar' => true, 'sidebarType' => 'admin']
        };
    }

    private function getDepartments()
    {
        try {
            $departments = \Config\Database::connect()->table('department')
                ->select('department_id, name')
                ->orderBy('name', 'ASC')
                ->get()
                ->getResultArray();
            return array_map(fn($dept) => ['department' => $dept['name']], $departments);
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getDepartments error: ' . $e->getMessage());
            return [['department' => 'Emergency'], ['department' => 'ICU'], ['department' => 'General'], ['department' => 'Pediatrics'], ['department' => 'Cardiology'], ['department' => 'Orthopedics']];
        }
    }

    private function getShiftTypes()
    {
        return [
            ['shift_type' => 'Morning'],
            ['shift_type' => 'Afternoon'],
            ['shift_type' => 'Night'],
            ['shift_type' => 'Emergency'],
            ['shift_type' => 'On-Call']
        ];
    }

    private function getRoomsWards()
    {
        return [
            ['room_ward' => 'ER-1'],
            ['room_ward' => 'ER-2'],
            ['room_ward' => 'ICU-1'],
            ['room_ward' => 'ICU-2'],
            ['room_ward' => 'OPD-1'],
            ['room_ward' => 'OPD-2'],
            ['room_ward' => 'Ward-A'],
            ['room_ward' => 'Ward-B']
        ];
    }

    private function getDoctors()
    {
        try {
            $db = \Config\Database::connect();
            $doctors = $db->table('doctor d')
                ->select('d.doctor_id, s.staff_id, s.first_name, s.last_name, s.department, d.specialization, d.status')
                ->join('staff s', 's.staff_id = d.staff_id', 'left')
                ->where('d.status', 'Active')
                ->orderBy('s.first_name', 'ASC')
                ->get()
                ->getResultArray();
            
            return array_map(function($doctor) {
                $doctor['name'] = trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''));
                return $doctor;
            }, $doctors);
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getDoctors error: ' . $e->getMessage());
            return [];
        }
    }

    private function getAvailableDoctors()
    {
        try {
            return \Config\Database::connect()->table('doctor d')
                ->select('s.staff_id, s.first_name, s.last_name, d.specialization')
                ->join('staff s', 's.staff_id = d.staff_id', 'inner')
                ->where('d.status', 'Active')
                ->orderBy('s.first_name', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'ShiftManagement::getAvailableDoctors error: ' . $e->getMessage());
            return [];
        }
    }

}

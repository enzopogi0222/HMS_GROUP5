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
        return view('unified/room-management', [
            'title' => 'Room Management',
            'roomStats' => $this->roomService->getRoomStats(),
            'roomTypes' => $this->getRoomTypes(),
            'departments' => $this->getDepartments(),
            'roomTypeMetadata' => [],
        ]);
    }

    private function getRoomTypes(): array
    {
        if (!$this->db->tableExists('room_type')) {
            return [];
        }
        return $this->db->table('room_type')
            ->select('room_type_id, type_name, accommodation_type')
            ->orderBy('type_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getDepartments(): array
    {
        if (!$this->db->tableExists('department')) {
            return [];
        }
        return $this->db->table('department')
            ->select('department_id, name, floor')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getRoomsAPI()
    {
        return $this->jsonResponse(['status' => 'success', 'data' => $this->roomService->getRooms()]);
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

        $billingMessage = null;
        if ($assignmentId > 0 && $patientId > 0) {
            $account = $this->financialService->getOrCreateBillingAccountForPatient($patientId, $admissionId, $staffId);

            if ($account && ! empty($account['billing_id'])) {
                $billingId = (int) $account['billing_id'];
                $billingResult = $this->financialService->addItemFromRoomAssignment($billingId, $assignmentId, null, $staffId);

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
}

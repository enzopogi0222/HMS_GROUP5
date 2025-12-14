<?php

namespace App\Controllers;

use App\Services\ResourceService;
use App\Libraries\PermissionManager;

class ResourceManagement extends BaseController
{
    protected $resourceService;
    protected $permissionManager;

    protected $userRole;
    protected $staffId;

    public function __construct()
    {
        $this->resourceService = new ResourceService();
        $this->permissionManager = new PermissionManager();
        $this->userRole = session()->get('role');
        $this->staffId = session()->get('staff_id');
    }

    private function jsonResponse(array $data, int $statusCode = 200)
    {
        return $this->response->setStatusCode($statusCode)->setJSON($data);
    }

    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if (!$this->permissionManager->hasPermission($this->userRole, 'resources', 'view')) {
            return redirect()->to($this->getRedirectUrl($this->userRole))->with('error', 'Access denied');
        }

        return view('unified/resource-management', [
            'title' => $this->getPageTitle($this->userRole),
            'userRole' => $this->userRole,
            'permissions' => $this->permissionManager->getRolePermissions($this->userRole),
            'stats' => $this->resourceService->getResourceStats($this->userRole, $this->staffId),
            'categories' => $this->resourceService->getCategories($this->userRole),
            'staff' => $this->resourceService->getStaffForAssignment(),
            'resources' => $this->resourceService->getResources($this->userRole, $this->staffId),
            'expiringMedications' => $this->resourceService->getExpiringMedications(30),
            'expiredMedications' => $this->resourceService->getExpiredMedications(),
            'lowStockResources' => $this->resourceService->getLowStockResources(20, $this->userRole),
            'redirectUrl' => $this->getRedirectUrl($this->userRole)
        ]);
    }

    public function getResourcesAPI()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $filters = array_filter([
            'category' => $this->request->getGet('category'),
            'status' => $this->request->getGet('status'),
            'location' => $this->request->getGet('location'),
            'search' => $this->request->getGet('search')
        ]);

        return $this->jsonResponse([
            'success' => true,
            'data' => $this->resourceService->getResources($this->userRole, $this->staffId, $filters)
        ]);
    }

    public function getResource($resourceId)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!$this->permissionManager->hasPermission($this->userRole, 'resources', 'view')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $resource = $this->resourceService->getResourceById((int)$resourceId, $this->userRole, $this->staffId);
            
            if (!$resource) {
                return $this->jsonResponse(['success' => false, 'message' => 'Resource not found'], 404);
            }

            return $this->jsonResponse([
                'success' => true,
                'resource' => $resource
            ]);
        } catch (\Exception $e) {
            log_message('error', 'ResourceManagement::getResource error: ' . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Failed to fetch resource'], 500);
        }
    }

    public function create()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        return $this->jsonResponse($this->resourceService->createResource(
            $this->request->getPost(),
            $this->userRole,
            $this->staffId
        ));
    }

    public function update()
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $data = $this->request->getPost();
        $resourceId = $data['id'] ?? null;

        if (!$resourceId) {
            return $this->jsonResponse(['success' => false, 'message' => 'Resource ID is required'], 400);
        }

        return $this->jsonResponse($this->resourceService->updateResource(
            $resourceId,
            $data,
            $this->userRole,
            $this->staffId
        ));
    }

    public function delete($resourceId = null)
    {
        if (!session()->get('isLoggedIn')) {
            return $this->jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!$resourceId) {
            $resourceId = $this->request->getPost('id');
        }

        if (!$resourceId) {
            return $this->jsonResponse(['success' => false, 'message' => 'Resource ID is required'], 400);
        }

        return $this->jsonResponse($this->resourceService->deleteResource(
            $resourceId,
            $this->userRole,
            $this->staffId
        ));
    }

    private function getPageTitle($role)
    {
        return match($role) {
            'admin' => 'Resource Management',
            'doctor', 'nurse' => 'Medical Resources',
            'pharmacist' => 'Pharmacy Resources',
            'laboratorist' => 'Lab Resources',
            'receptionist' => 'Office Resources',
            'it_staff' => 'IT Resource Management',
            default => 'Resources'
        };
    }

    private function getRedirectUrl($role)
    {
        return match($role) {
            'admin' => '/admin/dashboard',
            'doctor' => '/doctor/dashboard',
            'nurse' => '/nurse/dashboard',
            'pharmacist' => '/pharmacist/dashboard',
            'laboratorist' => '/laboratorist/dashboard',
            'receptionist' => '/receptionist/dashboard',
            'it_staff' => '/it-staff/dashboard',
            default => '/login'
        };
    }

    public function export()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/login');
        }

        if (!$this->permissionManager->hasPermission($this->userRole, 'resources', 'view')) {
            return redirect()->to($this->getRedirectUrl($this->userRole))->with('error', 'Access denied');
        }

        $filters = array_filter([
            'category' => $this->request->getGet('category'),
            'status' => $this->request->getGet('status'),
            'location' => $this->request->getGet('location'),
            'search' => $this->request->getGet('search')
        ]);

        $resources = $this->resourceService->getResources($this->userRole, $this->staffId, $filters);

        // Set headers for CSV download
        $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="resources_export_' . date('Y-m-d') . '.csv"');
        $this->response->setHeader('Pragma', 'no-cache');
        $this->response->setHeader('Expires', '0');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8 Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Headers
        fputcsv($output, [
            'ID',
            'Resource Name',
            'Category',
            'Quantity',
            'Status',
            'Location',
            'Serial Number',
            'Batch Number',
            'Expiry Date',
            'Remarks'
        ]);

        // CSV Data rows
        foreach ($resources as $resource) {
            fputcsv($output, [
                $resource['id'] ?? '',
                $resource['equipment_name'] ?? '',
                $resource['category'] ?? '',
                $resource['quantity'] ?? '',
                $resource['status'] ?? '',
                $resource['location'] ?? '',
                $resource['serial_number'] ?? '',
                $resource['batch_number'] ?? '',
                $resource['expiry_date'] ?? '',
                $resource['remarks'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }

}
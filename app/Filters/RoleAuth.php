<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleAuth implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Check if user is logged in
        if (!$session->get('isLoggedIn')) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON(['status' => 'error', 'message' => 'Authentication required']);
            }
            return redirect()->to(base_url('/login'));
        }
        
        // Check role permissions if arguments are provided
        if (!empty($arguments)) {
            $userRole = $session->get('role');
            $allowedRoles = is_array($arguments) ? $arguments : [$arguments];
            
            if (!in_array($userRole, $allowedRoles)) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON(['status' => 'error', 'message' => 'Access denied. Insufficient permissions.']);
                }
                
                // Redirect based on user's actual role
                switch ($userRole) {
                    case 'admin':
                        return redirect()->to(base_url('/admin/dashboard'));
                    case 'doctor':
                        return redirect()->to(base_url('/doctor/dashboard'));
                    case 'receptionist':
                        return redirect()->to(base_url('/receptionist/dashboard'));
                    case 'pharmacist':
                        return redirect()->to(base_url('/pharmacist/dashboard'));
                    case 'accountant':
                        return redirect()->to(base_url('/accountant/dashboard'));
                    default:
                        return redirect()->to(base_url('/login'));
                }
            }
        }
        
        return null; // Continue with request
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No after processing needed
        return null;
    }
}
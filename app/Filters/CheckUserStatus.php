<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CheckUserStatus implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // If no user is logged in, nothing to check
        if (!$session->get('user_id')) {
            return null;
        }

        $userId = (int) $session->get('user_id');

        $db = \Config\Database::connect();
        $user = $db->table('users')
            ->select('status')
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();

        // If user not found or not active, end their authenticated session
        if (!$user || strtolower((string) ($user['status'] ?? 'inactive')) !== 'active') {
            $session->setFlashdata('error', 'Your account is inactive. Please contact the ADMINISTRATOR or IT STAFF.');

            // Clear auth-related data but keep session so flashdata survives
            $session->remove(['user_id', 'staff_id', 'username', 'email', 'role', 'isLoggedIn']);

            return redirect()->to(base_url('/login'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}

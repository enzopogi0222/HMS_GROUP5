<?php

namespace App\Controllers;

class DebugController extends BaseController
{
    public function index()
    {
        $session = session();
        $db = \Config\Database::connect();
        
        echo "<h2>Debug Information</h2>";
        
        echo "<h3>Session Data:</h3>";
        echo "<pre>";
        print_r($session->get());
        echo "</pre>";
        
        echo "<h3>Database Connection:</h3>";
        echo "Database: " . $db->getDatabase() . "<br>";
        echo "Connected: " . ($db->getConnection() ? "Yes" : "No") . "<br>";
        
        echo "<h3>Users Table:</h3>";
        $users = $db->table('users')->get()->getResultArray();
        echo "Count: " . count($users) . "<br>";
        if (!empty($users)) {
            echo "<pre>";
            print_r($users);
            echo "</pre>";
        }
        
        echo "<h3>Staff Table:</h3>";
        $staff = $db->table('staff')->get()->getResultArray();
        echo "Count: " . count($staff) . "<br>";
        if (!empty($staff)) {
            echo "<pre>";
            print_r($staff);
            echo "</pre>";
        }
        
        echo "<h3>Department Table:</h3>";
        $departments = $db->table('department')->get()->getResultArray();
        echo "Count: " . count($departments) . "<br>";
        if (!empty($departments)) {
            echo "<pre>";
            print_r($departments);
            echo "</pre>";
        }
        
        echo "<h3>Join Query Test:</h3>";
        $joinQuery = $db->table('users u')
            ->select('u.*, s.first_name, s.last_name, d.name as department, s.employee_id')
            ->join('staff s', 's.staff_id = u.staff_id', 'left')
            ->join('department d', 'd.department_id = s.department_id', 'left')
            ->get()->getResultArray();
        echo "Count: " . count($joinQuery) . "<br>";
        if (!empty($joinQuery)) {
            echo "<pre>";
            print_r($joinQuery);
            echo "</pre>";
        }
    }
}

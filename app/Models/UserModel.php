<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'staff_id',
        'username',
        'email',
        'first_name',
        'last_name',
        'password',
        'role',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    
    public function getUserWithStaff($userId)
    {
      return $this->select('users.user_id, users.username, users.email as user_email, users.role, 
                     staff.employee_id, staff.first_name, staff.last_name, staff.gender, 
                     staff.dob, staff.contact_no, staff.email as staff_email, 
                     staff.address, staff.department')
            ->join('staff', 'staff.staff_id = users.staff_id', 'inner')
            ->where('users.user_id', $userId)
            ->first();
    }

    
    public function getAllUsersWithStaff()
    {
        return $this->select('users.user_id, users.username, users.email as email, users.role, users.status,
                              staff.employee_id, staff.first_name, staff.last_name, staff.department')
                    ->join('staff', 'staff.staff_id = users.staff_id', 'inner')
                    ->orderBy('users.user_id', 'DESC')
                    ->findAll();
    }
    
    public function getByEmail($email)
    {
    return $this->where('email', $email)->first();
    }
}

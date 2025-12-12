<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffModel extends Model
{
    protected $table      = 'staff';
    protected $primaryKey = 'staff_id';
    
    protected $allowedFields = [
        'employee_id',
        'first_name',
        'last_name',
        'gender',
        'dob',
        'contact_no',
        'email',
        'address',
        'department',
        'role',
        'date_joined',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'date_joined';
    protected $updatedField  = '';

   
    public function getFullName($staffId)
    {
        $staff = $this->find($staffId);
        
        if ($staff) {
            return $staff['first_name'] . ' ' . $staff['last_name'];
        }
        
        return null;
    }
    
   
    public function getStaffWithUser($staffId)
    {
        return $this->select('staff.staff_id, staff.employee_id, staff.first_name, staff.last_name,
                      staff.department, users.email,
                      users.role_id, roles.slug as role_slug, roles.name as role_name')
            ->join('users', 'users.staff_id = staff.staff_id', 'left')
            ->join('roles', 'roles.role_id = users.role_id', 'left')
            ->where('staff.staff_id', $staffId)
            ->first();
    }

   
    public function getStaffWithoutUsers()
    {
        $userStaffIds = $this->db->table('users')->select('staff_id')->get()->getResultArray();
        $userStaffIds = array_column($userStaffIds, 'staff_id');

        if (!empty($userStaffIds)) {
            return $this->whereNotIn('staff_id', $userStaffIds)->findAll();
        } else {
            return $this->findAll();
        }
    }
}
   
    


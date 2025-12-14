<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MedicalNonMedicalDepartmentSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        // Check if department table exists
        if (!$db->tableExists('department')) {
            echo "Department table does not exist. Please run migrations first.\n";
            return;
        }

        // Medical Departments (Clinical, Emergency, Diagnostic)
        $medicalDepartments = [
            // Emergency Departments
            [
                'name' => 'Emergency Department',
                'code' => 'ER',
                'type' => 'Emergency',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Handles all urgent and life-threatening cases 24/7.',
            ],

            // Clinical Departments
            [
                'name' => 'Internal Medicine',
                'code' => 'IM',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Focuses on adult medical conditions like heart, lungs, kidney, and endocrine disorders.',
            ],
            [
                'name' => 'Pediatrics',
                'code' => 'PED',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Treats children from newborns to adolescents.',
            ],
            [
                'name' => 'OB-GYN',
                'code' => 'OBGYN',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Manages pregnancy, childbirth, and female reproductive health.',
            ],
            [
                'name' => 'General Surgery',
                'code' => 'SUR',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Performs surgeries for various general conditions.',
            ],
            [
                'name' => 'Orthopedics',
                'code' => 'ORTHO',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Treats musculoskeletal disorders including bones, joints, and muscles.',
            ],
            [
                'name' => 'Cardiology',
                'code' => 'CARD',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Focuses on heart and vascular system disorders.',
            ],
            [
                'name' => 'Neurology',
                'code' => 'NEURO',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Treats disorders of the nervous system.',
            ],
            [
                'name' => 'Pulmonology',
                'code' => 'PULMO',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Manages lung and respiratory conditions.',
            ],
            [
                'name' => 'Gastroenterology',
                'code' => 'GI',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Focuses on digestive system disorders.',
            ],
            [
                'name' => 'Dermatology',
                'code' => 'DERM',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Treats skin, hair, and nail conditions.',
            ],
            [
                'name' => 'Ophthalmology',
                'code' => 'OPHTHA',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Provides eye care and vision management.',
            ],
            [
                'name' => 'ENT',
                'code' => 'ENT',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Treats ear, nose, throat, and related head & neck conditions.',
            ],
            [
                'name' => 'Psychiatry',
                'code' => 'PSY',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '4F',
                'status' => 'Active',
                'description' => 'Manages mental health conditions.',
            ],
            [
                'name' => 'Oncology',
                'code' => 'ONC',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '4F',
                'status' => 'Active',
                'description' => 'Provides cancer diagnosis, treatment, and follow-up care.',
            ],
            [
                'name' => 'Infectious Diseases',
                'code' => 'ID',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '4F',
                'status' => 'Active',
                'description' => 'Manages infectious and communicable diseases.',
            ],
            [
                'name' => 'Endocrinology',
                'code' => 'ENDO',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '4F',
                'status' => 'Active',
                'description' => 'Focuses on hormonal and metabolic disorders.',
            ],
            [
                'name' => 'Urology',
                'code' => 'URO',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '4F',
                'status' => 'Active',
                'description' => 'Manages urinary tract and male reproductive system disorders.',
            ],
            [
                'name' => 'Anesthesiology',
                'code' => 'ANES',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '3F',
                'status' => 'Active',
                'description' => 'Provides anesthesia and pain management services.',
            ],
            [
                'name' => 'Rheumatology',
                'code' => 'RHEUM',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Treats autoimmune and inflammatory conditions.',
            ],

            // Diagnostic Departments
            [
                'name' => 'Radiology',
                'code' => 'RAD',
                'type' => 'Diagnostic',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Provides imaging services including X-rays, CT scans, MRI, and ultrasound.',
            ],
            [
                'name' => 'Laboratory',
                'code' => 'LAB',
                'type' => 'Diagnostic',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Performs clinical laboratory tests and analysis.',
            ],
            [
                'name' => 'Pathology',
                'code' => 'PATH',
                'type' => 'Diagnostic',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Examines tissues and body fluids for disease diagnosis.',
            ],
            [
                'name' => 'Nuclear Medicine',
                'code' => 'NUC',
                'type' => 'Diagnostic',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Uses radioactive materials for diagnosis and treatment.',
            ],
        ];

        // Non-Medical Departments (Administrative, Support)
        $nonMedicalDepartments = [
            [
                'name' => 'Administration',
                'code' => 'ADMIN',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Hospital administration and management services.',
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages staff recruitment, training, and employee relations.',
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Handles financial operations, billing, and accounting.',
            ],
            [
                'name' => 'Billing and Finance',
                'code' => 'BILL',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages patient billing, insurance claims, and payment processing.',
            ],
            [
                'name' => 'Medical Records',
                'code' => 'MR',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Maintains patient medical records and documentation.',
            ],
            [
                'name' => 'Patient Registration',
                'code' => 'REG',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Handles patient registration and check-in processes.',
            ],
            [
                'name' => 'IT Department',
                'code' => 'IT',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages hospital information systems and technology infrastructure.',
            ],
            [
                'name' => 'Information Technology',
                'code' => 'INFO',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Provides IT support and manages hospital technology systems.',
            ],
            [
                'name' => 'Housekeeping',
                'code' => 'HK',
                'type' => 'Support',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Maintains cleanliness and sanitation throughout the hospital.',
            ],
            [
                'name' => 'Maintenance',
                'code' => 'MAINT',
                'type' => 'Support',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages facility maintenance and equipment repairs.',
            ],
            [
                'name' => 'Security',
                'code' => 'SEC',
                'type' => 'Support',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Provides security services and ensures hospital safety.',
            ],
            [
                'name' => 'Inventory / Supply',
                'code' => 'INV',
                'type' => 'Support',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages hospital inventory, supplies, and procurement.',
            ],
        ];

        // Get existing departments to avoid duplicates
        $existingDepartments = $db->table('department')
            ->select('name, code')
            ->get()
            ->getResultArray();
        
        $existingNames = array_column($existingDepartments, 'name');
        $existingCodes = array_column($existingDepartments, 'code');

        // Filter out departments that already exist
        $newMedicalDepts = array_filter($medicalDepartments, function($dept) use ($existingNames, $existingCodes) {
            return !in_array($dept['name'], $existingNames) && 
                   !in_array($dept['code'], $existingCodes);
        });

        $newNonMedicalDepts = array_filter($nonMedicalDepartments, function($dept) use ($existingNames, $existingCodes) {
            return !in_array($dept['name'], $existingNames) && 
                   !in_array($dept['code'], $existingCodes);
        });

        $totalNew = count($newMedicalDepts) + count($newNonMedicalDepts);

        if ($totalNew === 0) {
            echo "All departments already exist. No new departments to insert.\n";
            return;
        }

        // Insert medical departments
        if (!empty($newMedicalDepts)) {
            $medicalData = array_values($newMedicalDepts);
            $inserted = $db->table('department')->insertBatch($medicalData);
            
            if ($inserted) {
                echo "Successfully inserted " . count($medicalData) . " medical department(s).\n";
                
                // Get the inserted department IDs
                $insertedNames = array_column($medicalData, 'name');
                $insertedDepts = $db->table('department')
                    ->select('department_id')
                    ->whereIn('name', $insertedNames)
                    ->get()
                    ->getResultArray();
                
                // Insert into medical_department table
                if ($db->tableExists('medical_department') && !empty($insertedDepts)) {
                    $medicalDeptData = array_map(function($dept) {
                        return ['department_id' => $dept['department_id']];
                    }, $insertedDepts);
                    
                    $db->table('medical_department')->insertBatch($medicalDeptData);
                    echo "Successfully linked " . count($medicalDeptData) . " department(s) to medical_department table.\n";
                }
            } else {
                echo "Failed to insert medical departments.\n";
            }
        }

        // Insert non-medical departments
        if (!empty($newNonMedicalDepts)) {
            $nonMedicalData = array_values($newNonMedicalDepts);
            $inserted = $db->table('department')->insertBatch($nonMedicalData);
            
            if ($inserted) {
                echo "Successfully inserted " . count($nonMedicalData) . " non-medical department(s).\n";
                
                // Get the inserted department IDs
                $insertedNames = array_column($nonMedicalData, 'name');
                $insertedDepts = $db->table('department')
                    ->select('department_id')
                    ->whereIn('name', $insertedNames)
                    ->get()
                    ->getResultArray();
                
                // Insert into non_medical_department table
                if ($db->tableExists('non_medical_department') && !empty($insertedDepts)) {
                    $nonMedicalDeptData = array_map(function($dept) {
                        return ['department_id' => $dept['department_id']];
                    }, $insertedDepts);
                    
                    $db->table('non_medical_department')->insertBatch($nonMedicalDeptData);
                    echo "Successfully linked " . count($nonMedicalDeptData) . " department(s) to non_medical_department table.\n";
                }
            } else {
                echo "Failed to insert non-medical departments.\n";
            }
        }

        echo "\nTotal: " . $totalNew . " new department(s) inserted.\n";
    }
}

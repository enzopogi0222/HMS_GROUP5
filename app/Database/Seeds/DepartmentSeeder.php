<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        // Check if department table exists
        if (!$db->tableExists('department')) {
            echo "Department table does not exist. Please run migrations first.\n";
            return;
        }

        $data = [
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
                'name' => 'Outpatient Department',
                'code' => 'OPD',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Provides consultation, diagnosis, and minor procedures for non-admitted patients.',
            ],

            [
                'name' => 'Inpatient Department',
                'code' => 'IPD',
                'type' => 'Clinical',
                'department_head_id' => null,
                'floor' => '2F',
                'status' => 'Active',
                'description' => 'Manages patients who require hospital admission and ongoing care.',
            ],

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

            // Administrative Departments
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
                'name' => 'Pharmacy',
                'code' => 'PHARM',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Dispenses medications and manages pharmaceutical inventory.',
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
                'name' => 'IT Department',
                'code' => 'IT',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages hospital information systems and technology infrastructure.',
            ],

            [
                'name' => 'Housekeeping',
                'code' => 'HK',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Maintains cleanliness and sanitation throughout the hospital.',
            ],

            [
                'name' => 'Maintenance',
                'code' => 'MAINT',
                'type' => 'Administrative',
                'department_head_id' => null,
                'floor' => '1F',
                'status' => 'Active',
                'description' => 'Manages facility maintenance and equipment repairs.',
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
        $newData = array_filter($data, function($dept) use ($existingNames, $existingCodes) {
            return !in_array($dept['name'], $existingNames) && 
                   !in_array($dept['code'], $existingCodes);
        });

        if (empty($newData)) {
            echo "All departments already exist. No new departments to insert.\n";
            return;
        }

        // Insert new departments in batches
        $inserted = $db->table('department')->insertBatch(array_values($newData));
        
        if ($inserted) {
            echo "Successfully inserted " . count($newData) . " department(s).\n";
        } else {
            echo "Failed to insert departments.\n";
        }
    }
}

<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InsuranceDetailsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('insurance_details')) {
            echo "Table insurance_details is missing.\n";
            return;
        }

        $patientTable = $db->tableExists('patient') ? 'patient' : 'patients';
        $patient = $db->table($patientTable)
            ->select('patient_id')
            ->orderBy('patient_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        if (! $patient) {
            echo "No patient records found.\n";
            return;
        }

        $patientId = (int) $patient->patient_id;

        $admission = $db->table('inpatient_admissions')
            ->select('admission_id')
            ->orderBy('admission_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $visit = $db->table('outpatient_visits')
            ->select('visit_id')
            ->orderBy('visit_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $claim = $db->table('insurance_claims')
            ->select('id')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        $coverStart = date('Y-m-d');
        $coverEnd = date('Y-m-d', strtotime('+1 year'));
        $entries = [];

        if ($admission) {
            $entries[] = [
                'patient_id'        => $patientId,
                'admission_id'      => (int) $admission->admission_id,
                'insurance_claim_id'=> $claim ? (int) $claim->id : null,
                'insurance_provider'=> 'Test HMO',
                'policy_number'     => 'INS-INP-' . $patientId,
                'coverage_start_date'=> $coverStart,
                'coverage_end_date' => $coverEnd,
                'member_name'       => 'Test Member',
                'hmo_member_id'     => 'HMO-MEM-' . $patientId,
                'hmo_approval_code' => 'HMO-APP-' . $admission->admission_id,
                'hmo_cardholder_name'=> 'Test Patient',
                'hmo_contact_person'=> 'HMO Rep',
                'hmo_attachment'    => null,
                'coverage_type'     => 'inpatient',
            ];
        }

        if ($visit) {
            $entries[] = [
                'patient_id'        => $patientId,
                'visit_id'          => (int) $visit->visit_id,
                'insurance_claim_id'=> $claim ? (int) $claim->id : null,
                'insurance_provider'=> 'Test PPO',
                'policy_number'     => 'INS-OUT-' . $patientId,
                'coverage_start_date'=> $coverStart,
                'coverage_end_date' => $coverEnd,
                'member_name'       => 'Sample Member',
                'hmo_member_id'     => 'PPO-MEM-' . $patientId,
                'hmo_approval_code' => 'PPO-APP-' . $visit->visit_id,
                'hmo_cardholder_name'=> 'Sample Patient',
                'hmo_contact_person'=> 'PPO Rep',
                'hmo_attachment'    => null,
                'coverage_type'     => 'outpatient',
            ];
        }

        if (empty($entries)) {
            echo "No admissions or visits available to seed insurance_details.\n";
            return;
        }

        $columns = $db->getFieldNames('insurance_details');
        foreach ($entries as $entry) {
            $entry = array_filter(
                $entry,
                fn($value, $key) => in_array($key, $columns, true),
                ARRAY_FILTER_USE_BOTH
            );

            $db->table('insurance_details')->insert($entry);
        }

        echo "Insurance details seeded: " . count($entries) . " records.\n";
    }
}

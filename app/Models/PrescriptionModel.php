<?php

namespace App\Models;

use CodeIgniter\Model;

class PrescriptionModel extends Model
{
    protected $table = 'prescriptions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'rx_number',
        'patient_id',
        'patient_name',
        'medication',
        'dosage',
        'frequency',
        'days_supply',
        'quantity',
        'prescriber',
        'priority',
        'notes',
        'status',
        'created_by',
        'created_at',
        'updated_at',
        'dispensed_at',
        'dispensed_by',
        'dispensed_quantity'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'patient_id' => 'required',
        'patient_name' => 'required|min_length[3]',
        'medication' => 'required|min_length[3]',
        'quantity' => 'required|integer|greater_than[0]',
        'prescriber' => 'required|min_length[3]',
        'priority' => 'required|in_list[routine,priority,stat]'
    ];

    /**
     * Get prescriptions with filtering
     */
    public function getPrescriptions($filters = [])
    {
        $builder = $this->db->table($this->table);

        // Status filter
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        // Priority filter
        if (!empty($filters['priority'])) {
            $builder->where('priority', $filters['priority']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('rx_number', $filters['search'])
                ->orLike('patient_name', $filters['search'])
                ->orLike('medication', $filters['search'])
                ->groupEnd();
        }

        return $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    /**
     * Get prescription queue (pending prescriptions)
     */
    public function getQueue($priority = null)
    {
        $builder = $this->where('status', 'queued');

        if ($priority) {
            $builder->where('priority', $priority);
        }

        return $builder->orderBy('priority', 'DESC')
                      ->orderBy('created_at', 'ASC')
                      ->findAll();
    }

    /**
     * Create new prescription
     */
    public function createPrescription($data)
    {
        $rxNumber = $this->generateRxNumber();

        $prescriptionData = [
            'rx_number' => $rxNumber,
            'patient_id' => $data['patient_id'],
            'patient_name' => $data['patient_name'],
            'medication' => $data['medication'],
            'dosage' => $data['dosage'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'days_supply' => $data['days_supply'] ?? null,
            'quantity' => $data['quantity'],
            'prescriber' => $data['prescriber'],
            'priority' => $data['priority'] ?? 'routine',
            'notes' => $data['notes'] ?? null,
            'status' => 'queued',
            'created_by' => session()->get('user_id') ?? 1
        ];

        return $this->insert($prescriptionData);
    }

    /**
     * Generate unique prescription number
     */
    private function generateRxNumber()
    {
        $prefix = 'RX';
        $year = date('Y');
        $unique = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $rxNumber = $prefix . '-' . $year . '-' . $unique;

        // Ensure uniqueness
        while ($this->where('rx_number', $rxNumber)->first()) {
            $unique = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $rxNumber = $prefix . '-' . $year . '-' . $unique;
        }

        return $rxNumber;
    }

    /**
     * Update prescription status
     */
    public function updateStatus($id, $status, $additionalData = [])
    {
        $updateData = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($status === 'dispensed') {
            $updateData['dispensed_at'] = date('Y-m-d H:i:s');
            $updateData['dispensed_by'] = session()->get('user_id') ?? 1;
            $updateData['dispensed_quantity'] = $additionalData['quantity'] ?? null;
        }

        return $this->update($id, $updateData);
    }

    /**
     * Get prescription by RX number
     */
    public function getByRxNumber($rxNumber)
    {
        return $this->where('rx_number', $rxNumber)->first();
    }

    /**
     * Check for drug interactions
     */
    public function checkInteractions($medications)
    {
        if (empty($medications) || count($medications) < 2) {
            return [];
        }

        $interactions = [];

        // Simple interaction checking logic
        // In a real application, this would query a drug interaction database
        $criticalInteractions = [
            ['warfarin', 'amoxicillin'],
            ['insulin', 'beta_blockers'],
            ['digoxin', 'diuretics']
        ];

        foreach ($medications as $med1) {
            foreach ($medications as $med2) {
                if ($med1 !== $med2) {
                    $med1Lower = strtolower($med1);
                    $med2Lower = strtolower($med2);

                    foreach ($criticalInteractions as $interaction) {
                        if (($med1Lower === $interaction[0] && $med2Lower === $interaction[1]) ||
                            ($med1Lower === $interaction[1] && $med2Lower === $interaction[0])) {
                            $interactions[] = [
                                'medication_a' => $med1,
                                'medication_b' => $med2,
                                'severity' => 'critical',
                                'description' => 'Critical drug interaction detected'
                            ];
                        }
                    }
                }
            }
        }

        return $interactions;
    }

    /**
     * Get dispense history
     */
    public function getDispenseHistory($filters = [])
    {
        $builder = $this->db->table($this->table)
                          ->where('status', 'dispensed');

        if (!empty($filters['date_from'])) {
            $builder->where('dispensed_at >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $builder->where('dispensed_at <=', $filters['date_to']);
        }

        if (!empty($filters['patient_name'])) {
            $builder->like('patient_name', $filters['patient_name']);
        }

        return $builder->orderBy('dispensed_at', 'DESC')
                      ->limit($filters['limit'] ?? 100)
                      ->get()
                      ->getResultArray();
    }

    /**
     * Get prescription statistics
     */
    public function getStatistics()
    {
        $today = date('Y-m-d');

        return [
            'total_queued' => $this->where('status', 'queued')->countAllResults(),
            'total_verifying' => $this->where('status', 'verifying')->countAllResults(),
            'total_ready' => $this->where('status', 'ready')->countAllResults(),
            'total_dispensed' => $this->where('status', 'dispensed')->countAllResults(),
            'stat_prescriptions' => $this->where('priority', 'stat')->where('status', 'queued')->countAllResults(),
            'today_dispensed' => $this->where('status', 'dispensed')->where('DATE(dispensed_at)', $today)->countAllResults()
        ];
    }
}

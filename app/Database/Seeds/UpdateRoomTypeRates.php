<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UpdateRoomTypeRates extends Seeder
{
    public function run()
    {
        $db = $this->db;
        
        if (!$db->tableExists('room_type')) {
            echo "Room type table does not exist.\n";
            return;
        }

        if (!$db->fieldExists('base_daily_rate', 'room_type')) {
            echo "base_daily_rate column does not exist. Please run migrations first.\n";
            return;
        }

        $rateMap = [
            'Ward' => 1500.00,
            'Semi-Private' => 2500.00,
            'Private' => 3500.00,
            'ICU' => 5000.00,
            'Isolation' => 3000.00,
            'Emergency' => 2000.00,
            'Consultation' => 0.00,
        ];

        $updated = 0;
        foreach ($rateMap as $typeName => $rate) {
            $result = $db->table('room_type')
                ->where('type_name', $typeName)
                ->update(['base_daily_rate' => $rate]);
            
            if ($result) {
                $updated++;
                echo "Updated {$typeName} daily rate to ₱" . number_format($rate, 2) . "\n";
            }
        }

        if ($updated > 0) {
            echo "\nSuccessfully updated {$updated} room type(s) with daily rates.\n";
        } else {
            echo "No room types were updated. Make sure room types exist in the database.\n";
        }
    }
}

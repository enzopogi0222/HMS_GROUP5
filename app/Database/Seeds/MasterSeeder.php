<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class MasterSeeder extends Seeder
{
    public function run()
    {
        $this->call('InpatientTestSeeder');
        $this->call('InsuranceDetailsSeeder');
        $this->call('OutpatientTestSeeder');

  
     
        // ...add all remaining seeders
    }
}
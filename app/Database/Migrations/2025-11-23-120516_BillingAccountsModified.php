<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterBillingAccountsAdmissionNullable extends Migration
{
    public function up()
    {
        // Make admission_id nullable (keep type and FK)
        $fields = [
            'admission_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,   // allow NULL
            ],
        ];

        $this->forge->modifyColumn('billing_accounts', $fields);
    }

    public function down()
    {
        // Revert to NOT NULL (only if you really need to roll back)
        $fields = [
            'admission_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,  // back to NOT NULL
            ],
        ];

        $this->forge->modifyColumn('billing_accounts', $fields);
    }
}
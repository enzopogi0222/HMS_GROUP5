<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToBillingAccounts extends Migration
{
    public function up()
    {
        $fields = [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['open', 'paid'],
                'default'    => 'open',
                'null'       => false,
            ],
        ];

        // Add after admission_id just for nicer ordering (optional)
        $this->forge->addColumn('billing_accounts', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('billing_accounts', 'status');
    }
}
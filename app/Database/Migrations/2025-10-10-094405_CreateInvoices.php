<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInvoices extends Migration
{
    public function up()
    {
        // Skip if invoices table already exists to avoid duplicate key/index errors
        if ($this->db->tableExists('invoices')) {
            return;
        }
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'invoice_no' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
            ],
            'patient_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'service' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'due_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'default'    => 'Pending',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('invoice_no');
        $this->forge->createTable('invoices');
    }

    public function down()
    {
        $this->forge->dropTable('invoices');
    }
}

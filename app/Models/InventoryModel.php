<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table = 'pharmacy_inventory';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'item_code',
        'name',
        'category',
        'description',
        'stock_quantity',
        'unit',
        'min_stock_level',
        'max_stock_level',
        'expiry_date',
        'batch_number',
        'supplier',
        'unit_price',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'item_code' => 'required|is_unique[pharmacy_inventory.item_code,id,{id}]',
        'name' => 'required|min_length[3]',
        'category' => 'required',
        'stock_quantity' => 'required|integer|greater_than_equal_to[0]',
        'unit' => 'required',
        'min_stock_level' => 'required|integer|greater_than_equal_to[0]',
        'expiry_date' => 'required|valid_date',
        'unit_price' => 'required|decimal|greater_than[0]'
    ];

    protected $validationMessages = [
        'item_code' => [
            'is_unique' => 'This item code already exists.'
        ]
    ];

    /**
     * Get inventory items with filtering
     */
    public function getInventory($filters = [])
    {
        $builder = $this->db->table($this->table);

        // Search filter
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('item_code', $filters['search'])
                ->orLike('name', $filters['search'])
                ->groupEnd();
        }

        // Category filter
        if (!empty($filters['category'])) {
            $builder->where('category', $filters['category']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'low':
                    $builder->where('stock_quantity <= min_stock_level');
                    break;
                case 'expired':
                    $builder->where('expiry_date <', date('Y-m-d'));
                    break;
                case 'ok':
                    $builder->where('stock_quantity > min_stock_level')
                           ->where('expiry_date >=', date('Y-m-d'));
                    break;
            }
        }

        return $builder->orderBy('name', 'ASC')->get()->getResultArray();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems()
    {
        return $this->where('stock_quantity <= min_stock_level')
                   ->where('expiry_date >=', date('Y-m-d'))
                   ->orderBy('stock_quantity', 'ASC')
                   ->findAll();
    }

    /**
     * Get expired items
     */
    public function getExpiredItems()
    {
        return $this->where('expiry_date <', date('Y-m-d'))
                   ->orderBy('expiry_date', 'ASC')
                   ->findAll();
    }

    /**
     * Receive stock (add to inventory)
     */
    public function receiveStock($data)
    {
        $this->db->transStart();

        $existing = $this->where('item_code', $data['item_code'])->first();

        if ($existing) {
            // Update existing item
            $newQuantity = $existing['stock_quantity'] + $data['quantity'];
            $this->update($existing['id'], [
                'stock_quantity' => $newQuantity,
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // Log the transaction
            $this->logInventoryTransaction($existing['id'], 'receive', $data['quantity'], $data['reason'] ?? 'Stock received');
        } else {
            // Create new item
            $this->insert([
                'item_code' => $data['item_code'],
                'name' => $data['name'],
                'category' => $data['category'],
                'stock_quantity' => $data['quantity'],
                'unit' => $data['unit'],
                'min_stock_level' => $data['min_stock_level'] ?? 10,
                'expiry_date' => $data['expiry_date'] ?? null,
                'batch_number' => $data['batch_number'] ?? null,
                'unit_price' => $data['unit_price'] ?? 0,
                'status' => 'active'
            ]);

            $newId = $this->getInsertID();
            $this->logInventoryTransaction($newId, 'receive', $data['quantity'], $data['reason'] ?? 'New item added');
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Adjust inventory (add/subtract quantity)
     */
    public function adjustInventory($data)
    {
        $item = $this->where('item_code', $data['item_code'])->first();

        if (!$item) {
            return false;
        }

        $newQuantity = max(0, $item['stock_quantity'] + $data['adjustment']);

        $this->db->transStart();

        $this->update($item['id'], [
            'stock_quantity' => $newQuantity,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->logInventoryTransaction($item['id'], 'adjust', $data['adjustment'], $data['reason']);

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Log inventory transactions
     */
    private function logInventoryTransaction($inventoryId, $type, $quantity, $reason)
    {
        $logData = [
            'inventory_id' => $inventoryId,
            'transaction_type' => $type,
            'quantity_change' => $quantity,
            'reason' => $reason,
            'created_by' => session()->get('user_id') ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->table('inventory_transactions')->insert($logData);
    }

    /**
     * Get inventory transaction history
     */
    public function getTransactionHistory($inventoryId = null, $limit = 50)
    {
        $builder = $this->db->table('inventory_transactions it');
        $builder->select('it.*, pi.name as item_name, pi.item_code');
        $builder->join('pharmacy_inventory pi', 'it.inventory_id = pi.id');

        if ($inventoryId) {
            $builder->where('it.inventory_id', $inventoryId);
        }

        return $builder->orderBy('it.created_at', 'DESC')
                      ->limit($limit)
                      ->get()
                      ->getResultArray();
    }
}

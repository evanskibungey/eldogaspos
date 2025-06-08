<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'name' => 'John Doe',
                'phone' => '0701234567',
                'credit_limit' => 5000.00,
                'balance' => 1500.00,
                'status' => 'active'
            ],
            [
                'name' => 'Jane Smith',
                'phone' => '0712345678',
                'credit_limit' => 3000.00,
                'balance' => 0.00,
                'status' => 'active'
            ],
            [
                'name' => 'Peter Johnson',
                'phone' => '0723456789',
                'credit_limit' => 2000.00,
                'balance' => 800.00,
                'status' => 'active'
            ],
            [
                'name' => 'Mary Wilson',
                'phone' => '0734567890',
                'credit_limit' => 4000.00,
                'balance' => 2200.00,
                'status' => 'active'
            ],
            [
                'name' => 'David Brown',
                'phone' => '0745678901',
                'credit_limit' => 1500.00,
                'balance' => 0.00,
                'status' => 'active'
            ],
            [
                'name' => 'Sarah Davis',
                'phone' => '0756789012',
                'credit_limit' => 6000.00,
                'balance' => 3500.00,
                'status' => 'active'
            ],
            [
                'name' => 'Michael Miller',
                'phone' => '0767890123',
                'credit_limit' => 2500.00,
                'balance' => 0.00,
                'status' => 'active'
            ],
            [
                'name' => 'Lisa Garcia',
                'phone' => '0778901234',
                'credit_limit' => 3500.00,
                'balance' => 1100.00,
                'status' => 'active'
            ]
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                ['phone' => $customerData['phone']],
                $customerData
            );
        }
    }
}

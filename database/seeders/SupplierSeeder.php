<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Jordan Lee',
                'email' => 'orders@northstar.example',
                'phone' => '+1-555-0101',
                'address' => '1200 Market Street, Denver, CO',
                'company_name' => 'Northstar Components',
            ],
            [
                'name' => 'Morgan Patel',
                'email' => 'sales@officeworks.example',
                'phone' => '+1-555-0102',
                'address' => '45 River Road, Portland, OR',
                'company_name' => 'OfficeWorks Supply',
            ],
            [
                'name' => 'Taylor Kim',
                'email' => null,
                'phone' => '+1-555-0103',
                'address' => '800 Industrial Avenue, Austin, TX',
                'company_name' => 'Summit Industrial',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::query()->updateOrCreate(
                ['company_name' => $supplier['company_name']],
                $supplier,
            );
        }
    }
}

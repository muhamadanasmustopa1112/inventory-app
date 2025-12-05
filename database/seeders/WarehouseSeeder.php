<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Warehouse::create([
            'name' => 'Gudang Cirebon',
            'code' => 'GDG-CRB',
            'city' => 'Cirebon',
            'address' => 'Cirebon, Jawa Barat',
            'is_active' => true,
        ]);

        Warehouse::create([
            'name' => 'Gudang Bandung',
            'code' => 'GDG-BDG',
            'city' => 'Bandung',
            'address' => 'Bandung, Jawa Barat',
            'is_active' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@inventory.com',
            'password' => bcrypt('password'),
            'role' => 'ADMIN',
            'warehouse_id' => null,
        ]);

        // User Gudang Cirebon (id = 1)
        User::create([
            'name' => 'Gudang Cirebon Operator',
            'email' => 'cirebon@inventory.com',
            'password' => bcrypt('password'),
            'role' => 'WAREHOUSE',
            'warehouse_id' => 1,
        ]);

        // User Gudang Bandung (id = 2)
        User::create([
            'name' => 'Gudang Bandung Operator',
            'email' => 'bandung@inventory.com',
            'password' => bcrypt('password'),
            'role' => 'WAREHOUSE',
            'warehouse_id' => 2,
        ]);
    }
}

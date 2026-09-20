<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'role_defined_id' => "001",
                'role_name' => "System Admin",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_defined_id' => "002",
                'role_name' => 'Creative Member',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_defined_id' => "003",
                'role_name' => "Organizer Admin",
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_defined_id' =>'004',
                'role_name' => 'Inspiring Manager',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_defined_id' => '005',
                'role_name' => 'Eagle Treasurer',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'role_defined_id' => '006',
                'role_name' => 'Friendly Visitor',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('roles')->insert($roles);

    }
}

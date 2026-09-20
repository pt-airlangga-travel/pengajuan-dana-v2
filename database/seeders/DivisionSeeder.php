<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'division_defined_id' => '001',
                'division_name' => 'Tour',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'division_defined_id' => '002',
                'division_name' => 'Ticketing',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'division_defined_id' => '003',
                'division_name' => 'Keuangan',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'division_defined_id' => '004',
                'division_name' => 'Store',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'division_defined_id' => '005',
                'division_name' => 'Armada',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'division_defined_id' => '006',
                'division_name' => 'Bisnis',
                'created_at' => now(),
                'updated_at' => now()
            ]

        ];
        DB::table('divisions')->insert($divisions);
    }
}

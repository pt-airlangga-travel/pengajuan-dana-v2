<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $institutions = [
            [
                'institution_defined_id' => '001',
                'institution_name' => 'Direktorat Keuangan',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        DB::table('institutions')->insert($institutions);
    }
}

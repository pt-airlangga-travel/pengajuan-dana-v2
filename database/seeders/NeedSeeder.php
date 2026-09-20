<?php

namespace Database\Seeders;

use App\Models\Need;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class NeedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $needs = ['fee','snack','pesawat','kereta','hotel','makan','sewa','mobil'];
        foreach ($needs as $index =>$value) {
            Need::create([
                'need_defined_id'=>'00'.++$index,
                'need_name'=> $value,
            ]);
        }
    }
}

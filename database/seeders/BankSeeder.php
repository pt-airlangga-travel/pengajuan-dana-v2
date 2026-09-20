<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bank = ['BNI','BCA','BRI','BANK JATIM','MANDIRI',"BTN",'BSI'];
        foreach ($bank as $index=>$value) {
            Bank::create([
                'bank_defined_id'=>'00'.++$index,
                'bank_name'=>$value
            ]);
        }
    }
}

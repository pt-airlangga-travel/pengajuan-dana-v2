<?php

namespace Database\Seeders;

use App\Models\BankAsal;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BankAsalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        
        BankAsal::create([
            'bank_name'=>"Mandiri Giro",
            'no_rekening'=>'1420014668502',
            'color'=>'red'
        ]);
        BankAsal::create([
            'bank_name'=>"Mandiri Bisnis",
            'no_rekening'=>'1420055001001',
            'color'=>'green'
        ]);
        BankAsal::create([
            'bank_name'=>"BNI AGT",
            'no_rekening'=>'1101119540',
            'color'=>'blue'
        ]);
        BankAsal::create([
            'bank_name'=>"BNI Unair Store",
            'no_rekening'=>'1638721498',
            'color'=>'yellow'
        ]);
        BankAsal::create([
            'bank_name'=>"BCA",
            'no_rekening'=>'0889002672',
            'color'=>'pink'
        ]);
        BankAsal::create([
            'bank_name'=>"Bank Jatim",
            'no_rekening'=>'0321024807',
            'color'=>'orange'
        ]);
    }
}

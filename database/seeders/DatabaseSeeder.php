<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\BankSeeder;
use Database\Seeders\NeedSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\BankAsalSeeder;
use Database\Seeders\DivisionSeeder;
use Database\Seeders\InstitutionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(RoleSeeder::class);
        $this->call(DivisionSeeder::class);
        $this->call(UserSeeder::class);

        $this->call(BankSeeder::class);
        $this->call(NeedSeeder::class);
        $this->call(BankAsalSeeder::class);
        
    }
}

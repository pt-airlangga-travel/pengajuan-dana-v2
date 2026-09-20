<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

     /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function run(): void
    {
        $users = [
            [
                'name' => 'Wahyu',
                'email' => 'wsobirin2@gmail.com',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '001',
                'id_division' => '006'
            ],
            [
                'name' => 'Imam Sobirin',
                'email' => 'imam.wahyu.sobirin-2021@vokasi.unair.ac.id',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '003',
                'id_division' => '006'
            ],
            [
                'name' => 'Sobirin',
                'email' => 'sobirin@gmail.com',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '002',
                'id_division' => '001'
            ],
            [
                'name' => 'john doe',
                'email' => 'systemadmin@gmail.com',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '001',
                'id_division' => '006'
            ],
            [
                'name' => 'member',
                'email' => 'member@gmail.com',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '002',
                'id_division' => '001'
            ],
            [
                'name' => 'Alexander ',
                'email' => 'adminoperational@gmail.com',
                'position' => 'Administrator Operasional',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '003',
                'id_division' => '006'
            ],
            [
                'name' => 'Udin ',
                'email' => 'manager@gmail.com',
                'position' => 'Manager',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '004',
                'id_division' => '006'
            ],
            [
                'name' => 'Bendahara',
                'email' => 'bendahara@gmail.com',
                'position' => 'Bendahara',
                'active_status' => true,
                'email_verified_at' => now(),
                'password' => static::$password ??= Hash::make('password'),
                'remember_token' => Str::random(10),    
                'created_at' => now(),
                'updated_at' => now(),
                'id_role' => '005',
                'id_division' => '006'
            ],

        ];

        DB::table('users')->insert($users);
    }
    
}

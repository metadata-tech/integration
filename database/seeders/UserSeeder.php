<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $superadmin = \App\Models\User::factory()->create([
             'name' => 'Hassan Abu Bakar',
             'email' => 'odin88@gmail.com',
             'password' => Hash::make('ayam')
         ]);

        $superadmin->assignRole('super admin');

        $admin = \App\Models\User::factory()->create([
            'name' => 'Hailmi',
            'email' => 'hailmi@gmail.com',
            'password' => Hash::make('ayam')
        ]);

        $admin->assignRole('admin');
    }
}

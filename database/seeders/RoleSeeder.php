<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'super admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'esis', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'kpm', 'guard_name' => 'sanctum']);
    }
}

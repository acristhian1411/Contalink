<?php

namespace Database\Seeders;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AssignPermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = Permission::all();
        $role = Role::where('name','Root')->first();
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission->name);
        }
        $user = User::find(0);
        $user->assignRole('Root');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $manageUsers = Permission::create(['name' => 'manage users']);
        $viewUsers = Permission::create(['name' => 'view users']);
        $createUsers = Permission::create(['name' => 'create users']);
        $editUsers = Permission::create(['name' => 'edit users']);
        $deleteUsers = Permission::create(['name' => 'delete users']);

        // Create roles and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo([
            $manageUsers,
            $viewUsers,
            $createUsers,
            $editUsers,
            $deleteUsers,
        ]);

        $userRole = Role::create(['name' => 'user']);
        // Users have no permissions by default
    }
}

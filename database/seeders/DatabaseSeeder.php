<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@lingualab.test',
            'password' => bcrypt('adminpass'),
        ]);

        // Assign admin role
        $admin->assignRole('admin');

        // Create a regular user for testing
        $user = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'user@lingualab.test',
            'password' => bcrypt('userpass'),
        ]);

        // Assign user role
        $user->assignRole('user');
    }
}

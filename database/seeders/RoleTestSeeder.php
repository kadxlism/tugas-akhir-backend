<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create PM user
        User::create([
            'name' => 'Project Manager',
            'email' => 'pm@test.com',
            'password' => Hash::make('password'),
            'role' => 'pm',
        ]);

        // Create team member
        User::create([
            'name' => 'Team Member',
            'email' => 'team@test.com',
            'password' => Hash::make('password'),
            'role' => 'team',
        ]);

        // Create client
        User::create([
            'name' => 'Client User',
            'email' => 'client@test.com',
            'password' => Hash::make('password'),
            'role' => 'client',
        ]);

        // Create additional test users for each role
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "Admin {$i}",
                'email' => "admin{$i}@test.com",
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]);

            User::create([
                'name' => "PM {$i}",
                'email' => "pm{$i}@test.com",
                'password' => Hash::make('password'),
                'role' => 'pm',
            ]);

            User::create([
                'name' => "Team {$i}",
                'email' => "team{$i}@test.com",
                'password' => Hash::make('password'),
                'role' => 'team',
            ]);

            User::create([
                'name' => "Client {$i}",
                'email' => "client{$i}@test.com",
                'password' => Hash::make('password'),
                'role' => 'client',
            ]);
        }
    }
}

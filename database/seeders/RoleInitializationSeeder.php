<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleInitializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperAdmin123!'),
            'role' => 'admin',
        ]);

        // Create default admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin123!'),
            'role' => 'admin',
        ]);

        // Create default PM user
        User::create([
            'name' => 'Project Manager',
            'email' => 'pm@example.com',
            'password' => Hash::make('PM123!'),
            'role' => 'pm',
        ]);

        // Create default team member
        User::create([
            'name' => 'Team Member',
            'email' => 'team@example.com',
            'password' => Hash::make('Team123!'),
            'role' => 'team',
        ]);

        // Create default client
        User::create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('Client123!'),
            'role' => 'client',
        ]);

        $this->command->info('Role initialization completed successfully!');
        $this->command->info('Default users created:');
        $this->command->info('- Super Admin: superadmin@example.com / SuperAdmin123!');
        $this->command->info('- Admin: admin@example.com / Admin123!');
        $this->command->info('- PM: pm@example.com / PM123!');
        $this->command->info('- Team: team@example.com / Team123!');
        $this->command->info('- Client: client@example.com / Client123!');
    }
}

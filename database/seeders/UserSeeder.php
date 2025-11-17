<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting user seeding...');

        // ===== FETCH ROLES =====
        $this->command->info('Fetching roles...');

        $bpsAdminRole = UserRole::where('role_name', 'bps_admin')->first();
        $villageOfficerRole = UserRole::where('role_name', 'village_officer')->first();
        $guestRole = UserRole::where('role_name', 'guest')->first();

        // Verify roles exist
        if (!$bpsAdminRole || !$villageOfficerRole || !$guestRole) {
            $this->command->error('Required roles not found! Make sure RoleSeeder has been run first.');
            return;
        }

        // ===== CREATE BPS ADMIN USER =====
        if (!User::where('email', 'admin@bps.go.id')->exists()) {
            User::create([
                'username' => 'bps_admin',
                'email' => 'admin@bps.go.id',
                'password' => Hash::make('password'),
                'full_name' => 'Administrator BPS',
                'phone_number' => '081234567890',
                'role_id' => $bpsAdminRole->id,
                'village_id' => null,
                'is_active' => true,
            ]);

            $this->command->info('✓ BPS Admin user created: admin@bps.go.id (password: password)');
        } else {
            $this->command->warn('BPS Admin user already exists. Skipping...');
        }

        // ===== CREATE VILLAGE OFFICER USER (if village exists) =====
        $village = Village::first();
        if ($village && $villageOfficerRole) {
            if (!User::where('email', 'officer@desa.go.id')->exists()) {
                User::create([
                    'username' => 'village_officer',
                    'email' => 'officer@desa.go.id',
                    'password' => Hash::make('password'),
                    'full_name' => 'Perangkat Desa ' . $village->name,
                    'phone_number' => '081234567891',
                    'role_id' => $villageOfficerRole->id,
                    'village_id' => $village->id,
                    'is_active' => true,
                ]);

                $this->command->info('✓ Village Officer user created: officer@desa.go.id (password: password)');
            } else {
                $this->command->warn('Village Officer user already exists. Skipping...');
            }
        } else {
            $this->command->warn('No village found in database. Skipping village officer creation.');
        }

        // ===== SUMMARY =====
        $this->command->newLine();
        $this->command->info('==================================');
        $this->command->info('✓ User seeding completed!');
        $this->command->info('==================================');
        $this->command->info('Total Users: ' . User::count());
        $this->command->info('Total Roles: ' . UserRole::count());
    }
}

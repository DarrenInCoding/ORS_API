<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('role', 'branch_manager')->first();
        $staff = User::where('role', 'staff')->first();

        $branch = Branch::create([
            'name' => 'Main Recycling Center',
            'code' => 'BR-001',
            'address' => '123 Green Street, Eco Park',
            'city' => 'Kuala Lumpur',
            'state' => 'Wilayah Persekutuan',
            'postal_code' => '50000',
            'country' => 'Malaysia',
            'latitude' => 3.1390,
            'longitude' => 101.6869,
            'phone' => '03-12345678',
            'email' => 'main@recyclesystem.com',
            'operating_hours' => [
                'mon' => '08:00-18:00',
                'tue' => '08:00-18:00',
                'wed' => '08:00-18:00',
                'thu' => '08:00-18:00',
                'fri' => '08:00-18:00',
                'sat' => '09:00-14:00',
                'sun' => 'Closed',
            ],
            'manager_id' => $manager?->id,
            'is_active' => true,
            'description' => 'Our main recycling center in Kuala Lumpur.',
        ]);

        // Assign staff to branch
        if ($staff) {
            $branch->staff()->attach($staff->id, [
                'position' => 'Sorter',
                'assigned_at' => now()->toDateString(),
            ]);
        }

        Branch::create([
            'name' => 'Petaling Jaya Branch',
            'code' => 'BR-002',
            'address' => '456 Jalan SS2/72',
            'city' => 'Petaling Jaya',
            'state' => 'Selangor',
            'postal_code' => '47300',
            'country' => 'Malaysia',
            'latitude' => 3.1073,
            'longitude' => 101.6068,
            'phone' => '03-87654321',
            'email' => 'pj@recyclesystem.com',
            'operating_hours' => [
                'mon' => '09:00-17:00',
                'tue' => '09:00-17:00',
                'wed' => '09:00-17:00',
                'thu' => '09:00-17:00',
                'fri' => '09:00-17:00',
                'sat' => '10:00-15:00',
                'sun' => 'Closed',
            ],
            'is_active' => true,
            'description' => 'Branch office in Petaling Jaya.',
        ]);
    }
}

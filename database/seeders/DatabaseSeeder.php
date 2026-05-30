<?php

namespace Database\Seeders;

use App\Models\Container;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Jalankan dengan: php artisan db:seed
     */
    public function run(): void
    {
        // ========== BUAT USER ADMIN & USER BIASA ==========
        $admin = User::create([
            'name'     => 'Admin WowoClean',
            'email'    => 'admin@wowoclean.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        $user = User::create([
            'name'     => 'User WowoClean',
            'email'    => 'user@wowoclean.com',
            'password' => Hash::make('user123'),
            'role'     => 'user',
        ]);

        // ========== BUAT CONTAINER + TRACKING LOGS ==========
        $container1 = Container::create([
            'container_id' => 'GD12345',
            'waste_type'   => 'Chemical',
            'weight_kg'    => 850,
            'status'       => 'Active',
        ]);
        TrackingLog::create([
            'container_id' => $container1->id,
            'location'     => 'Warehouse A',
            'timestamp'    => now()->subDays(20),
            'description'  => 'Container received from supplier',
        ]);
        TrackingLog::create([
            'container_id' => $container1->id,
            'location'     => 'Processing Unit B',
            'timestamp'    => now()->subDays(18),
            'description'  => 'Moved to processing area',
        ]);

        $container2 = Container::create([
            'container_id' => 'WH67890',
            'waste_type'   => 'Organic',
            'weight_kg'    => 1200,
            'status'       => 'Active',
        ]);
        TrackingLog::create([
            'container_id' => $container2->id,
            'location'     => 'Warehouse C',
            'timestamp'    => now()->subDays(19),
            'description'  => 'Initial container entry',
        ]);

        $container3 = Container::create([
            'container_id' => 'PL54321',
            'waste_type'   => 'Plastic',
            'weight_kg'    => 450,
            'status'       => 'Archived',
        ]);
        TrackingLog::create([
            'container_id' => $container3->id,
            'location'     => 'Recycling Center',
            'timestamp'    => now()->subDays(22),
            'description'  => 'Sent for recycling',
        ]);
        TrackingLog::create([
            'container_id' => $container3->id,
            'location'     => 'Archive Storage',
            'timestamp'    => now()->subDays(15),
            'description'  => 'Process completed and archived',
        ]);
    }
}

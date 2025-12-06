<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Delivery;
use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $business = Business::factory()->create([
            'name' => 'Local Bakery',
        ]);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'business_id' => $business->id,
        ]);

        // Create a pending run with deliveries
        $pendingRun = Run::factory()->create([
            'business_id' => $business->id,
            'created_by_user_id' => $user->id,
            'name' => 'Monday Morning Deliveries',
            'pin' => '1234',
        ]);

        $deliveryData = [
            ['email' => 'customer1@example.com', 'name' => '123 Main Street'],
            ['email' => 'customer2@example.com', 'name' => 'Jane Smith'],
            ['email' => 'customer3@example.com', 'name' => '456 Oak Avenue'],
            ['email' => 'customer4@example.com', 'name' => null],
            ['email' => 'customer5@example.com', 'name' => 'The Corner Shop'],
        ];

        foreach ($deliveryData as $index => $data) {
            Delivery::factory()->create([
                'run_id' => $pendingRun->id,
                'email' => $data['email'],
                'name' => $data['name'],
                'position' => $index + 1,
            ]);
        }

        // Create an in-progress run
        $inProgressRun = Run::factory()->inProgress()->create([
            'business_id' => $business->id,
            'created_by_user_id' => $user->id,
            'name' => 'Afternoon Run',
            'pin' => '5678',
        ]);

        Delivery::factory()->completed()->create([
            'run_id' => $inProgressRun->id,
            'position' => 1,
        ]);

        Delivery::factory()->notified()->create([
            'run_id' => $inProgressRun->id,
            'position' => 2,
        ]);

        Delivery::factory()->create([
            'run_id' => $inProgressRun->id,
            'position' => 3,
        ]);
    }
}

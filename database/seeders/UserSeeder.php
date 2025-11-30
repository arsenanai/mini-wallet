<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Log::info('UserSeeder: Creating commission account...');
        User::factory()->create([
            'name'    => 'Commission Account',
            'email'   => config('wallet.commission_account_email'),
            'balance' => 0,
        ]);
        Log::info('UserSeeder: Commission account created successfully.');

        Log::info('UserSeeder: Creating 100 sample users...');
        try {
            User::factory()->create([
                'name'     => 'User A',
                'email'    => 'user_a@email.com',
            ]);
            User::factory()->create([
                'name'     => 'User B',
                'email'    => 'user_b@email.com',
            ]);
            User::factory(98)->create();
            Log::info('UserSeeder: 100 sample users created successfully.');
        } catch (\Exception $e) {
            Log::error('UserSeeder: Failed to create sample users. Error: ' . $e->getMessage());
            // Optionally re-throw or handle the exception as needed
        }
    }
}

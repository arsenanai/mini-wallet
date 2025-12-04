<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prepare data for a single bulk insert to improve performance.
        $users = [];
        $now = now();
        $password = Hash::make('password');

        // Add the commission account.
        $users[] = [
            'name' => 'Commission Account',
            'email' => config('wallet.commission_account_email'),
            'balance' => 0,
        ];

        // Prepare User A and User B
        $users[] = ['name' => 'User A', 'email' => 'user_a@email.com'];
        $users[] = ['name' => 'User B', 'email' => 'user_b@email.com'];

        // Prepare users 3 through 100
        for ($i = 3; $i <= 100; $i++) {
            $users[] = ['name' => 'User ' . $i, 'email' => 'user_' . $i . '@email.com'];
        }

        // Map over the array to add common attributes to all users.
        $dataToInsert = array_map(fn ($user) => array_merge($user, [
            'balance' => $user['balance'] ?? fake()->randomFloat(4, 1000, 10000),
            'email_verified_at' => $now,
            'password' => $password,
            'remember_token' => Str::random(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]), $users);

        // Insert all users in a single, consistent query.
        User::insert($dataToInsert);
    }
}

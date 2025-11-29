<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sender_id'      => User::factory(),
            'receiver_id'    => User::factory(),
            'amount'         => $this->faker->randomFloat(4, 1, 1000),
            'commission_fee' => $this->faker->randomFloat(4, 0.01, 15),
            'status'         => TransactionStatus::COMPLETED,
            'type'           => TransactionType::TRANSFER,
            'reference_id'   => Str::uuid(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('dashboard page is rendered with transactions including sender and receiver', function () {
    // 1. Arrange
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create an older transaction where the user is the receiver
    Transaction::factory()->create([
        'sender_id' => $otherUser->id,
        'receiver_id' => $user->id,
        'created_at' => now()->subMinute(),
    ]);

    // Create the latest transaction where the user is the sender
    Transaction::factory()->create([
        'sender_id' => $user->id,
        'receiver_id' => $otherUser->id,
        'created_at' => now(),
    ]);

    // 2. Act
    $response = $this->actingAs($user)->get(route('dashboard'));

    // 3. Assert
    $response->assertOk();

    $response->assertInertia(function (AssertableInertia $page) use ($user, $otherUser) {
        $page->component('Dashboard')
            ->has('balance')
            ->has('transactions.data', 2)
            ->has('transactions.data.0.sender')
            ->has('transactions.data.0.receiver')
            ->where('transactions.data.0.sender.id', $user->id)
            ->where('transactions.data.0.receiver.id', $otherUser->id);
    });
});

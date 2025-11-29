<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Create the central commission account for tests
    User::factory()->create([
        'email'   => config('wallet.commission_account_email'),
        'balance' => 0,
    ]);

    Event::fake();
});

test('a user can successfully make a transfer', function () {
    // Arrange: Create a sender and a receiver with initial balances
    $sender   = User::factory()->create([ 'balance' => 1000 ]);
    $receiver = User::factory()->create([ 'balance' => 500 ]);
    $amount   = 100.00;

    $commission = $amount * config('wallet.commission_rate');
    $totalDebit = $amount + $commission;

    // Act: Simulate an API call from the authenticated sender
    $response = $this->actingAs($sender)->postJson('/api/transactions', [
        'receiver_email' => $receiver->email,
        'amount'         => $amount,
    ]);

    // Assert: Check for a successful response and JSON structure
    $response
        ->assertStatus(201)
        ->assertJson([
            'message' => __('messages.transfer_successful'),
        ])
        ->assertJsonStructure([
            'message',
            'transaction' => [ 'id', 'sender_id', 'receiver_id', 'amount' ],
        ]);

    // Assert: Check the database for the completed transaction record
    $this->assertDatabaseHas('transactions', [
        'sender_id'      => $sender->id,
        'receiver_id'    => $receiver->id,
        'amount'         => $amount,
        'commission_fee' => $commission,
        'status'         => 'completed',
    ]);

    // Assert: Check the updated balances in the database
    $this->assertEquals(1000 - $totalDebit, $sender->refresh()->balance);
    $this->assertEquals(500 + $amount, $receiver->refresh()->balance);

    // Assert: Check that events were dispatched
    Event::assertDispatched(TransactionCompleted::class, 2);
    Event::assertDispatched(fn (TransactionCompleted $event) => $event->user->id === $sender->id);
    Event::assertDispatched(fn (TransactionCompleted $event) => $event->user->id === $receiver->id);
});

test('a transfer fails due to insufficient funds', function () {
    // Arrange: Create users where the sender has an insufficient balance
    $sender   = User::factory()->create([ 'balance' => 50 ]);
    $receiver = User::factory()->create();
    $amount   = 100.00;

    // Act: Simulate the API call
    $response = $this->actingAs($sender)->postJson('/api/transactions', [
        'receiver_email' => $receiver->email,
        'amount'         => $amount,
    ]);

    // Assert: Check for the correct error response
    $response
        ->assertStatus(422) // Unprocessable Entity
        ->assertJson([
            'message' => 'Sender has insufficient funds.',
        ]);

    // Assert: Ensure balances have not changed
    $this->assertEquals(50, $sender->refresh()->balance);

    // Assert: Check that a 'failed' transaction record was created
    $this->assertDatabaseHas('transactions', [
        'sender_id'   => $sender->id,
        'receiver_id' => $receiver->id,
        'amount'      => $amount,
        'status'      => 'failed',
    ]);

    // Assert: No events should be dispatched on failure
    Event::assertNotDispatched(TransactionCompleted::class);
});

test('a user cannot transfer money to themselves', function () {
    // Arrange: Create a single user
    $user = User::factory()->create([ 'balance' => 1000 ]);

    // Act: Attempt to send money to their own email
    $response = $this->actingAs($user)->postJson('/api/transactions', [
        'receiver_email' => $user->email,
        'amount'         => 100,
    ]);

    // Assert: Check for a validation error
    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('receiver_email');
});

test('a transfer fails with an invalid amount', function ($amount) {
    $sender   = User::factory()->create();
    $receiver = User::factory()->create();

    $this->actingAs($sender)->postJson('/api/transactions', [
        'receiver_email' => $receiver->email,
        'amount'         => $amount,
    ])->assertStatus(422)->assertJsonValidationErrors('amount');
})->with([ 0, -100, 'not-a-number' ]);

test('a user can view their transaction history and balance', function () {
    // Arrange: Create a user and some other users
    $user  = User::factory()->create([ 'balance' => 1000 ]);
    $other = User::factory()->create();

    // Create transactions where the user is the sender and receiver
    Transaction::factory()->create([ 'sender_id' => $user->id, 'receiver_id' => $other->id ]);
    Transaction::factory()->create([ 'sender_id' => $other->id, 'receiver_id' => $user->id ]);
    Transaction::factory()->create([ 'sender_id' => $user->id, 'receiver_id' => $other->id ]);

    // Create a transaction not involving the user, which should not appear in the results
    Transaction::factory()->create();

    // Act: Make a GET request to the endpoint
    $response = $this->actingAs($user)->getJson('/api/transactions');

    // Assert: Check for a successful response and correct structure
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'balance',
            'transactions' => [
                'data' => [
                    '*' => [ 'id', 'sender', 'receiver', 'amount', 'status', 'type' ],
                ],
                'current_page',
                'last_page',
                'total',
            ],
        ])
        ->assertJsonPath('balance', '1000.0000') // Assert the balance is correct
        ->assertJsonCount(3, 'transactions.data'); // Assert only the user's transactions are returned
});

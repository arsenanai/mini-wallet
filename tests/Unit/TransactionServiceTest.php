<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionCompleted;
use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Mock the service class we are about to create
    $this->transactionService = $this->app->make(TransactionService::class);

    // Create the central commission account for tests
    $this->commissionAccount = User::factory()->create([
        'email'   => config('wallet.commission_account_email'),
        'balance' => 0,
    ]);

    // We need to fake events to test them
    Event::fake();
});

test('it successfully creates a transfer and updates balances', function () {
    // Arrange
    $sender   = User::factory()->create([ 'balance' => 200.00 ]);
    $receiver = User::factory()->create([ 'balance' => 50.00 ]);
    $amount   = 100.00;
    $commission = $amount * config('wallet.commission_rate');
    $totalDebit = $amount + $commission;

    // Act
    $transaction = $this->transactionService->createTransfer($sender, $receiver->email, $amount);

    // Assert
    // 1. Check sender's balance
    expect($sender->refresh()->balance)->toEqual(200.00 - $totalDebit);

    // 2. Check receiver's balance
    expect($receiver->refresh()->balance)->toEqual(50.00 + $amount);

    // 3. Check commission account's balance
    expect($this->commissionAccount->refresh()->balance)->toEqual($commission);

    // 4. Check transaction record in the database
    $this->assertDatabaseHas('transactions', [
        'sender_id'      => $sender->id,
        'receiver_id'    => $receiver->id,
        'amount'         => $amount,
        'commission_fee' => $commission,
        'status'         => TransactionStatus::COMPLETED->value,
        'type'           => TransactionType::TRANSFER->value,
        'reference_id'   => $transaction->reference_id,
    ]);

    // 5. Assert that events were dispatched for sender and receiver
    Event::assertDispatched(TransactionCompleted::class, 2);
    Event::assertDispatched(fn (TransactionCompleted $event) => $event->broadcastOn()[0]->name === 'private-App.Models.User.' . $sender->id);
    Event::assertDispatched(fn (TransactionCompleted $event) => $event->broadcastOn()[0]->name === 'private-App.Models.User.' . $receiver->id);
});

test('it throws exception and creates failed transaction for insufficient funds', function () {
    // Arrange
    $sender   = User::factory()->create([ 'balance' => 50.00 ]);
    $receiver = User::factory()->create([ 'balance' => 50.00 ]);
    $amount   = 100.00;

    // Assert
    // Expect an InsufficientFundsException to be thrown
    $this->expectException(InsufficientFundsException::class);

    // Act
    $this->transactionService->createTransfer($sender, $receiver->email, $amount);

    // Assertions after the action (these won't be reached, but are good for clarity)
    // 1. Balances should not have changed
    expect($sender->refresh()->balance)->toEqual(50.00);
    expect($receiver->refresh()->balance)->toEqual(50.00);
    expect($this->commissionAccount->refresh()->balance)->toEqual(0.0);

    // 2. A 'failed' transaction record should be created
    $this->assertDatabaseHas('transactions', [
        'sender_id'      => $sender->id,
        'receiver_id'    => $receiver->id,
        'amount'         => $amount,
        'commission_fee' => 0,
        'status'         => TransactionStatus::FAILED->value,
        'type'           => TransactionType::TRANSFER->value,
    ]);

    // 3. No events should be dispatched on failure
    Event::assertNotDispatched(TransactionCompleted::class);
});

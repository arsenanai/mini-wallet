<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

pest()->use(DatabaseTruncation::class);

/**
 * Set up the necessary users with specific initial balances before each test in this file.
 * This makes the test suite self-contained and independent of the main DatabaseSeeder,
 * and ensures consistent starting balances for tests.
 */
beforeEach(function () {
    // Only create the commission account, as it's common to all tests.
    // Test-specific users will be created within each test.
    User::factory()->create(['email' => config('wallet.commission_account_email'), 'balance' => 0]);
});

test('a user can successfully make a transfer and see real time updates', function () {
    // 1. Arrange: Create users with the exact balances needed for this test.
    $senderInitialBalance = 1000.00;
    $receiverInitialBalance = 500.00;
    $transferAmount = 100.00;

    $sender = User::factory()->create(['email' => 'user_a@email.com', 'name' => 'User A', 'password' => 'password', 'balance' => $senderInitialBalance]);
    $receiver = User::factory()->create(['email' => 'user_b@email.com', 'name' => 'User B', 'password' => 'password', 'balance' => $receiverInitialBalance]);

    // Calculate dynamic values based on the commission rate from the config
    $commissionRate = (float) config('wallet.commission_rate');
    $commission = $transferAmount * $commissionRate;
    $totalDebit = $transferAmount + $commission;
    $finalSenderBalance = $senderInitialBalance - $totalDebit;
    $finalReceiverBalance = $receiverInitialBalance + $transferAmount;

    $formattedTotalDebit = '-$' . number_format($totalDebit, 2); // Frontend formats to 2 decimal places
    $formattedReceiverBalance = '$' . number_format($finalReceiverBalance, 2, '.', ',');
    $formattedSenderBalance = '$' . number_format($finalSenderBalance, 2, '.', ',');

    // 2. Act & 3. Assert
    $this->browse(function (Browser $browser) use ($sender, $receiver, $formattedSenderBalance, $finalReceiverBalance, $formattedTotalDebit) {
        // --- Test Sender's Experience ---
        $browser->loginAs($sender)->visit('/dashboard')
            ->waitForLocation('/dashboard')
            ->assertSee('Dashboard')
            ->assertSeeIn('@balance-amount', '$1,000.00');

        // --- Sender performs the transfer ---
        $browser->waitFor('#receiver_email') // Wait for the transfer form to be ready
            ->type('#receiver_email', $receiver->email)
            ->type('#amount', '100')
            ->press('@send-money-button') // Use the new dusk selector
            ->waitForTextIn('[data-testid="success-message"]', 'Transfer successful!') // This is a frontend-only message from en.json
            ->assertSeeIn('[data-testid="success-message"]', 'Transfer successful!');

        // --- Assert sender's UI updated ---
        $transaction = Transaction::latest()->first();
        $this->assertNotNull($transaction);

        $browser->waitForTextIn('@balance-amount', $formattedSenderBalance)
            ->assertSeeIn('@balance-amount', $formattedSenderBalance)
            ->with('@transaction-history', function ($history) use ($transaction, $receiver, $formattedTotalDebit) {
                $history->waitFor("@transaction-{$transaction->id}", 5)
                        ->within("@transaction-{$transaction->id}", function ($item) use ($receiver, $formattedTotalDebit) {
                            $item->assertSee('Sent to '.$receiver->name)
                                 ->assertSee($formattedTotalDebit);
                        });
            });

        // --- Test Receiver's Experience (Sequentially) ---
        $browser->logout()->loginAs($receiver)->visit('/dashboard')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$' . number_format($finalReceiverBalance, 2, '.', ','))
            // Wait for the specific transaction item and assert its contents
            ->waitFor("@transaction-{$transaction->id}")
            ->within("@transaction-{$transaction->id}", function ($item) use ($sender) {
                $item->assertSee('Received from '.$sender->name)
                     ->assertSee('+$100.00');
            });
    });
});

test('a user sees an error when trying to send with insufficient funds', function () {
    // Arrange: Create users with the exact balances needed for this test.
    $sender = User::factory()->create(['email' => 'user_a@email.com', 'password' => 'password', 'balance' => 100.00]);
    $receiver = User::factory()->create(['email' => 'user_b@email.com', 'password' => 'password']);

    $this->browse(function (Browser $browser) use ($sender, $receiver) {
        $browser->logout()->visit('/login')
            ->waitFor('#email')
            ->type('#email', $sender->email)
            ->type('#password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$100.00') // Correct balance for this test
            ->type('#receiver_email', $receiver->email)
            ->type('#amount', '101') // Try to send more than the balance
            ->press('@send-money-button') // Use the new dusk selector
            ->waitForTextIn('[data-testid="general-error"]', __('messages.insufficient_funds'))
            ->assertSeeIn('[data-testid="general-error"]', __('messages.insufficient_funds'))
            ->assertSeeIn('@balance-amount', '$100.00'); // Balance should not change, assert 2 decimal places
    });
});

test('a user sees validation errors for invalid inputs', function () {
    // Arrange: Create users with the exact balances needed for this test.
    $sender = User::factory()->create(['email' => 'user_a@email.com', 'password' => 'password', 'balance' => 500.00]);
    $receiver = User::factory()->create(['email' => 'user_b@email.com', 'password' => 'password']);

    $this->browse(function (Browser $browser) use ($sender, $receiver) {
        $browser->logout()->visit('/login')
            ->waitFor('#email')
            ->type('#email', $sender->email)
            ->type('#password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$500.00');

        // Invalid recipient - wait for processing to finish before asserting
        $browser->type('#receiver_email', 'nonexistent@user.com')
            ->type('#amount', '50')
            ->press('@send-money-button')
            ->waitForTextIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.')
            ->assertSeeIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.');

        // Sending to self
        $browser->clear('#receiver_email')->type('#receiver_email', $sender->email)->press('@send-money-button')
            ->waitForTextIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.')
            ->assertSeeIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.');

        // Invalid amount (zero)
        // Provide a valid recipient to ensure we are only testing the amount validation.
        $browser->clear('#receiver_email')->type('#receiver_email', $receiver->email)
            ->clear('#amount')->type('#amount', '0')
            ->waitForTextIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'))
            ->assertSeeIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'));

        // Invalid amount (negative) - clear email field to ensure a fresh validation state
        $browser->clear('#receiver_email')->type('#receiver_email', $receiver->email)
            ->clear('#amount')->type('#amount', '-50')
            ->waitForTextIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'))
            ->assertSeeIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'));

        // Final check: Balance should not have changed
        $browser->assertSeeIn('@balance-amount', '$500.00');
    });
});

test('a user can receive a transfer and see real time updates', function () {
    // 1. Arrange: Prepare database data
    $receiverInitialBalance = 500.00;
    $transferAmount = 75.00;

    $receiver = User::factory()->create(['name' => 'Receiver User', 'balance' => $receiverInitialBalance]);
    $sender = User::factory()->create(['name' => 'Sender User', 'balance' => 200.00]);

    $finalReceiverBalance = $receiverInitialBalance + $transferAmount;
    $formattedFinalBalance = '$' . number_format($finalReceiverBalance, 2, '.', ',');
    $formattedTransferAmount = '+$' . number_format($transferAmount, 2);

    $this->browse(function (Browser $browser) use ($receiver, $sender, $transferAmount, $formattedFinalBalance, $formattedTransferAmount) {
        // 2. Act: The receiver logs in and opens the dashboard
        $browser->loginAs($receiver)
            ->maximize() // Maximize the browser window for better visibility
            ->visit('/dashboard')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$500.00');

        // 3. Act: Mimic another user sending a transfer programmatically
        $transactionService = app(\App\Services\TransactionService::class);
        $transaction = $transactionService->createTransfer($sender, $receiver->email, $transferAmount);

        // 4. Assert: The receiver sees their balance update in real-time via Echo
        $browser->waitForTextIn('@balance-amount', $formattedFinalBalance, 10)
                ->assertSeeIn('@balance-amount', $formattedFinalBalance);

        // 5. Assert: The receiver sees the new transaction appear in their history
        $browser->waitFor("@transaction-{$transaction->id}", 5)
                ->within("@transaction-{$transaction->id}", function ($item) use ($sender, $formattedTransferAmount) {
                    $item->assertSee('Received from ' . $sender->name)
                         ->assertSee($formattedTransferAmount);
                });
    });
});
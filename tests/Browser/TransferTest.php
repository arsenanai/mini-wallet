<?php

declare(strict_types=1);

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

pest()->use(DatabaseTruncation::class);

beforeEach(function () {
    User::factory()->create(['email' => config('wallet.commission_account_email'), 'balance' => 0]);
});

test('a user can successfully make a transfer and see real time updates', function () {
    $senderInitialBalance = 1000.00;
    $receiverInitialBalance = 500.00;
    $transferAmount = 100.00;

    $sender = User::factory()->create(['email' => 'user_a@email.com', 'name' => 'User A', 'password' => 'password', 'balance' => $senderInitialBalance]);
    $receiver = User::factory()->create(['email' => 'user_b@email.com', 'name' => 'User B', 'password' => 'password', 'balance' => $receiverInitialBalance]);

    $commissionRate = (float) config('wallet.commission_rate');
    $commission = $transferAmount * $commissionRate;
    $totalDebit = $transferAmount + $commission;
    $finalSenderBalance = $senderInitialBalance - $totalDebit;
    $finalReceiverBalance = $receiverInitialBalance + $transferAmount;

    $formattedTotalDebit = '-$' . number_format($totalDebit, 2); // Frontend formats to 2 decimal places
    $formattedReceiverBalance = '$' . number_format($finalReceiverBalance, 2, '.', ',');
    $formattedSenderBalance = '$' . number_format($finalSenderBalance, 2, '.', ',');

    $this->browse(function (Browser $browser) use ($sender, $receiver, $formattedSenderBalance, $finalReceiverBalance, $formattedTotalDebit) {
        // --- Test Sender's Experience ---
        $browser->loginAs($sender)->visit('/dashboard')
            ->waitForLocation('/dashboard')
            ->assertSee('Dashboard')
            ->assertSeeIn('@balance-amount', '$1,000.00');

        $browser->waitFor('#receiver_email')
            ->type('#receiver_email', $receiver->email)
            ->type('#amount', '100')
            ->press('@send-money-button')
            ->waitForTextIn('[data-testid="success-message"]', 'Transfer successful!')
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
            ->waitFor("@transaction-{$transaction->id}")
            ->within("@transaction-{$transaction->id}", function ($item) use ($sender) {
                $item->assertSee('Received from '.$sender->name)
                     ->assertSee('+$100.00');
            });
    });
});

test('a user sees an error when trying to send with insufficient funds', function () {
    $sender = User::factory()->create(['email' => 'user_a@email.com', 'password' => 'password', 'balance' => 100.00]);
    $receiver = User::factory()->create(['email' => 'user_b@email.com', 'password' => 'password']);

    $this->browse(function (Browser $browser) use ($sender, $receiver) {
        $browser->logout()->visit('/login')
            ->waitFor('#email')
            ->type('#email', $sender->email)
            ->type('#password', 'password')
            ->press('Log in')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$100.00')
            ->type('#receiver_email', $receiver->email)
            ->type('#amount', '101')
            ->press('@send-money-button')
            ->waitForTextIn('[data-testid="general-error"]', __('messages.insufficient_funds'))
            ->assertSeeIn('[data-testid="general-error"]', __('messages.insufficient_funds'))
            ->assertSeeIn('@balance-amount', '$100.00');
    });
});

test('a user sees validation errors for invalid inputs', function () {
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

        $browser->type('#receiver_email', 'nonexistent@user.com')
            ->type('#amount', '50')
            ->press('@send-money-button')
            ->waitForTextIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.')
            ->assertSeeIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.');

        $browser->clear('#receiver_email')->type('#receiver_email', $sender->email)->press('@send-money-button')
            ->waitForTextIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.')
            ->assertSeeIn('[data-testid="receiver-email-error"]', 'The selected receiver email is invalid.');

        // Provide a valid recipient to ensure we are only testing the amount validation.
        $browser->clear('#receiver_email')->type('#receiver_email', $receiver->email)
            ->clear('#amount')->type('#amount', '0')
            ->waitForTextIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'))
            ->assertSeeIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'));

        $browser->clear('#receiver_email')->type('#receiver_email', $receiver->email)
            ->clear('#amount')->type('#amount', '-50')
            ->waitForTextIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'))
            ->assertSeeIn('[data-testid="amount-error"]', __('The amount must be greater than 0.'));

        $browser->assertSeeIn('@balance-amount', '$500.00');
    });
});

test('a user can receive a transfer and see real time updates', function () {
    $receiverInitialBalance = 500.00;
    $transferAmount = 75.00;

    $receiver = User::factory()->create(['name' => 'Receiver User', 'balance' => $receiverInitialBalance]);
    $sender = User::factory()->create(['name' => 'Sender User', 'balance' => 200.00]);

    $finalReceiverBalance = $receiverInitialBalance + $transferAmount;
    $formattedFinalBalance = '$' . number_format($finalReceiverBalance, 2, '.', ',');
    $formattedTransferAmount = '+$' . number_format($transferAmount, 2);

    $this->browse(function (Browser $browser) use ($receiver, $sender, $transferAmount, $formattedFinalBalance, $formattedTransferAmount) {
        $browser->loginAs($receiver)
            ->maximize()
            ->visit('/dashboard')
            ->waitForLocation('/dashboard')
            ->assertSeeIn('@balance-amount', '$500.00');

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

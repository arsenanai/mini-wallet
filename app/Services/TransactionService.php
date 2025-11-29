<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Events\TransactionCompleted;
use App\Exceptions\InsufficientFundsException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TransactionService
{
    /**
     * Retrieves the transaction history and current balance for a given user.
     *
     * @param User $user
     *
     * @return array{balance: string, transactions: \Illuminate\Contracts\Pagination\LengthAwarePaginator}
     */
    public function getTransactionHistory(User $user): array
    {
        $transactions = Transaction::where('sender_id', $user->id)
                                   ->orWhere('receiver_id', $user->id)
                                   ->with([ 'sender:id,name,email', 'receiver:id,name,email' ])
                                   ->latest()
                                   ->paginate(15);

        return [
            'balance'      => (string) $user->balance,
            'transactions' => $transactions->toArray(),
        ];
    }

    /**
     * Creates a transfer between two users.
     *
     * @param User $sender
     * @param User $receiver
     * @param float $amount
     *
     * @return Transaction
     * @throws InsufficientFundsException|Throwable
     */
    public function createTransfer(User $sender, string $receiverEmail, float $amount): Transaction
    {
        $commissionRate    = (float) config('wallet.commission_rate');
        $commission        = $amount * $commissionRate;
        $totalDebit        = $amount + $commission;
        $commissionAccount = User::where('email', config('wallet.commission_account_email'))->firstOrFail();
        $receiver          = User::where('email', $receiverEmail)->firstOrFail();

        // Check for sufficient funds before starting the transaction
        if ($sender->balance < $totalDebit) {
            // Create a failed transaction record for auditing
            $transaction = Transaction::create([
                'sender_id'      => $sender->id,
                'receiver_id'    => $receiver->id,
                'amount'         => $amount,
                'commission_fee' => 0,
                'status'         => TransactionStatus::FAILED,
                'type'           => TransactionType::TRANSFER,
                'reference_id' => (string) Str::uuid(),
            ]);

            throw new InsufficientFundsException(__('messages.insufficient_funds'));
        }

        return DB::transaction(function () use ($sender, $receiver, $amount, $commission, $totalDebit, $commissionAccount) {
            // Lock the involved user rows within the transaction to prevent race conditions
            $sender->lockForUpdate()->get();
            $receiver->lockForUpdate()->get();
            $commissionAccount->lockForUpdate()->get();

            // Perform the balance updates
            $sender->decrement('balance', $totalDebit);
            $receiver->increment('balance', $amount);
            $commissionAccount->increment('balance', $commission);

            // Create the transaction record
            $transaction = Transaction::create([
                'sender_id'      => $sender->id,
                'receiver_id'    => $receiver->id,
                'amount'         => $amount,
                'commission_fee' => $commission,
                'status'         => TransactionStatus::COMPLETED,
                'type'           => TransactionType::TRANSFER,
                'reference_id'   => (string) Str::uuid(),
            ]);

            // Dispatch events for real-time updates
            event(new TransactionCompleted($transaction, $sender));
            event(new TransactionCompleted($transaction, $receiver));

            return $transaction;
        });
    }
}

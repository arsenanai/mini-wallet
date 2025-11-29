<?php


declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | General Application Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various messages that we need
    | to display to the user. You are free to modify these language lines
    | according to your application's requirements.
    |
    */

    // API/Service level messages
    'transaction_successful' => 'Your transfer was successful.',
    'transaction_failed' => 'Your transfer failed. Please try again.',
    'insufficient_funds' => 'You have insufficient funds to complete this transfer.',
    'self_transfer_not_allowed' => 'You cannot transfer money to your own account.',
    'receiver_not_found' => 'The recipient with the specified email was not found.',
    'invalid_amount' => 'The transfer amount must be a positive number.',
    'generic_error' => 'An unexpected error occurred. Please contact support.',

    // Frontend UI labels
    'balance' => 'Current Balance',
    'transaction_history' => 'Transaction History',
    'new_transfer' => 'New Transfer',
    'recipient_email' => 'Recipient Email',
    'amount' => 'Amount',
    'send' => 'Send',
];

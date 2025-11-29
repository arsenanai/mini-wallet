<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Wallet Settings
    |--------------------------------------------------------------------------
    |
    | This file is for storing the settings for the wallet service.
    |
    */

    'commission_rate'          => (float) env('APP_COMMISSION_RATE', 0.015), // 1.5%
    'commission_account_email' => env('COMMISSION_ACCOUNT_EMAIL', 'commission@wallet.app'),
];

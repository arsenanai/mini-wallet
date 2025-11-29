<?php

declare(strict_types=1);

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('transactions', [TransactionController::class, 'store'])->name(
        'api.transactions.store',
    );
    Route::get('transactions', [TransactionController::class, 'index']);
});

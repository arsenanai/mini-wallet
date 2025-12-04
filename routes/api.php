<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name(
    'api.login',
);

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('transactions', [TransactionController::class, 'store'])->name(
        'api.transactions.store',
    );
    Route::get('transactions/{transaction}', [
        TransactionController::class, 'show'
    ])->name('api.transactions.show');
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ])->name('api.logout');
});

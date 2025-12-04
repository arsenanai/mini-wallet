<?php

namespace App\Services;

use App\Http\Resources\TransactionResource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Get the data required for the user's dashboard.
     *
     * @return array{balance: float, transactions: \Illuminate\Http\Resources\Json\AnonymousResourceCollection}
     */
    public function getDashboardData(?Authenticatable $user = null): array
    {
        /** @var \App\Models\User $user */
        $user = $user ?? Auth::user();

        $transactions = $user->transactions()
            ->with(['sender', 'receiver']) // Eager-load relationships
            ->latest()
            ->paginate(15);

        return [
            'balance' => (string) $user->balance,
            'transactions' => TransactionResource::collection($transactions),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\InsufficientFundsException;
use App\Http\Requests\TransferRequest;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function index(): JsonResponse
    {
        $data = $this->transactionService->getTransactionHistory(Auth::user());

        return response()->json($data);
    }

    public function store(TransferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $transaction = $this->transactionService->createTransfer(Auth::user(), $validated['receiver_email'], (float) $validated['amount']);

            return response()->json([
                'message'     => __('messages.transfer_successful'),
                'transaction' => $transaction,
            ], 201);
        } catch (InsufficientFundsException $e) {
            return response()->json([ 'message' => $e->getMessage() ], 422);
        }
    }
}

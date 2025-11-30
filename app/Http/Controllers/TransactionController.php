<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $history = $this->transactionService->getTransactionHistory($user);

        return response()->json($history);
    }

    public function store(TransferRequest $request): JsonResponse
    {
        $sender = $request->user();
        $validatedData = $request->validated();

        $transaction = $this->transactionService->createTransfer($sender, $validatedData['receiver_email'], (float) $validatedData['amount']);

        return response()->json([
            'message' => __('messages.transaction_successful'),
            'transaction' => new TransactionResource($transaction),
        ], 201);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read string $reference_id
 * @property-read int $sender_id
 * @property-read int $receiver_id
 * @property-read float $amount
 * @property-read float $commission_fee
 * @property-read \App\Enums\TransactionType $type
 * @property-read \App\Enums\TransactionStatus $status
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User $sender
 * @property-read \App\Models\User $receiver
 * @mixin \App\Models\Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_id' => $this->reference_id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'amount' => $this->amount,
            'commission_fee' => $this->commission_fee,
            'type' => $this->type,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i'),
            'sender' => new UserResource($this->whenLoaded('sender')),
            'receiver' => new UserResource($this->whenLoaded('receiver')),
        ];
    }
}

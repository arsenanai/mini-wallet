<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ensures only authenticated users can make transfers.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'receiver_email' => [
                'required',
                'email',
                'exists:users,email',
                // Prevent users from transferring to themselves
                Rule::notIn([$this->user()->email]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('refund', $this->route('paymentTransaction'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}

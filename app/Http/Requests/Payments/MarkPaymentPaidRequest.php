<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class MarkPaymentPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('markPaid', $this->route('paymentTransaction'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}

<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('paymentMethod'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['sometimes', 'boolean'],
            'test_mode' => ['sometimes', 'boolean'],
            'label_en' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_order_amount'],
            'allowed_order_types' => ['nullable', 'array'],
            'allowed_order_types.*' => [Rule::in(['delivery', 'pickup'])],

            // Write-only credential fields (Phase 2, Moyasar). Blank means
            // "keep the existing stored value" — see PaymentMethodController
            // ::update(). Never populated back into the form from a stored
            // value.
            'secret_key_test' => ['nullable', 'string', 'max:255'],
            'secret_key_live' => ['nullable', 'string', 'max:255'],
            'webhook_secret_test' => ['nullable', 'string', 'max:255'],
            'webhook_secret_live' => ['nullable', 'string', 'max:255'],
        ];
    }
}

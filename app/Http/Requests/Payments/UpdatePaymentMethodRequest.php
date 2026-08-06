<?php

namespace App\Http\Requests\Payments;

use App\Payments\Money;
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
        // decimalPlacesRule() rejects a min/max order amount with more
        // decimal places than the site currency allows before it reaches
        // Money::toMinor() in the controller, which would otherwise throw
        // uncaught — see the full-project audit.
        $decimalRule = Money::decimalPlacesRule(config('site.currency'));

        return [
            'enabled' => ['sometimes', 'boolean'],
            'test_mode' => ['sometimes', 'boolean'],
            'label_en' => ['nullable', 'string', 'max:120'],
            'label_ar' => ['nullable', 'string', 'max:120'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0', $decimalRule],
            'max_order_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_order_amount', $decimalRule],
            'allowed_order_types' => ['nullable', 'array'],
            'allowed_order_types.*' => [Rule::in(['delivery', 'pickup'])],

            'icon' => ['nullable', 'image', 'max:512'],
            'remove_icon' => ['sometimes', 'boolean'],

            // Write-only credential fields (Phase 2, Moyasar). Blank means
            // "keep the existing stored value" — see PaymentMethodController
            // ::update(). Never populated back into the form from a stored
            // value.
            'secret_key_test' => ['nullable', 'string', 'max:255'],
            'secret_key_live' => ['nullable', 'string', 'max:255'],
            'webhook_secret_test' => ['nullable', 'string', 'max:255'],
            'webhook_secret_live' => ['nullable', 'string', 'max:255'],

            // Plain (non-secret) driver config, Phase 3. Deliberately free
            // text, not Rule::in([...]) — no settled "supported countries"
            // list exists to validate against.
            'country' => ['nullable', 'string', 'max:80'],
            'local_source_id' => ['nullable', 'string', 'max:80'],
        ];
    }
}

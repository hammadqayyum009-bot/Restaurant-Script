<?php

namespace App\Http\Requests\Payments;

use App\Payments\Money;
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
            // decimalPlacesRule() rejects an amount with more decimal places
            // than the transaction's own currency allows (e.g. "10.999" for
            // SAR) before it ever reaches Money::toMinor(), which would
            // otherwise throw uncaught.
            'amount' => ['required', 'numeric', 'gt:0', Money::decimalPlacesRule($this->route('paymentTransaction')->currency)],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        $currency = $this->route('paymentTransaction')?->currency;

        return [
            'amount.decimal' => $currency
                ? "The refund amount has more decimal places than {$currency} allows."
                : 'The refund amount has more decimal places than this currency allows.',
        ];
    }
}

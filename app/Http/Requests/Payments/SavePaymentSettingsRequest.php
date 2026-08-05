<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class SavePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-payments');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'max_attempts_per_order' => ['required', 'integer', 'min:1', 'max:20'],
            // Enforced here, not just documented: a threshold below 5
            // minutes would make reconciliation race the customer's own
            // callback and verify-on-page-load fallback, marking a payment
            // "stuck" while it is still genuinely in flight.
            'stuck_after_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
        ];
    }
}

<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Drafts are the only billing documents that can be updated at all — see
 * App\Policies\BillingDocumentPolicy::update(), which denies this once a
 * document is issued.
 *
 * Deliberately one route, one FormRequest, branching on whether the draft is
 * order-linked or standalone (order_id null) — not two separate update
 * routes — so the route surface stays exactly what Batch 2 already shipped.
 * An order-linked draft has nothing of its own to edit but notes: its lines
 * only exist from the moment it's issued (built fresh from the order by
 * Reconciler). A standalone draft has no order behind it at all, so its
 * buyer details and line items are the only source of truth and must be
 * editable here.
 */
class UpdateDraftDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', $this->route('document'));
    }

    public function isStandalone(): bool
    {
        return $this->route('document')?->order_id === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if (! $this->isStandalone()) {
            return [
                'notes_en' => ['nullable', 'string', 'max:600'],
                'notes_ar' => ['nullable', 'string', 'max:600'],
            ];
        }

        return [
            'currency' => ['required', 'string', Rule::in(array_keys(config('billing.currencies')))],
            'valid_until' => ['nullable', 'date', 'after_or_equal:today'],

            'buyer_name_en' => ['required', 'string', 'max:160'],
            'buyer_name_ar' => ['nullable', 'string', 'max:160'],
            'buyer_vat_number' => ['nullable', 'regex:/^\d{15}$/'],
            'buyer_cr_number' => ['nullable', 'string', 'max:40'],
            'buyer_phone' => ['nullable', 'string', 'max:40'],
            'buyer_email' => ['nullable', 'email', 'max:160'],
            'buyer_address_en' => ['nullable', 'string', 'max:600'],
            'buyer_address_ar' => ['nullable', 'string', 'max:600'],

            'notes_en' => ['nullable', 'string', 'max:600'],
            'notes_ar' => ['nullable', 'string', 'max:600'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.name_en' => ['required', 'string', 'max:200'],
            'lines.*.name_ar' => ['nullable', 'string', 'max:200'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.vat_category' => ['required', Rule::in(config('billing.vat_categories'))],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one line item before saving.',
            'lines.min' => 'Add at least one line item before saving.',
            'buyer_vat_number.regex' => 'The buyer VAT registration number must be exactly 15 digits.',
        ];
    }
}

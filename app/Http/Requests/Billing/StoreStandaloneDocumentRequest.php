<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A document created with no underlying order — a catering quote, a proforma
 * for a B2B customer, a delivery note for an off-platform sale. Tax invoices
 * are deliberately excluded here: they stay order-linked only (see
 * StoreOrderDocumentRequest), since a tax invoice with nothing behind it to
 * reconcile against has no real-world grounding in this app's model.
 */
class StoreStandaloneDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-billing');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(['quotation', 'proforma', 'delivery_note'])],
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

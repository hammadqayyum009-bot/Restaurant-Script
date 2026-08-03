<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderDocumentRequest extends FormRequest
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
        $isStandard = $this->input('document_type') === 'standard_tax_invoice';

        return [
            'document_type' => ['required', Rule::in([
                'simplified_tax_invoice', 'standard_tax_invoice', 'quotation', 'proforma', 'delivery_note',
            ])],

            // A standard (B2B) tax invoice must carry the buyer's own VAT
            // registration — a simplified (B2C) one does not ask for it.
            'buyer_name_en' => [$isStandard ? 'required' : 'nullable', 'string', 'max:160'],
            'buyer_name_ar' => ['nullable', 'string', 'max:160'],
            'buyer_vat_number' => [$isStandard ? 'required' : 'nullable', 'regex:/^\d{15}$/'],
            'buyer_cr_number' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'buyer_vat_number.required' => 'A standard tax invoice requires the buyer\'s VAT registration number.',
            'buyer_vat_number.regex' => 'The buyer VAT registration number must be exactly 15 digits.',
        ];
    }
}

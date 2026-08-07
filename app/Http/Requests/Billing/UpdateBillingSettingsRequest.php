<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBillingSettingsRequest extends FormRequest
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
        $currencies = array_keys(config('billing.currencies'));

        return [
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'prices_include_vat' => ['nullable', 'boolean'],
            'default_currency' => ['required', 'string', Rule::in($currencies)],

            // Strict, per Section I — a wrong-length VAT number on a tax
            // invoice is a real compliance failure, unlike the other fields.
            'vat_number' => ['nullable', 'regex:/^\d{15}$/'],

            // Format-level only, never blocking (varies by jurisdiction).
            'cr_number' => ['nullable', 'string', 'max:40'],
            'building_number' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'additional_number' => ['nullable', 'string', 'max:10'],

            'seller_name_en' => ['required', 'string', 'max:120'],
            'seller_name_ar' => ['required', 'string', 'max:120'],
            'street_en' => ['nullable', 'string', 'max:120'],
            'street_ar' => ['nullable', 'string', 'max:120'],
            'district_en' => ['nullable', 'string', 'max:120'],
            'district_ar' => ['nullable', 'string', 'max:120'],
            'city_en' => ['nullable', 'string', 'max:80'],
            'city_ar' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'footer_en' => ['nullable', 'string', 'max:600'],
            'footer_ar' => ['nullable', 'string', 'max:600'],

            'show_hijri' => ['nullable', 'boolean'],
            'number_padding' => ['required', 'integer', 'min:1', 'max:10'],
            'number_format' => ['required', 'string', 'max:60'],
            'prefix_quotation' => ['required', 'string', 'max:10'],
            'prefix_proforma' => ['required', 'string', 'max:10'],
            'prefix_simplified_tax_invoice' => ['required', 'string', 'max:10'],
            'prefix_standard_tax_invoice' => ['required', 'string', 'max:10'],
            'prefix_credit_note' => ['required', 'string', 'max:10'],
            'prefix_delivery_note' => ['required', 'string', 'max:10'],

            // MIME *and* extension *and* size — an extension-spoofed upload
            // (e.g. a .php file renamed to look like a .jpg) fails "mimetypes"
            // even when "mimes" alone would have been fooled by the extension.
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'vat_number.regex' => 'The VAT registration number must be exactly 15 digits.',
        ];
    }
}

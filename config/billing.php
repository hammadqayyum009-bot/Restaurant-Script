<?php

return [
    /**
     * Minor-unit exponent per currency code. Money::toMinor()/toDecimal() read
     * this — never assume 2. KWD, BHD and OMR use 3; JPY uses 0.
     */
    'currencies' => [
        'SAR' => 2,
        'AED' => 2,
        'QAR' => 2,
        'USD' => 2,
        'EUR' => 2,
        'GBP' => 2,
        'EGP' => 2,
        'PKR' => 2,
        'KWD' => 3,
        'BHD' => 3,
        'OMR' => 3,
        'JPY' => 0,
    ],

    'document_types' => [
        'quotation',
        'proforma',
        'simplified_tax_invoice',
        'standard_tax_invoice',
        'credit_note',
        'delivery_note',
    ],

    'vat_categories' => ['S', 'Z', 'E', 'O'],

    'number_format' => '{PREFIX}-{YYYY}-{NUMBER}',

    'max_allocation_attempts' => 3,

    /**
     * Fallback values read by App\Services\Billing\BillingSettings whenever the
     * matching `billing.*` row is absent from the settings table — the module
     * works, unbranded, before a single field is saved.
     */
    'defaults' => [
        'billing.vat_rate' => '15',
        'billing.prices_include_vat' => '1',
        'billing.default_currency' => 'SAR',
        'billing.vat_number' => '',
        'billing.cr_number' => '',
        'billing.seller_name_en' => '',
        'billing.seller_name_ar' => '',
        'billing.building_number' => '',
        'billing.street_en' => '',
        'billing.street_ar' => '',
        'billing.district_en' => '',
        'billing.district_ar' => '',
        'billing.city_en' => '',
        'billing.city_ar' => '',
        'billing.postal_code' => '',
        'billing.additional_number' => '',
        'billing.phone' => '',
        'billing.email' => '',
        'billing.logo' => '',
        'billing.footer_en' => '',
        'billing.footer_ar' => '',
        'billing.show_hijri' => '0',
        'billing.number_padding' => '6',
        'billing.number_format' => '{PREFIX}-{YYYY}-{NUMBER}',
        'billing.prefix_quotation' => 'QT',
        'billing.prefix_proforma' => 'PF',
        'billing.prefix_simplified_tax_invoice' => 'INV',
        'billing.prefix_standard_tax_invoice' => 'TI',
        'billing.prefix_credit_note' => 'CN',
        'billing.prefix_delivery_note' => 'DN',
    ],
];

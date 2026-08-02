<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\UpdateBillingSettingsRequest;
use App\Services\ActivityLogger;
use App\Services\Billing\BillingSettings;
use App\Services\Uploader;

class BillingSettingsController extends Controller
{
    public function __construct(
        protected BillingSettings $billing,
        protected Uploader $uploader,
        protected ActivityLogger $activity,
    ) {
    }

    public function edit()
    {
        return view('admin.settings.billing', [
            'billing' => $this->billing,
            'currencies' => array_keys(config('billing.currencies')),
            'documentTypes' => config('billing.document_types'),
            'vatMismatch' => $this->vatMismatchWarning(),
            'formatWarnings' => $this->formatWarnings(),
        ]);
    }

    public function update(UpdateBillingSettingsRequest $request)
    {
        $data = $request->validated();
        unset($data['logo'], $data['remove_logo']);

        $values = [];

        foreach ($data as $key => $value) {
            $values['billing.'.$key] = match (true) {
                $key === 'prices_include_vat', $key === 'show_hijri' => $request->boolean($key) ? '1' : '0',
                default => $value,
            };
        }

        $this->billing->setMany($values);
        $this->saveLogo($request);

        $this->activity->log('settings', 'Saved billing settings');

        return back()->with('success', 'Billing settings saved.');
    }

    protected function saveLogo(UpdateBillingSettingsRequest $request): void
    {
        if ($request->hasFile('logo')) {
            $this->uploader->delete($this->billing->logo());
            $this->billing->set('billing.logo', $this->uploader->storeRandom($request->file('logo'), 'branding'));

            return;
        }

        if ($request->boolean('remove_logo')) {
            $this->uploader->delete($this->billing->logo());
            $this->billing->set('billing.logo', null);
        }
    }

    /**
     * Rule D3 — the two VAT sources are allowed to disagree (the storefront's
     * own tax is untouched by this module), but the admin must be told plainly
     * rather than have one silently override the other.
     */
    protected function vatMismatchWarning(): ?string
    {
        if ($this->billing->pricesIncludeVat()) {
            return null;
        }

        $shopRate = (float) config('shop.tax_percent');
        $billingRate = (float) $this->billing->vatRate();

        if (abs($shopRate - $billingRate) < 0.0001) {
            return null;
        }

        return sprintf(
            'The storefront tax rate (Settings → Shop: %s%%) does not match the billing VAT rate (%s%%). '.
            'Documents will report %s%% regardless of what the storefront charged — check this is intended.',
            rtrim(rtrim(number_format($shopRate, 2), '0'), '.'),
            rtrim(rtrim(number_format($billingRate, 2), '0'), '.'),
            rtrim(rtrim(number_format($billingRate, 2), '0'), '.'),
        );
    }

    /**
     * Format-level, non-blocking notices for fields whose shape varies by
     * jurisdiction and must never stop a save (unlike the VAT number).
     *
     * @return array<string, string>
     */
    protected function formatWarnings(): array
    {
        $warnings = [];

        $cr = (string) $this->billing->get('billing.cr_number', '');
        if ($cr !== '' && ! preg_match('/^\d{7,12}$/', $cr)) {
            $warnings['cr_number'] = 'Commercial Registration numbers are usually 7-12 digits — double-check this value.';
        }

        $building = (string) $this->billing->get('billing.building_number', '');
        if ($building !== '' && ! preg_match('/^\d{4}$/', $building)) {
            $warnings['building_number'] = 'The Saudi National Address building number is usually 4 digits.';
        }

        $postal = (string) $this->billing->get('billing.postal_code', '');
        if ($postal !== '' && ! preg_match('/^\d{5}$/', $postal)) {
            $warnings['postal_code'] = 'The postal code is usually 5 digits.';
        }

        $additional = (string) $this->billing->get('billing.additional_number', '');
        if ($additional !== '' && ! preg_match('/^\d{4}$/', $additional)) {
            $warnings['additional_number'] = 'The additional number is usually 4 digits.';
        }

        return $warnings;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Settings;
use App\Services\Uploader;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(
        protected Settings $settings,
        protected Uploader $uploader,
        protected ActivityLogger $activity,
    ) {}

    /* ---------------- Public site ---------------- */

    public function site()
    {
        return view('admin.settings.site');
    }

    public function saveSite(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:200'],
            'site_meta_description' => ['nullable', 'string', 'max:300'],
            'site_phone' => ['nullable', 'string', 'max:40'],
            'site_whatsapp' => ['nullable', 'string', 'max:30'],
            'site_email' => ['nullable', 'email', 'max:150'],
            'site_address' => ['nullable', 'string', 'max:255'],
            // Unvalidated before, an unrecognized code silently broke
            // Money::exponent() everywhere it's read — including Payments,
            // since the Fix 2 currency unification. config('currencies') is
            // the same shared exponent table both Billing and Payments
            // already read, so anything not in it can't work downstream.
            'site_currency' => ['required', 'string', Rule::in(array_keys(config('currencies')))],
            'site_hours' => ['nullable', 'string', 'max:150'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_twitter' => ['nullable', 'string', 'max:255'],
            'social_tiktok' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ], [
            'site_currency.in' => 'Unrecognized currency code. Supported: '.implode(', ', array_keys(config('currencies'))).'.',
        ]);

        $this->settings->setMany($data, 'site');
        $this->handleImage($request, 'logo', 'site_logo', 'branding');
        $this->handleImage($request, 'favicon', 'site_favicon', 'branding');

        $this->activity->settings('site');

        return back()->with('success', 'Website settings saved.');
    }

    /* ---------------- Admin panel branding ---------------- */

    public function panel()
    {
        return view('admin.settings.panel');
    }

    public function savePanel(Request $request)
    {
        $data = $request->validate([
            'panel_name' => ['required', 'string', 'max:120'],
            'panel_short_name' => ['nullable', 'string', 'max:40'],
            'panel_footer_note' => ['nullable', 'string', 'max:150'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ]);

        $this->settings->setMany($data, 'panel');
        $this->handleImage($request, 'logo', 'panel_logo', 'branding');
        $this->handleImage($request, 'favicon', 'panel_favicon', 'branding');

        $this->activity->settings('panel');

        return back()->with('success', 'Admin panel settings saved.');
    }

    /* ---------------- Search engines & sharing ---------------- */

    public function seo()
    {
        return view('admin.settings.seo');
    }

    public function saveSeo(Request $request)
    {
        $data = $request->validate([
            'site_meta_description' => ['nullable', 'string', 'max:300'],
            'seo_cuisine' => ['nullable', 'string', 'max:80'],
            'seo_price_range' => ['nullable', 'string', 'max:10'],
            'seo_google_verification' => ['nullable', 'string', 'max:120'],
            'share_image' => ['nullable', 'image', 'max:3072'],
        ]);

        $data['seo_indexable'] = $request->boolean('seo_indexable') ? '1' : '0';

        $this->settings->setMany($data, 'seo');
        $this->handleImage($request, 'share_image', 'seo_share_image', 'branding');

        $this->activity->settings('SEO');

        return back()->with('success', 'Search engine settings saved.');
    }

    /* ---------------- Ordering, delivery, bookings ---------------- */

    public function shop()
    {
        return view('admin.settings.shop');
    }

    public function saveShop(Request $request)
    {
        $data = $request->validate([
            'shop_delivery_fee' => ['required', 'numeric', 'min:0'],
            'shop_min_order' => ['required', 'numeric', 'min:0'],
            'shop_tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'shop_reservation_open' => ['nullable', 'string', 'max:10'],
            'shop_reservation_close' => ['nullable', 'string', 'max:10'],
            'shop_reservation_max_guests' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        $toggles = [
            'shop_enable_delivery', 'shop_enable_pickup',
            'shop_reservations_enabled', 'shop_reviews_enabled', 'shop_reviews_auto_approve',
        ];

        foreach ($toggles as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        $this->settings->setMany($data, 'shop');

        $this->activity->settings('shop');

        return back()->with('success', 'Ordering settings saved.');
    }

    /* ---------------- helpers ---------------- */

    /**
     * Stores an uploaded image under the given settings key, replacing (and
     * deleting) whatever was there before.
     */
    protected function handleImage(Request $request, string $field, string $key, string $folder): void
    {
        if ($request->boolean('remove_'.$field)) {
            $this->uploader->delete($this->settings->get($key));
            $this->settings->forget($key);

            return;
        }

        if (! $request->hasFile($field)) {
            return;
        }

        $this->uploader->delete($this->settings->get($key));
        $this->settings->set($key, $this->uploader->store($request->file($field), $folder), 'branding');
    }
}

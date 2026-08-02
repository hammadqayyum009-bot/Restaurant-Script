<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use App\Services\Uploader;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(protected Settings $settings, protected Uploader $uploader)
    {
    }

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
            'site_currency' => ['required', 'string', 'max:10'],
            'site_hours' => ['nullable', 'string', 'max:150'],
            'social_facebook' => ['nullable', 'string', 'max:255'],
            'social_instagram' => ['nullable', 'string', 'max:255'],
            'social_twitter' => ['nullable', 'string', 'max:255'],
            'social_tiktok' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
        ]);

        $this->settings->setMany($data, 'site');
        $this->handleImage($request, 'logo', 'site_logo', 'branding');
        $this->handleImage($request, 'favicon', 'site_favicon', 'branding');

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

        return back()->with('success', 'Admin panel settings saved.');
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
            'shop_enable_delivery', 'shop_enable_pickup', 'shop_enable_cash', 'shop_enable_card',
            'shop_reservations_enabled', 'shop_reviews_enabled', 'shop_reviews_auto_approve',
        ];

        foreach ($toggles as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        $this->settings->setMany($data, 'shop');

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

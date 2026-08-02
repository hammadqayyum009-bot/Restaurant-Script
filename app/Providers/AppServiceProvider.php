<?php

namespace App\Providers;

use App\Services\Settings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Maps a settings key to the config key it overrides. Anything the admin
     * has not set keeps the default from config/, so a fresh install works
     * before a single setting is saved.
     *
     * @var array<string, string>
     */
    protected array $configMap = [
        'site_name' => 'site.name',
        'site_tagline' => 'site.tagline',
        'site_phone' => 'site.phone',
        'site_whatsapp' => 'site.whatsapp',
        'site_email' => 'site.email',
        'site_address' => 'site.address',
        'site_currency' => 'site.currency',
        'site_hours' => 'site.opening_hours',
        'site_logo' => 'site.logo',
        'site_favicon' => 'site.favicon',
        'site_meta_description' => 'site.meta_description',
        'social_facebook' => 'site.social.facebook',
        'social_instagram' => 'site.social.instagram',
        'social_twitter' => 'site.social.twitter',
        'social_tiktok' => 'site.social.tiktok',

        'panel_name' => 'panel.name',
        'panel_short_name' => 'panel.short_name',
        'panel_logo' => 'panel.logo',
        'panel_favicon' => 'panel.favicon',
        'panel_footer_note' => 'panel.footer_note',

        'shop_delivery_fee' => 'shop.delivery_fee',
        'shop_min_order' => 'shop.min_order',
        'shop_tax_percent' => 'shop.tax_percent',
        'shop_reservation_open' => 'shop.reservation_open',
        'shop_reservation_close' => 'shop.reservation_close',
        'shop_reservation_max_guests' => 'shop.reservation_max_guests',

        'notify_admin_email' => 'notifications.admin_email',

        'mail_host' => 'mail.mailers.smtp.host',
        'mail_port' => 'mail.mailers.smtp.port',
        'mail_username' => 'mail.mailers.smtp.username',
        'mail_password' => 'mail.mailers.smtp.password',
        'mail_from_address' => 'mail.from.address',
        'mail_from_name' => 'mail.from.name',
    ];

    /**
     * Settings stored as checkboxes, mapped to the boolean config they drive.
     *
     * @var array<string, string>
     */
    protected array $boolMap = [
        'shop_enable_delivery' => 'shop.enable_delivery',
        'shop_enable_pickup' => 'shop.enable_pickup',
        'shop_enable_cash' => 'shop.enable_cash',
        'shop_enable_card' => 'shop.enable_card',
        'shop_reservations_enabled' => 'shop.reservations_enabled',
        'shop_reviews_enabled' => 'shop.reviews_enabled',
        'shop_reviews_auto_approve' => 'shop.reviews_auto_approve',

        'notify_on_register' => 'notifications.on_register',
        'notify_on_order' => 'notifications.on_order',
        'notify_on_order_status' => 'notifications.on_order_status',
        'notify_on_reservation' => 'notifications.on_reservation',
        'notify_copy_admin_on_order' => 'notifications.copy_admin_on_order',
        'notify_copy_admin_on_reservation' => 'notifications.copy_admin_on_reservation',
    ];

    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        $this->applySettings();

        // Admin forms read raw stored values (e.g. whether a password exists)
        // that config() cannot express.
        View::share('settings', $this->app->make(Settings::class));
    }

    /**
     * Push saved settings over the top of config so every existing
     * config('site.*') call in the views picks them up untouched.
     */
    protected function applySettings(): void
    {
        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        if (! $settings->available()) {
            return;
        }

        $stored = $settings->all();

        foreach ($this->configMap as $key => $configKey) {
            if (isset($stored[$key]) && $stored[$key] !== '') {
                config([$configKey => $stored[$key]]);
            }
        }

        foreach ($this->boolMap as $key => $configKey) {
            if (array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== '') {
                config([$configKey => $settings->bool($key)]);
            }
        }

        // SMTP credentials only matter once a host has actually been entered;
        // until then the mailer stays on whatever .env configured, so nothing
        // tries to connect and time out on a fresh install.
        if (! empty($stored['mail_host'])) {
            $encryption = $stored['mail_encryption'] ?? 'tls';

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
                'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : null,
            ]);
        }
    }
}

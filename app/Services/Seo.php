<?php

namespace App\Services;

use App\Models\MenuCategory;
use App\Models\Review;
use Illuminate\Support\Str;

/**
 * Everything search engines and link previews read.
 *
 * Kept in one place so the meta tags, the sitemap and the structured data all
 * describe the same restaurant rather than drifting apart.
 */
class Seo
{
    public function __construct(protected Settings $settings)
    {
    }

    public function siteName(): string
    {
        return (string) config('site.name');
    }

    public function defaultDescription(): string
    {
        return (string) (config('site.meta_description')
            ?: config('site.name').' — '.config('site.tagline').'. Order online for delivery or pickup.');
    }

    /** Absolute URL, because link previews reject relative image paths. */
    public function shareImage(): ?string
    {
        $image = $this->settings->get('seo_share_image')
            ?: $this->settings->get('home_hero_image')
            ?: config('site.logo');

        if (! $image) {
            return null;
        }

        return Str::startsWith($image, ['http://', 'https://']) ? $image : url($image);
    }

    public function indexable(): bool
    {
        // Off by default would be a trap; a live restaurant wants to be found.
        return $this->settings->bool('seo_indexable', true);
    }

    public function googleVerification(): ?string
    {
        return $this->settings->get('seo_google_verification');
    }

    /**
     * Opening hours in the format schema.org expects, e.g. "Mo-Su 11:00-24:00".
     * Falls back to the free-text setting when it cannot be parsed.
     */
    public function openingHours(): ?string
    {
        $raw = (string) config('site.opening_hours');

        if (preg_match('/(\d{1,2}:\d{2})\s*(AM|PM)?\s*[-–]\s*(\d{1,2}:\d{2})\s*(AM|PM)?/i', $raw, $m)) {
            $open = $this->to24h($m[1], $m[2] ?? null);
            $close = $this->to24h($m[3], $m[4] ?? null);

            return 'Mo-Su '.$open.'-'.$close;
        }

        return null;
    }

    /**
     * The Restaurant node Google reads for the knowledge panel and rich result.
     *
     * @return array<string, mixed>
     */
    public function restaurantSchema(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => $this->siteName(),
            'description' => $this->defaultDescription(),
            'url' => url('/'),
            'telephone' => config('site.phone'),
            'email' => config('site.email'),
            'servesCuisine' => $this->settings->get('seo_cuisine', 'Middle Eastern'),
            'priceRange' => $this->settings->get('seo_price_range', '$$'),
            'acceptsReservations' => config('shop.reservations_enabled') ? 'True' : 'False',
            'hasMenu' => route('menu.index'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('site.address'),
            ],
        ];

        if ($image = $this->shareImage()) {
            $schema['image'] = $image;
        }

        if ($hours = $this->openingHours()) {
            $schema['openingHours'] = $hours;
        }

        $social = array_values(array_filter([
            config('site.social.facebook'),
            config('site.social.instagram'),
            config('site.social.twitter'),
            config('site.social.tiktok'),
        ], fn ($url) => $url && $url !== '#'));

        if ($social) {
            $schema['sameAs'] = $social;
        }

        $reviews = Review::where('is_approved', true);
        $count = $reviews->count();

        if ($count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) $reviews->avg('rating'), 1),
                'reviewCount' => $count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $schema;
    }

    /**
     * The full menu as structured data, so Google can show dishes and prices.
     *
     * @return array<string, mixed>
     */
    public function menuSchema(): array
    {
        $sections = MenuCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->with('availableItems')
            ->get()
            ->filter(fn ($category) => $category->availableItems->isNotEmpty())
            ->map(fn ($category) => [
                '@type' => 'MenuSection',
                'name' => $category->name,
                'hasMenuItem' => $category->availableItems->map(fn ($item) => array_filter([
                    '@type' => 'MenuItem',
                    'name' => $item->name,
                    'description' => $item->description,
                    'image' => $item->image ? (Str::startsWith($item->image, ['http://', 'https://']) ? $item->image : url($item->image)) : null,
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => number_format((float) $item->price, 2, '.', ''),
                        'priceCurrency' => config('site.currency'),
                    ],
                ]))->values()->all(),
            ])->values()->all();

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            'name' => $this->siteName().' Menu',
            'url' => route('menu.index'),
            'hasMenuSection' => $sections,
        ];
    }

    protected function to24h(string $time, ?string $meridiem): string
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '00');
        $hour = (int) $hour;

        if ($meridiem) {
            $meridiem = strtoupper($meridiem);
            if ($meridiem === 'PM' && $hour < 12) {
                $hour += 12;
            } elseif ($meridiem === 'AM' && $hour === 12) {
                // Midnight closing reads better as 24:00 than 00:00.
                $hour = 24;
            }
        }

        return sprintf('%02d:%s', $hour, $minute);
    }
}

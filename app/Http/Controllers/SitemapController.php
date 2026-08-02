<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\Page;
use App\Services\Seo;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(protected Seo $seo)
    {
    }

    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('menu.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('about'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('contact'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('track.show'), 'priority' => '0.4', 'changefreq' => 'yearly'],
        ];

        if (config('shop.reservations_enabled')) {
            $urls[] = ['loc' => route('reservations.create'), 'priority' => '0.7', 'changefreq' => 'monthly'];
        }

        foreach (MenuCategory::where('is_active', true)->orderBy('sort_order')->get() as $category) {
            $urls[] = [
                'loc' => route('menu.index', ['category' => $category->slug]),
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        foreach (Page::orderBy('title')->get() as $page) {
            $urls[] = [
                'loc' => route('page.show', $page->slug),
                'priority' => '0.3',
                'changefreq' => 'yearly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = $this->seo->indexable()
            ? [
                'User-agent: *',
                'Disallow: /admin',
                'Disallow: /install',
                'Disallow: /checkout',
                'Disallow: /cart',
                'Disallow: /profile',
                '',
                'Sitemap: '.route('sitemap'),
            ]
            : [
                // The admin switched the site to private.
                'User-agent: *',
                'Disallow: /',
            ];

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain');
    }
}

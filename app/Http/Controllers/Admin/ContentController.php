<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\Settings;
use App\Services\Uploader;
use Illuminate\Http\Request;

/**
 * Everything the visitor reads on the header, home page and footer, editable
 * without touching a Blade file. Unset values fall back to the wording that
 * ships with the theme, so a fresh install still reads like a finished site.
 */
class ContentController extends Controller
{
    public function __construct(
        protected Settings $settings,
        protected Uploader $uploader,
        protected ActivityLogger $activity,
    )
    {
    }

    /* ---------------- Header ---------------- */

    public function header()
    {
        return view('admin.content.header', [
            'navLinks' => $this->links('header_nav', self::defaultNav()),
        ]);
    }

    public function saveHeader(Request $request)
    {
        $data = $request->validate([
            'header_cta_label' => ['nullable', 'string', 'max:40'],
            'header_cta_url' => ['nullable', 'string', 'max:255'],
            'header_brand_subtitle' => ['nullable', 'string', 'max:60'],
            'nav_label' => ['array'],
            'nav_label.*' => ['nullable', 'string', 'max:40'],
            'nav_url' => ['array'],
            'nav_url.*' => ['nullable', 'string', 'max:255'],
        ]);

        $this->settings->setMany([
            'header_cta_label' => $data['header_cta_label'] ?? null,
            'header_cta_url' => $data['header_cta_url'] ?? null,
            'header_brand_subtitle' => $data['header_brand_subtitle'] ?? null,
            'header_show_cta' => $request->boolean('header_show_cta') ? '1' : '0',
        ], 'header');

        $this->saveLinks('header_nav', $request->input('nav_label', []), $request->input('nav_url', []), 'header');

        $this->activity->log('settings', 'Edited the header content');

        return back()->with('success', 'Header saved.');
    }

    /* ---------------- Home page ---------------- */

    public function home()
    {
        return view('admin.content.home');
    }

    public function saveHome(Request $request)
    {
        $data = $request->validate([
            'home_hero_eyebrow' => ['nullable', 'string', 'max:80'],
            'home_hero_title' => ['nullable', 'string', 'max:200'],
            'home_hero_lead' => ['nullable', 'string', 'max:500'],
            'home_hero_primary_label' => ['nullable', 'string', 'max:40'],
            'home_hero_secondary_label' => ['nullable', 'string', 'max:40'],
            'home_stat1_value' => ['nullable', 'string', 'max:20'],
            'home_stat1_label' => ['nullable', 'string', 'max:40'],
            'home_stat2_value' => ['nullable', 'string', 'max:20'],
            'home_stat2_label' => ['nullable', 'string', 'max:40'],
            'home_stat3_value' => ['nullable', 'string', 'max:20'],
            'home_stat3_label' => ['nullable', 'string', 'max:40'],
            'home_strip_text' => ['nullable', 'string', 'max:400'],
            'home_featured_eyebrow' => ['nullable', 'string', 'max:60'],
            'home_featured_heading' => ['nullable', 'string', 'max:120'],
            'home_featured_intro' => ['nullable', 'string', 'max:400'],
            'home_story_eyebrow' => ['nullable', 'string', 'max:60'],
            'home_story_heading' => ['nullable', 'string', 'max:150'],
            'home_story_body' => ['nullable', 'string', 'max:1200'],
            'home_story_badge' => ['nullable', 'string', 'max:40'],
            'home_categories_heading' => ['nullable', 'string', 'max:120'],
            'home_categories_intro' => ['nullable', 'string', 'max:300'],
            'home_cta_eyebrow' => ['nullable', 'string', 'max:60'],
            'home_cta_heading' => ['nullable', 'string', 'max:150'],
            'home_cta_text' => ['nullable', 'string', 'max:400'],
            'home_cta_button' => ['nullable', 'string', 'max:40'],
            'hero_image' => ['nullable', 'image', 'max:4096'],
            'story_image' => ['nullable', 'image', 'max:4096'],
            'home_hero_image_url' => ['nullable', 'string', 'max:500'],
            'home_story_image_url' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (['home_feature1', 'home_feature2', 'home_feature3'] as $feature) {
            $data[$feature.'_icon'] = $request->input($feature.'_icon');
            $data[$feature.'_title'] = $request->input($feature.'_title');
            $data[$feature.'_desc'] = $request->input($feature.'_desc');
        }

        $heroUpload = $data['hero_image'] ?? null;
        $storyUpload = $data['story_image'] ?? null;
        unset($data['hero_image'], $data['story_image']);

        $this->settings->setMany($data, 'home');

        $this->saveImage($request, 'hero_image', 'home_hero_image', 'home_hero_image_url');
        $this->saveImage($request, 'story_image', 'home_story_image', 'home_story_image_url');

        unset($heroUpload, $storyUpload);

        $this->activity->log('settings', 'Edited the home page content');

        return back()->with('success', 'Home page content saved.');
    }

    /* ---------------- Footer ---------------- */

    public function footer()
    {
        return view('admin.content.footer', [
            'exploreLinks' => $this->links('footer_explore', self::defaultExplore()),
            'legalLinks' => $this->links('footer_legal', self::defaultLegal()),
        ]);
    }

    public function saveFooter(Request $request)
    {
        $data = $request->validate([
            'footer_about' => ['nullable', 'string', 'max:600'],
            'footer_copyright' => ['nullable', 'string', 'max:200'],
            'footer_explore_heading' => ['nullable', 'string', 'max:60'],
            'footer_legal_heading' => ['nullable', 'string', 'max:60'],
            'footer_contact_heading' => ['nullable', 'string', 'max:60'],
        ]);

        $this->settings->setMany($data, 'footer');
        $this->saveLinks('footer_explore', $request->input('explore_label', []), $request->input('explore_url', []), 'footer');
        $this->saveLinks('footer_legal', $request->input('legal_label', []), $request->input('legal_url', []), 'footer');

        $this->activity->log('settings', 'Edited the footer content');

        return back()->with('success', 'Footer saved.');
    }

    /* ---------------- helpers ---------------- */

    /**
     * @param  array<int, array{label: string, url: string}>  $default
     * @return array<int, array{label: string, url: string}>
     */
    protected function links(string $key, array $default): array
    {
        $raw = $this->settings->get($key);

        if (! $raw) {
            return $default;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * @param  array<int, string|null>  $labels
     * @param  array<int, string|null>  $urls
     */
    protected function saveLinks(string $key, array $labels, array $urls, string $group): void
    {
        $links = [];

        foreach ($labels as $i => $label) {
            $label = trim((string) $label);
            $url = trim((string) ($urls[$i] ?? ''));

            if ($label !== '' && $url !== '') {
                $links[] = ['label' => $label, 'url' => $url];
            }
        }

        $this->settings->set($key, json_encode(array_values($links)), $group);
    }

    /**
     * Accepts either an upload or a pasted URL for the same slot, preferring
     * the upload when both are present.
     */
    protected function saveImage(Request $request, string $field, string $key, string $urlField): void
    {
        if ($request->hasFile($field)) {
            $this->uploader->delete($this->settings->get($key));
            $this->settings->set($key, $this->uploader->store($request->file($field), 'content'), 'home');

            return;
        }

        $url = trim((string) $request->input($urlField));

        if ($url !== '' && $url !== $this->settings->get($key)) {
            $this->uploader->delete($this->settings->get($key));
            $this->settings->set($key, $url, 'home');
        }
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function defaultNav(): array
    {
        return [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Menu', 'url' => '/menu'],
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Contact', 'url' => '/contact'],
            ['label' => 'FAQ', 'url' => '/pages/faq'],
        ];
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function defaultExplore(): array
    {
        return [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Our Menu', 'url' => '/menu'],
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Contact', 'url' => '/contact'],
            ['label' => 'My Cart', 'url' => '/cart'],
            ['label' => 'Track Order', 'url' => '/track'],
        ];
    }

    /** @return array<int, array{label: string, url: string}> */
    public static function defaultLegal(): array
    {
        return [
            ['label' => 'Terms & Conditions', 'url' => '/pages/terms-and-conditions'],
            ['label' => 'Privacy Policy', 'url' => '/pages/privacy-policy'],
            ['label' => 'Cookies Policy', 'url' => '/pages/cookies-policy'],
            ['label' => 'Refund & Cancellation', 'url' => '/pages/refund-policy'],
            ['label' => 'FAQs', 'url' => '/pages/faq'],
        ];
    }
}

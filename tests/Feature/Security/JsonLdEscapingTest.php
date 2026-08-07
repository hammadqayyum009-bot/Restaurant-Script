<?php

namespace Tests\Feature\Security;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test for the JSON-LD script-tag breakout: both menu.blade.php
 * and partials/seo.blade.php (shared by every page, including the homepage)
 * used to json_encode() with JSON_UNESCAPED_SLASHES, which let a literal
 * "</script>" inside an admin-entered string close the JSON-LD <script>
 * block early and start executing whatever followed it, for every visitor.
 */
class JsonLdEscapingTest extends TestCase
{
    use RefreshDatabase;

    protected const PAYLOAD = '</script><script>alert(document.domain)</script>';

    public function test_a_malicious_dish_description_cannot_break_out_of_the_menu_pages_json_ld_block(): void
    {
        $category = MenuCategory::create(['name' => 'Mains', 'slug' => 'mains', 'sort_order' => 1, 'is_active' => true]);
        MenuItem::create([
            'menu_category_id' => $category->id,
            'name' => 'Mandi',
            'slug' => 'mandi',
            'description' => self::PAYLOAD,
            'price' => 25,
            'is_available' => true,
        ]);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $this->assertStringNotContainsString(
            '</script><script>alert(document.domain)</script>',
            $response->getContent(),
            'The raw payload must never appear unescaped in the response body — it would execute as a live <script> tag.',
        );
        $this->assertStringContainsString(
            "\\u003C\\/script\\u003E",
            $response->getContent(),
            'The description should still be present in the JSON-LD block, just safely hex/slash-escaped (JSON_HEX_TAG, no JSON_UNESCAPED_SLASHES) rather than dropped entirely.',
        );
    }

    public function test_a_malicious_site_name_cannot_break_out_of_the_shared_seo_partials_json_ld_block(): void
    {
        // restaurantSchema() (used by partials/seo.blade.php on every page,
        // including the homepage) reads site name straight from config —
        // set directly here rather than through the Settings-service layer,
        // whose per-request cache is populated during app boot and would not
        // reliably reflect a value set mid-test.
        config(['site.name' => 'Al Waha '.self::PAYLOAD]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertStringNotContainsString(
            '</script><script>alert(document.domain)</script>',
            $response->getContent(),
            'The raw payload must never appear unescaped in the response body — it would execute as a live <script> tag.',
        );
    }
}

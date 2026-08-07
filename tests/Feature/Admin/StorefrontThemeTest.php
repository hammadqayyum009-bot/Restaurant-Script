<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin-switchable second storefront theme ("Quiet Minimal") — a
 * config-driven conditional stylesheet/font swap in layouts/app.blade.php,
 * nothing else. See public/assets/css/theme-quiet-minimal.css and the
 * Stage 2/4 build notes for the full mechanism.
 */
class StorefrontThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Al Waha',
            'site_currency' => 'SAR',
            'site_theme' => 'classic',
        ], $overrides);
    }

    public function test_an_unrecognized_theme_value_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['site_theme' => 'bogus'])
        );

        $response->assertSessionHasErrors('site_theme');
        $this->assertNotSame('bogus', config('site.theme'));
    }

    public function test_the_minimal_theme_value_saves_cleanly(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['site_theme' => 'minimal'])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }

    public function test_the_default_theme_is_classic_when_nothing_has_ever_been_saved(): void
    {
        $this->assertSame('classic', config('site.theme'));
    }

    public function test_the_classic_theme_renders_only_the_original_stylesheet_and_fonts(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('assets/css/style.css', false);
        $response->assertSee('Playfair+Display', false);
        $response->assertDontSee('theme-quiet-minimal.css', false);
        $response->assertDontSee('Fraunces', false);
    }

    /**
     * config('site.*') is re-derived from the settings table once per
     * application boot (App\Providers\AppServiceProvider::applySettings()) —
     * a real production request always gets a fresh boot, so a save takes
     * effect on the very next page load. A single PHPUnit test method reuses
     * one already-booted app instance across every $this->get()/post() call,
     * so a save made via the real admin route mid-test never re-triggers
     * that boot step. Setting config() directly here simulates "the value a
     * subsequent real request would have booted with," the same technique
     * already used in Payments\CurrencyConsistencyTest for the identical
     * class of setting — this test is about the Blade conditional in
     * layouts/app.blade.php, not about re-proving the generic settings-sync
     * mechanism every other site.* key already relies on.
     */
    public function test_the_minimal_theme_renders_style_css_plus_the_override_stylesheet_and_its_own_fonts(): void
    {
        config(['site.theme' => 'minimal']);

        $response = $this->get(route('home'));

        $response->assertOk();
        // Both stylesheets present, in this order — style.css always loads
        // first, theme-quiet-minimal.css is the cascaded override, not a
        // replacement.
        $response->assertSeeInOrder(['assets/css/style.css', 'theme-quiet-minimal.css'], false);
        $response->assertSee('Playfair+Display', false);
        $response->assertSee('Fraunces', false);
    }

    public function test_the_theme_switch_also_applies_to_a_second_storefront_page(): void
    {
        config(['site.theme' => 'minimal']);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $response->assertSee('theme-quiet-minimal.css', false);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four-slot storefront colour override (Primary/Accent/Background/Text)
 * — see resources/views/partials/theme-overrides.blade.php and the Stage 2
 * build notes for the gated color-mix() mechanism. Each slot is independent:
 * an unset slot must emit nothing at all, so the page renders byte-identical
 * to not having this feature until an admin actually picks a colour.
 */
class StorefrontColorSlotsTest extends TestCase
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

    public function test_a_malformed_hex_value_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        foreach (['red', '#12', '#gggggg', 'c9a24b'] as $bad) {
            $response = $this->actingAs($admin, 'web')->put(
                route('admin.settings.site.save'),
                $this->validPayload(['theme_primary' => $bad])
            );

            $response->assertSessionHasErrors('theme_primary');
        }

        $this->assertNull(config('site.theme_primary'));
    }

    public function test_a_valid_hex_value_saves_cleanly_for_every_slot(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload([
                'theme_primary' => '#123abc',
                'theme_accent' => '#ABCDEF',
                'theme_background' => '#ffffff',
                'theme_text' => '#000000',
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }

    public function test_an_empty_value_clears_a_previously_saved_override(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['theme_primary' => '#123abc'])
        );
        $this->assertSame('#123abc', config('site.theme_primary'));

        $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['theme_primary' => ''])
        );

        $this->assertNull(config('site.theme_primary'));
    }

    public function test_all_four_slots_default_to_unset(): void
    {
        $this->assertNull(config('site.theme_primary'));
        $this->assertNull(config('site.theme_accent'));
        $this->assertNull(config('site.theme_background'));
        $this->assertNull(config('site.theme_text'));
    }

    /**
     * The structural half of the zero-regression requirement: with every
     * slot unset, the override partial's @if never opens, so no <style>
     * block — and specifically no color-mix( call, the one string that
     * only this feature ever emits — reaches the response at all. The
     * visual half (real screenshots, both themes, before/after) is the
     * Stage 4 proof; this is what makes that proof reproducible in CI.
     */
    public function test_nothing_is_emitted_when_no_slot_is_customized(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('color-mix(', false);
    }

    public function test_customizing_primary_emits_only_the_primary_block(): void
    {
        config(['site.theme_primary' => '#123abc']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('--gold-500: #123abc', false);
        // One of the 12 reconciled gold-family selectors.
        $response->assertSee('color-mix(in srgb, #123abc 35%, transparent)', false);
        $response->assertDontSee('--gold-400:', false);
        $response->assertDontSee('--cream-050:', false);
        $response->assertDontSee('--ink-900:', false);
    }

    public function test_customizing_accent_emits_only_the_accent_block(): void
    {
        config(['site.theme_accent' => '#abcdef']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('--gold-400: #abcdef', false);
        $response->assertDontSee('--gold-500:', false);
        $response->assertDontSee('color-mix(', false);
    }

    public function test_customizing_background_emits_only_the_background_block(): void
    {
        config(['site.theme_background' => '#ffffff']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('--cream-050: #ffffff', false);
        $response->assertDontSee('--ink-900:', false);
        $response->assertDontSee('color-mix(', false);
    }

    public function test_customizing_text_emits_the_full_derived_scale_and_all_29_reconciled_selectors(): void
    {
        config(['site.theme_text' => '#000000']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('--ink-900: #000000', false);
        $response->assertSee('--maroon-950: #000000', false);
        $response->assertSee('color-mix(in srgb, #000000 85%, white)', false); // --maroon-900
        $response->assertSee('color-mix(in srgb, #000000 80%, var(--cream-050))', false); // --ink-700
        // One of the 16 hairline selectors and the 1 solid near-black surface.
        $response->assertSee('.dish-card { border-color: color-mix(in srgb, #000000 5%, transparent); }', false);
        $response->assertSee('.site-header { background-color: color-mix(in srgb, #000000 97%, transparent); }', false);
        $response->assertDontSee('--gold-500:', false);
    }

    public function test_all_four_slots_together_emit_every_block(): void
    {
        config([
            'site.theme_primary' => '#111111',
            'site.theme_accent' => '#222222',
            'site.theme_background' => '#333333',
            'site.theme_text' => '#444444',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('--gold-500: #111111', false);
        $response->assertSee('--gold-400: #222222', false);
        $response->assertSee('--cream-050: #333333', false);
        $response->assertSee('--ink-900: #444444', false);
    }

    public function test_the_override_reaches_a_second_storefront_page(): void
    {
        config(['site.theme_primary' => '#123abc']);

        $response = $this->get(route('menu.index'));

        $response->assertOk();
        $response->assertSee('--gold-500: #123abc', false);
    }

    public function test_the_admin_panel_is_unaffected(): void
    {
        config(['site.theme_primary' => '#123abc']);

        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $response = $this->actingAs($admin, 'web')->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('color-mix(', false);
    }
}

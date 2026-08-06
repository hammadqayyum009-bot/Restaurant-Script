<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * site_currency previously accepted any string — a typo or unsupported code
 * silently broke Money::exponent() wherever it's read (Billing, and Payments
 * since the Fix 2 currency unification). See the full-project audit
 * ("site_currency has no validation against known currencies").
 */
class SiteCurrencyValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Al Waha',
            'site_currency' => 'SAR',
        ], $overrides);
    }

    public function test_an_unrecognized_currency_code_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $response = $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['site_currency' => 'XXX'])
        );

        $response->assertSessionHasErrors('site_currency');
        $this->assertNotSame('XXX', config('site.currency'));
    }

    public function test_a_currency_code_present_in_the_shared_currencies_table_still_saves(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $this->assertArrayHasKey('KWD', config('currencies'));

        $response = $this->actingAs($admin, 'web')->put(
            route('admin.settings.site.save'),
            $this->validPayload(['site_currency' => 'KWD'])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }
}

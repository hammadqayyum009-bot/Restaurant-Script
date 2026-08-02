<?php

namespace Tests\Feature\Billing;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BillingSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'vat_rate' => '15',
            'prices_include_vat' => '1',
            'default_currency' => 'SAR',
            'vat_number' => '300000000000003',
            'cr_number' => '1010101010',
            'seller_name_en' => 'Al Waha Restaurant Co.',
            'seller_name_ar' => 'شركة مطعم الواحة',
            'building_number' => '1234',
            'postal_code' => '12345',
            'additional_number' => '6789',
            'number_padding' => '6',
            'number_format' => '{PREFIX}-{YYYY}-{NUMBER}',
            'prefix_quotation' => 'QT',
            'prefix_proforma' => 'PF',
            'prefix_simplified_tax_invoice' => 'INV',
            'prefix_standard_tax_invoice' => 'TI',
            'prefix_credit_note' => 'CN',
            'prefix_delivery_note' => 'DN',
        ], $overrides);
    }

    public function test_a_vat_number_that_is_not_exactly_15_digits_blocks_the_save(): void
    {
        $response = $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['vat_number' => '12345']));

        $response->assertSessionHasErrors('vat_number');
        $this->assertDatabaseMissing('settings', ['key' => 'billing.vat_number', 'value' => '12345']);
    }

    public function test_a_malformed_cr_number_warns_but_saves(): void
    {
        $response = $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['cr_number' => 'ABC-not-a-number']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'billing.cr_number', 'value' => 'ABC-not-a-number']);

        $edit = $this->actingAs($this->admin())->get(route('admin.settings.billing'));
        $edit->assertOk();
    }

    public function test_a_logo_upload_with_a_disguised_php_payload_is_rejected(): void
    {
        // Illuminate\Http\Testing\File::getMimeType() derives its answer from
        // the given filename, not real content — a genuine UploadedFile is
        // needed here so Laravel's "mimetypes" rule actually sniffs the bytes
        // on disk (Symfony's FileinfoMimeTypeGuesser), the same path a real
        // malicious upload with a renamed extension would go through.
        $path = tempnam(sys_get_temp_dir(), 'billing-logo-test');
        file_put_contents($path, '<?php echo "not an image"; ?>');
        $file = new UploadedFile($path, 'logo.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['logo' => $file]));

        $response->assertSessionHasErrors('logo');

        @unlink($path);
    }

    public function test_an_uploaded_logo_filename_is_randomised_not_taken_from_the_original(): void
    {
        $file = UploadedFile::fake()->image('my-original-logo-name.png', 200, 100);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['logo' => $file]));

        $stored = Setting::query()->where('key', 'billing.logo')->value('value');

        $this->assertNotNull($stored);
        $this->assertStringNotContainsString('my-original-logo-name', $stored);
    }

    public function test_the_exclusive_mode_mismatch_warning_names_both_values(): void
    {
        config(['shop.tax_percent' => 5]);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['prices_include_vat' => '0', 'vat_rate' => '15']));

        $response = $this->actingAs($this->admin())->get(route('admin.settings.billing'));

        $response->assertOk();
        $response->assertSee('Shop: 5%');
        $response->assertSee('VAT rate (15%');
    }

    /**
     * D3 explicitly requires *reading* shop.tax_percent to warn about a
     * mismatch (see the previous test) — what this asserts is the stricter,
     * real invariant: saving billing settings never *writes* the storefront's
     * own tax setting, and billing.vat_rate is never silently overridden by
     * or derived from it.
     */
    public function test_saving_billing_settings_never_writes_the_storefront_tax_setting(): void
    {
        config(['shop.tax_percent' => 7]);

        $this->actingAs($this->admin())
            ->put(route('admin.settings.billing.save'), $this->validPayload(['vat_rate' => '15']));

        $this->assertDatabaseMissing('settings', ['key' => 'shop_tax_percent']);
        $this->assertSame(7.0, (float) config('shop.tax_percent'));
        $this->assertSame('15', Setting::query()->where('key', 'billing.vat_rate')->value('value'));
    }
}

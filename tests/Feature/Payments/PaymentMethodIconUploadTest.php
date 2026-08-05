<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PaymentMethodIconUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'is_active' => true]);
    }

    public function test_an_admin_can_upload_an_icon_for_a_payment_method(): void
    {
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);
        $file = UploadedFile::fake()->image('mastercard.png', 60, 40);

        $response = $this->actingAs($this->admin(), 'web')
            ->put(route('admin.settings.payment-methods.update', $method), [
                'icon' => $file,
            ]);

        $response->assertRedirect(route('admin.settings.payment-methods'));
        $fresh = $method->fresh();
        $this->assertNotNull($fresh->icon_path);
        $this->assertFileExists(public_path($fresh->icon_path));
    }

    public function test_an_admin_can_remove_an_existing_icon(): void
    {
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);
        $file = UploadedFile::fake()->image('visa.png', 60, 40);
        $this->actingAs($this->admin(), 'web')
            ->put(route('admin.settings.payment-methods.update', $method), ['icon' => $file]);

        $iconPath = $method->fresh()->icon_path;
        $this->assertNotNull($iconPath);

        $this->actingAs($this->admin(), 'web')
            ->put(route('admin.settings.payment-methods.update', $method), ['remove_icon' => '1']);

        $fresh = $method->fresh();
        $this->assertNull($fresh->icon_path);
        $this->assertFileDoesNotExist(public_path($iconPath));
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        $method = PaymentMethod::factory()->create(['driver' => 'cod']);
        $file = UploadedFile::fake()->create('not-an-icon.txt', 10, 'text/plain');

        $response = $this->actingAs($this->admin(), 'web')
            ->put(route('admin.settings.payment-methods.update', $method), ['icon' => $file]);

        $response->assertSessionHasErrors('icon');
        $this->assertNull($method->fresh()->icon_path);
    }
}

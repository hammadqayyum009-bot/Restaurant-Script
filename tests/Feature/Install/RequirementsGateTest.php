<?php

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression test: the requirements screen correctly disables its own
 * "Continue" button when a requirement fails, but databaseForm()/
 * databaseStore() never re-checked — navigating straight to /install/database
 * skipped the gate entirely regardless of real server state. See the
 * full-project audit ("install-requirements check is not server-enforced").
 *
 * isInstalled() is mocked rather than deleting the real storage/installed.lock
 * — EnsureInstalled (applied to almost every route in the app) reads that
 * exact file, so touching it for real would leak into every other test in
 * the same run, not just this class.
 */
class RequirementsGateTest extends TestCase
{
    use RefreshDatabase;

    protected function mockNotInstalledWith(array $requirements): void
    {
        $this->partialMock(InstallController::class, function ($mock) use ($requirements) {
            $mock->shouldAllowMockingProtectedMethods()
                ->shouldReceive('isInstalled')->andReturn(false)
                ->shouldReceive('checkRequirements')->andReturn($requirements);
        });
    }

    protected function passingRequirements(): array
    {
        return [
            'phpOk' => true,
            'extensions' => collect([['name' => 'openssl', 'loaded' => true]]),
            'permissions' => collect([['path' => 'storage', 'writable' => true]]),
            'allOk' => true,
        ];
    }

    protected function failingRequirements(): array
    {
        return [
            'phpOk' => true,
            'extensions' => collect([['name' => 'openssl', 'loaded' => true]]),
            'permissions' => collect([['path' => 'storage', 'writable' => false]]),
            'allOk' => false,
        ];
    }

    public function test_hitting_the_database_form_directly_with_a_failing_requirement_redirects_back(): void
    {
        $this->mockNotInstalledWith($this->failingRequirements());

        $response = $this->get(route('install.database'));

        $response->assertRedirect(route('install.requirements'));
        $response->assertSessionHas('error');
    }

    public function test_posting_to_database_store_directly_with_a_failing_requirement_redirects_back_without_installing(): void
    {
        $this->mockNotInstalledWith($this->failingRequirements());

        $response = $this->post(route('install.database.store'), [
            'db_connection' => 'sqlite',
            'site_name' => 'Test', 'site_phone' => '123', 'site_whatsapp' => '123',
            'site_email' => 'a@b.com', 'site_address' => 'x',
            'admin_name' => 'Admin', 'admin_email' => 'admin@example.com',
            'admin_password' => 'password123', 'admin_password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('install.requirements'));
        $response->assertSessionHas('error');
    }

    public function test_the_database_form_is_reachable_normally_when_requirements_pass(): void
    {
        $this->mockNotInstalledWith($this->passingRequirements());

        $response = $this->get(route('install.database'));

        $response->assertOk();
    }
}

<?php

namespace Tests\Feature\Payments;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaymentCredentialEncryptionTest extends TestCase
{
    use RefreshDatabase;

    protected const SECRET = 'sk_live_super_secret_key_do_not_leak_9f3a2b';

    public function test_credentials_are_stored_encrypted_not_as_plaintext(): void
    {
        $method = PaymentMethod::factory()->create([
            'driver' => 'cod',
            'credentials' => ['api_key' => self::SECRET],
        ]);

        $rawColumn = DB::table('payment_methods')->where('id', $method->id)->value('credentials');

        $this->assertStringNotContainsString(self::SECRET, (string) $rawColumn, 'The raw DB column must never contain the plaintext secret.');
        $this->assertSame(self::SECRET, $method->fresh()->credentials['api_key'], 'The model must still decrypt it correctly.');
    }

    public function test_credentials_never_appear_when_the_model_is_serialized(): void
    {
        $method = PaymentMethod::factory()->create([
            'driver' => 'cod',
            'credentials' => ['api_key' => self::SECRET],
        ]);

        $array = $method->fresh()->toArray();
        $json = $method->fresh()->toJson();

        $this->assertArrayNotHasKey('credentials', $array);
        $this->assertStringNotContainsString(self::SECRET, $json);
    }

    /**
     * The realistic leak path for a secret is a future call like
     * Log::info('...', $method->toArray()) or an exception handler dumping
     * request/model context. Since credentials is both encrypted at rest and
     * hidden from serialization, neither can put the plaintext secret into a
     * log line — this proves that guarantee holds through the actual Log
     * facade, not just in isolation.
     */
    public function test_a_secret_never_appears_in_application_log_output(): void
    {
        $method = PaymentMethod::factory()->create([
            'driver' => 'cod',
            'credentials' => ['api_key' => self::SECRET],
        ]);

        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        Log::info('Payment method saved', ['method' => $method->fresh()->toArray()]);

        foreach ($logged as $line) {
            $this->assertStringNotContainsString(self::SECRET, $line);
        }
        $this->assertNotEmpty($logged);
    }
}

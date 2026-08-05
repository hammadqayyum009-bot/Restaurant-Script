<?php

namespace Tests\Feature\Payments\Moyasar;

use App\Payments\Contracts\PaymentDriver;
use App\Payments\Drivers\CashOnDeliveryDriver;
use App\Payments\Drivers\MoyasarDriver;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Phase 2's Stage 2 contract promised "CONTRACT CHANGES: none". This proves
 * it by reflection rather than by having looked at the diff and believed it —
 * if a future change alters PaymentDriver's shape, this fails immediately.
 */
class DriverContractUnchangedTest extends TestCase
{
    /** @var array<string, array{params: list<string>, return: string}> */
    protected const EXPECTED_METHODS = [
        'identifier' => ['params' => [], 'return' => 'string'],
        'displayInfo' => ['params' => [], 'return' => 'App\Payments\ValueObjects\DriverDisplayInfo'],
        'supportedCurrencies' => ['params' => [], 'return' => 'array'],
        'isConfigured' => ['params' => ['App\Models\PaymentMethod'], 'return' => 'bool'],
        'initiate' => ['params' => ['App\Models\PaymentTransaction', 'App\Models\PaymentMethod'], 'return' => 'App\Payments\ValueObjects\PaymentInitiationResult'],
        'handleCallback' => ['params' => ['App\Models\PaymentMethod', 'Illuminate\Http\Request'], 'return' => 'App\Payments\ValueObjects\PaymentCallbackResult'],
        'handleWebhook' => ['params' => ['App\Models\PaymentMethod', 'Illuminate\Http\Request'], 'return' => 'App\Payments\ValueObjects\PaymentWebhookResult'],
        'verify' => ['params' => ['App\Models\PaymentTransaction', 'App\Models\PaymentMethod'], 'return' => 'App\Payments\ValueObjects\PaymentVerificationResult'],
        'supportsRefund' => ['params' => [], 'return' => 'bool'],
        'refund' => ['params' => ['App\Models\PaymentTransaction', 'App\Models\PaymentMethod', 'int', 'string'], 'return' => 'App\Payments\ValueObjects\RefundResult'],
    ];

    public function test_the_payment_driver_interface_has_exactly_the_phase_1_methods(): void
    {
        $reflection = new ReflectionClass(PaymentDriver::class);
        $actualMethods = array_map(fn ($m) => $m->getName(), $reflection->getMethods());

        sort($actualMethods);
        $expectedNames = array_keys(self::EXPECTED_METHODS);
        sort($expectedNames);

        $this->assertSame($expectedNames, $actualMethods, 'PaymentDriver gained or lost a method — the Phase 1 contract changed.');
    }

    /**
     * @dataProvider methodProvider
     */
    public function test_each_method_signature_is_unchanged(string $method): void
    {
        $reflection = new ReflectionClass(PaymentDriver::class);
        $reflectionMethod = $reflection->getMethod($method);

        $actualParams = array_map(
            fn ($p) => ltrim((string) $p->getType(), '?'),
            $reflectionMethod->getParameters(),
        );
        $actualReturn = ltrim((string) $reflectionMethod->getReturnType(), '?');

        $this->assertSame(self::EXPECTED_METHODS[$method]['params'], $actualParams, "Parameters of {$method}() changed.");
        $this->assertSame(self::EXPECTED_METHODS[$method]['return'], $actualReturn, "Return type of {$method}() changed.");
    }

    public static function methodProvider(): array
    {
        return array_map(fn ($name) => [$name], array_keys(self::EXPECTED_METHODS));
    }

    public function test_both_drivers_still_implement_the_interface(): void
    {
        $this->assertInstanceOf(PaymentDriver::class, new CashOnDeliveryDriver);
        $this->assertInstanceOf(PaymentDriver::class, new MoyasarDriver);
    }
}

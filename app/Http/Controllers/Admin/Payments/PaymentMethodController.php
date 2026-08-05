<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\ReorderPaymentMethodsRequest;
use App\Http\Requests\Payments\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use App\Payments\Contracts\SupportsConnectionTest;
use App\Payments\Money;
use App\Payments\PaymentDriverRegistry;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Gate;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentDriverRegistry $registry,
        protected ActivityLogger $activity,
    ) {}

    public function index()
    {
        Gate::authorize('manage-payments');

        $rows = PaymentMethod::orderBy('sort_order')->get()->map(function (PaymentMethod $method) {
            $driver = $this->registry->has($method->driver) ? $this->registry->get($method->driver) : null;

            return [
                'method' => $method,
                'driver' => $driver,
                'configured' => (bool) $driver?->isConfigured($method),
            ];
        });

        return view('admin.settings.payment-methods.index', ['rows' => $rows]);
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        Gate::authorize('view', $paymentMethod);

        $driver = $this->registry->has($paymentMethod->driver) ? $this->registry->get($paymentMethod->driver) : null;

        return view('admin.settings.payment-methods.edit', [
            'method' => $paymentMethod,
            'driver' => $driver,
            'currency' => config('payments.currency'),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validated();
        $currency = config('payments.currency');

        $updates = [
            'test_mode' => $request->boolean('test_mode'),
            'label_en' => $data['label_en'] ?? null,
            'label_ar' => $data['label_ar'] ?? null,
            'description_en' => $data['description_en'] ?? null,
            'description_ar' => $data['description_ar'] ?? null,
            'min_order_amount_minor' => isset($data['min_order_amount'])
                ? Money::toMinor((string) $data['min_order_amount'], $currency) : null,
            'max_order_amount_minor' => isset($data['max_order_amount'])
                ? Money::toMinor((string) $data['max_order_amount'], $currency) : null,
            'allowed_order_types' => $data['allowed_order_types'] ?? null,
            'credentials' => $this->mergedCredentials($paymentMethod, $data),
        ];

        $wantsEnabled = $request->boolean('enabled');

        if ($wantsEnabled && ! $this->canBeEnabledWith($paymentMethod, $updates)) {
            return back()
                ->withErrors(['enabled' => 'This payment method cannot be enabled until it is fully configured.'])
                ->withInput();
        }

        $updates['enabled'] = $wantsEnabled;

        $paymentMethod->update($updates);
        $this->activity->updated($paymentMethod, 'payment method "'.$paymentMethod->driver.'"');

        return redirect()->route('admin.settings.payment-methods')->with('success', 'Payment method saved.');
    }

    public function toggle(PaymentMethod $paymentMethod)
    {
        Gate::authorize('update', $paymentMethod);

        $enabling = ! $paymentMethod->enabled;

        if ($enabling && ! $this->canBeEnabledWith($paymentMethod, [])) {
            return back()->with('error', 'This payment method cannot be enabled until it is fully configured.');
        }

        $paymentMethod->update(['enabled' => $enabling]);
        $this->activity->log(
            'updated',
            ($enabling ? 'Enabled' : 'Disabled').' payment method "'.$paymentMethod->driver.'"',
            $paymentMethod,
        );

        return back()->with('success', 'Payment method updated.');
    }

    public function reorder(ReorderPaymentMethodsRequest $request)
    {
        foreach ($request->validated('order') as $index => $id) {
            PaymentMethod::whereKey($id)->update(['sort_order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function testConnection(PaymentMethod $paymentMethod)
    {
        Gate::authorize('update', $paymentMethod);

        $driver = $this->registry->has($paymentMethod->driver) ? $this->registry->get($paymentMethod->driver) : null;

        if (! $driver instanceof SupportsConnectionTest) {
            return back()->with('error', 'This payment method does not support a connection test.');
        }

        $ok = $driver->testConnection($paymentMethod);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? __('payments.test_connection_success') : __('payments.test_connection_failed'),
        );
    }

    /**
     * Secret fields left blank keep their existing stored value —
     * credentials are write-only, never rendered back into an input, so a
     * blank field must never be read as "clear this." Plain (non-secret)
     * config fields like country/local_source_id are the opposite: they
     * are visible in the form, so a submitted value always overwrites,
     * including clearing it out with a blank submission.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mergedCredentials(PaymentMethod $paymentMethod, array $data): array
    {
        $credentials = $paymentMethod->credentials ?? [];

        foreach (['secret_key_test', 'secret_key_live', 'webhook_secret_test', 'webhook_secret_live'] as $field) {
            if (! empty($data[$field])) {
                $credentials[$field] = $data[$field];
            }
        }

        foreach (['country', 'local_source_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $credentials[$field] = $data[$field] !== '' && $data[$field] !== null ? $data[$field] : null;
            }
        }

        return $credentials;
    }

    /**
     * Checks isConfigured() against the values about to be saved, not the
     * stale row, so a credential entered in this same submit is honoured
     * immediately rather than requiring a second save.
     *
     * @param  array<string, mixed>  $pendingUpdates
     */
    protected function canBeEnabledWith(PaymentMethod $paymentMethod, array $pendingUpdates): bool
    {
        if (! $this->registry->has($paymentMethod->driver)) {
            return false;
        }

        $preview = $paymentMethod->replicate();
        $preview->forceFill($pendingUpdates);

        return $this->registry->get($paymentMethod->driver)->isConfigured($preview);
    }
}

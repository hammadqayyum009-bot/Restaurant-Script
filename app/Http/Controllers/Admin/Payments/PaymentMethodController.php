<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\ReorderPaymentMethodsRequest;
use App\Http\Requests\Payments\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
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

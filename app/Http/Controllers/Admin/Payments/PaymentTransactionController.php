<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\MarkPaymentPaidRequest;
use App\Models\PaymentTransaction;
use App\Payments\PaymentStatus;
use App\Payments\PaymentTransactionStatusService;

class PaymentTransactionController extends Controller
{
    public function __construct(protected PaymentTransactionStatusService $statusService) {}

    public function markPaid(MarkPaymentPaidRequest $request, PaymentTransaction $paymentTransaction)
    {
        $result = $this->statusService->transitionTo(
            $paymentTransaction,
            PaymentStatus::Paid,
            PaymentTransactionStatusService::SOURCE_MANUAL,
            $request->user(),
            $request->validated('note'),
        );

        if (! $result->applied) {
            return back()->with('error', __('payments.already_paid'));
        }

        return back()->with('success', __('payments.marked_paid_success'));
    }
}

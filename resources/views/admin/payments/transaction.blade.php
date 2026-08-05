@extends('admin.layouts.app')

@section('title', __('payments.transaction_detail_title').' #'.$transaction->id)

@section('actions')
    <a href="{{ route('admin.orders.show', $transaction->order) }}" class="a-btn ghost sm">Back to order</a>
@endsection

@section('content')
    @php
        $tone = match ($transaction->status) {
            'paid' => 'ok',
            'failed', 'cancelled' => 'danger',
            'pending', 'processing' => 'warn',
            default => 'info',
        };

        $creditableDocument = \App\Models\BillingDocument::where('order_id', $transaction->order_id)
            ->where('document_type', '!=', 'credit_note')
            ->whereIn('status', [
                \App\Models\BillingDocument::STATUS_ISSUED,
                \App\Models\BillingDocument::STATUS_PARTIALLY_CREDITED,
            ])
            ->first();
    @endphp

    <div class="a-grid side">
        <div>
            <div class="a-card">
                <div class="a-card-head">
                    <h2>Transaction #{{ $transaction->id }}</h2>
                    <span class="a-badge {{ $tone }}">{{ __('payments.status_'.$transaction->status) }}</span>
                </div>

                <div class="a-stack" style="font-size:0.88rem;">
                    <div><span class="a-muted">Order</span><br><a href="{{ route('admin.orders.show', $transaction->order) }}">{{ $transaction->order->order_number }}</a></div>
                    <div><span class="a-muted">Driver</span><br>{{ $transaction->driver }}</div>
                    <div><span class="a-muted">Amount</span><br>{{ $transaction->currency }} {{ $currencyDecimal }}</div>
                    @if ($transaction->refunded_amount_minor > 0)
                        <div><span class="a-muted">Refunded</span><br>{{ $transaction->currency }} {{ $refundedDecimal }} (remaining: {{ $remainingDecimal }})</div>
                    @endif
                    <div><span class="a-muted">Provider reference</span><br><span class="a-mono">{{ $transaction->provider_reference ?: '—' }}</span></div>
                    <div><span class="a-muted">Last known provider status</span><br>{{ $transaction->last_provider_status ?: '—' }}</div>
                    @if ($transaction->failure_reason)
                        <div><span class="a-muted">Failure reason</span><br><span style="color:var(--a-danger, #b3261e);">{{ $transaction->failure_reason }}</span></div>
                    @endif
                    <div><span class="a-muted">Created</span><br>{{ $transaction->created_at->format('d M Y, H:i') }}</div>
                    @if ($transaction->paid_at)
                        <div><span class="a-muted">Paid at</span><br>{{ $transaction->paid_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if ($transaction->last_verified_at)
                        <div><span class="a-muted">Last verified</span><br>{{ $transaction->last_verified_at->format('d M Y, H:i') }}</div>
                    @endif
                    @if ($transaction->refunded_at)
                        <div><span class="a-muted">Refunded at</span><br>{{ $transaction->refunded_at->format('d M Y, H:i') }} — {{ $transaction->refund_reason }}</div>
                    @endif
                </div>
            </div>

            <div class="a-card">
                <div class="a-card-head"><h3 style="margin:0;">{{ __('payments.gateway_log_title') }}</h3></div>
                @if ($transaction->gatewayLogs->isEmpty())
                    <p class="a-card-sub" style="margin-top:0;">No outbound provider requests recorded yet.</p>
                @else
                    <div class="a-table-wrap">
                        <table class="a-table">
                            <thead>
                                <tr><th>When</th><th>Method</th><th>Endpoint</th><th class="num">Status</th><th class="num">ms</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($transaction->gatewayLogs->sortByDesc('id') as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('d M H:i:s') }}</td>
                                        <td>{{ $log->http_method }}</td>
                                        <td class="a-mono">{{ $log->endpoint }}</td>
                                        <td class="num">{{ $log->http_status ?? '—' }}</td>
                                        <td class="num">{{ $log->duration_ms ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="a-card">
                <div class="a-card-head"><h3 style="margin:0;">{{ __('payments.webhook_events_title') }}</h3></div>
                @if ($transaction->webhookEvents->isEmpty())
                    <p class="a-card-sub" style="margin-top:0;">No webhook deliveries recorded for this transaction yet.</p>
                @else
                    <div class="a-table-wrap">
                        <table class="a-table">
                            <thead>
                                <tr><th>When</th><th>Signature</th><th>Processed</th><th>Note</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($transaction->webhookEvents->sortByDesc('id') as $event)
                                    <tr>
                                        <td>{{ $event->created_at->format('d M H:i:s') }}</td>
                                        <td><span class="a-badge {{ $event->signature_valid ? 'ok' : 'danger' }}">{{ $event->signature_valid ? 'valid' : 'invalid' }}</span></td>
                                        <td>{{ $event->processed ? 'Yes' : 'No' }}</td>
                                        <td>{{ $event->error_message }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div>
            @can('reverify', $transaction)
                <div class="a-card">
                    <h3>Verification</h3>
                    <p class="a-card-sub" style="margin-top:0;">Asks the provider directly for this payment's current status.</p>
                    <form method="POST" action="{{ route('admin.payment-transactions.reverify', $transaction) }}">
                        @csrf @method('PUT')
                        <button type="submit" class="a-btn block">{{ __('payments.reverify') }}</button>
                    </form>
                </div>
            @endcan

            @can('markPaid', $transaction)
                <div class="a-card">
                    <h3>Mark as paid</h3>
                    <form method="POST" action="{{ route('admin.payment-transactions.mark-paid', $transaction) }}"
                          data-confirm="{{ __('payments.mark_paid_confirm') }}">
                        @csrf @method('PUT')
                        <button type="submit" class="a-btn block">{{ __('payments.mark_paid') }}</button>
                    </form>
                </div>
            @endcan

            @can('refund', $transaction)
                <div class="a-card">
                    <h3>{{ __('payments.refund') }}</h3>
                    <p class="a-card-sub" style="margin-top:0;">Remaining refundable: {{ $transaction->currency }} {{ $remainingDecimal }}.</p>
                    <form method="POST" action="{{ route('admin.payment-transactions.refund', $transaction) }}">
                        @csrf
                        <div class="a-field">
                            <label for="amount">{{ __('payments.refund_amount') }}</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="a-input" value="{{ old('amount') }}" required>
                        </div>
                        <div class="a-field">
                            <label for="reason">{{ __('payments.refund_reason') }}</label>
                            <textarea id="reason" name="reason" class="a-textarea" minlength="10" required>{{ old('reason') }}</textarea>
                        </div>
                        <button type="submit" class="a-btn block" data-confirm="Confirm this refund? It calls the payment provider immediately.">{{ __('payments.refund') }}</button>
                    </form>
                </div>
            @endcan

            @if ($transaction->refunded_amount_minor > 0)
                <div class="a-card">
                    <h3>{{ __('payments.issue_credit_note') }}</h3>
                    <p class="a-card-sub" style="margin-top:0;">
                        {{ str_replace(':amount', $transaction->currency.' '.$refundedDecimal, __('payments.issue_credit_note_hint')) }}
                    </p>
                    @if ($creditableDocument)
                        <a href="{{ route('admin.billing.credit.create', $creditableDocument) }}" class="a-btn block">{{ __('payments.issue_credit_note') }}</a>
                    @else
                        <a href="{{ route('admin.billing.from-order.create', $transaction->order) }}" class="a-btn ghost block">Issue a tax invoice first</a>
                    @endif
                </div>
            @endif

            <div class="a-card">
                <h3>Status history</h3>
                <div class="a-stack" style="font-size:0.82rem;">
                    @foreach ($transaction->statusLogs->sortByDesc('id') as $log)
                        <div>
                            <strong>{{ $log->old_status ?? '—' }} → {{ $log->new_status }}</strong>
                            <span class="a-muted">({{ $log->source }})</span><br>
                            <span class="a-muted">{{ $log->created_at->format('d M Y, H:i') }}</span>
                            @if ($log->note)
                                <br>{{ $log->note }}
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

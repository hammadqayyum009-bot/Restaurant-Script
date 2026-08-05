@extends('admin.layouts.app')

@section('title', __('payments.stuck_transactions_title'))

@section('actions')
    <form method="POST" action="{{ route('admin.payment-transactions.reconcile') }}">
        @csrf
        <button type="submit" class="a-btn sm">{{ __('payments.run_reconciliation') }}</button>
    </form>
@endsection

@section('content')
    <div class="a-card">
        <div class="a-card-head"><h2>{{ __('payments.stuck_transactions_title') }}</h2></div>
        <p class="a-card-sub" style="margin-top:0;">
            {{ __('payments.stuck_transactions_intro') }}
            (&gt; {{ $stuckAfterMinutes }} minutes)
        </p>

        @if ($transactions->isEmpty())
            <div class="a-empty"><strong>{{ __('payments.no_stuck_transactions') }}</strong></div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="num">Amount</th>
                            <th>Waiting since</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $transaction->order) }}" class="a-mono">{{ $transaction->order->order_number }}</a>
                                </td>
                                <td>{{ $transaction->paymentMethod->label_en ?: $transaction->driver }}</td>
                                <td><span class="a-badge warn">{{ __('payments.status_'.$transaction->status) }}</span></td>
                                <td class="num">{{ $transaction->currency }} {{ \App\Payments\Money::toDecimal($transaction->amount_minor, $transaction->currency) }}</td>
                                <td>{{ $transaction->created_at->diffForHumans() }}</td>
                                <td>
                                    <a href="{{ route('admin.payment-transactions.show', $transaction) }}" class="a-btn ghost sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $transactions->links() }}</div>
        @endif
    </div>
@endsection

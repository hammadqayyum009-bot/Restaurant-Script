@extends('admin.layouts.app')

@section('title', 'Sales reports')

@section('actions')
    <a href="{{ route('admin.export.orders', request()->only('range', 'from', 'to')) }}" class="a-btn sm">Export CSV</a>
@endsection

@section('content')
    @php
        $currency = config('site.currency');
        $peak = collect($series)->max('revenue') ?: 0;
        $peakDay = collect($series)->firstWhere('revenue', $peak);
    @endphp

    <div class="a-card">
        <form method="GET" class="a-filters" style="margin-bottom:0;">
            @foreach ($ranges as $key => $label)
                @if ($key !== 'custom')
                    <a href="{{ route('admin.reports.index', ['range' => $key]) }}"
                       class="a-chip {{ $range === $key ? 'active' : '' }}">{{ $label }}</a>
                @endif
            @endforeach

            <input type="hidden" name="range" value="custom">
            <input type="date" name="from" class="a-input" value="{{ $from->toDateString() }}" aria-label="From date">
            <input type="date" name="to" class="a-input" value="{{ $to->toDateString() }}" aria-label="To date">
            <button type="submit" class="a-btn ghost sm">Apply</button>
        </form>
    </div>

    <div class="a-grid cols-4" style="margin-bottom:18px;">
        <div class="a-stat">
            <span class="label">Revenue</span>
            <span class="value">{{ $currency }} {{ number_format($revenue, 2) }}</span>
            <span class="meta">{{ $from->format('d M') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="a-stat">
            <span class="label">Orders</span>
            <span class="value">{{ number_format($orderCount) }}</span>
            <span class="meta">{{ $cancelled }} cancelled &middot; {{ $reservations }} bookings</span>
        </div>
        <div class="a-stat">
            <span class="label">Average order</span>
            <span class="value">{{ $currency }} {{ number_format($averageOrder, 2) }}</span>
            <span class="meta">per completed order</span>
        </div>
        <div class="a-stat">
            <span class="label">Fees &amp; tax</span>
            <span class="value">{{ $currency }} {{ number_format($deliveryFees + $taxCollected, 2) }}</span>
            <span class="meta">{{ $currency }} {{ number_format($deliveryFees, 2) }} delivery &middot; {{ $currency }} {{ number_format($taxCollected, 2) }} tax</span>
        </div>
    </div>

    <div class="a-card">
        <div class="a-card-head">
            <h2>Revenue per day</h2>
            <div class="a-actions">
                <button type="button" class="a-btn ghost sm" data-toggle-table="revenue-table">Show table</button>
            </div>
        </div>

        @if ($peak <= 0)
            <div class="a-empty"><strong>No sales in this range</strong>Pick a different range, or wait for the first order.</div>
        @else
            <div class="chart" role="img"
                 aria-label="Daily revenue from {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}, peak {{ $currency }} {{ number_format($peak, 2) }}">
                <div class="chart-grid" aria-hidden="true">
                    @foreach ([1, 0.75, 0.5, 0.25, 0] as $step)
                        <div class="chart-gridline">
                            <span>{{ $currency }} {{ number_format($peak * $step, 0) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="chart-bars">
                    @foreach ($series as $point)
                        @php $pct = $peak > 0 ? max(1.5, $point['revenue'] / $peak * 100) : 0; @endphp
                        <div class="chart-bar-slot"
                             data-label="{{ $point['label'] }}"
                             data-value="{{ $currency }} {{ number_format($point['revenue'], 2) }}"
                             data-orders="{{ $point['orders'] }} order{{ $point['orders'] === 1 ? '' : 's' }}"
                             tabindex="0">
                            <div class="chart-bar {{ $point['revenue'] >= $peak && $peak > 0 ? 'is-peak' : '' }}"
                                 style="height: {{ $point['revenue'] > 0 ? $pct : 0 }}%"></div>
                        </div>
                    @endforeach
                </div>

                <div class="chart-axis" aria-hidden="true">
                    <span>{{ $series[0]['label'] ?? '' }}</span>
                    @if ($peakDay)
                        <span class="chart-axis-peak">Peak {{ $peakDay['label'] }} &middot; {{ $currency }} {{ number_format($peak, 2) }}</span>
                    @endif
                    <span>{{ $series[count($series) - 1]['label'] ?? '' }}</span>
                </div>

                <div class="chart-tip" id="chart-tip" hidden></div>
            </div>

            <div id="revenue-table" class="a-table-wrap" hidden style="margin-top:18px;">
                <table class="a-table">
                    <thead>
                        <tr><th>Day</th><th class="num">Orders</th><th class="num">Revenue</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($series as $point)
                            <tr>
                                <td>{{ $point['label'] }}</td>
                                <td class="num">{{ $point['orders'] }}</td>
                                <td class="num">{{ $currency }} {{ number_format($point['revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="a-grid cols-2">
        <div class="a-card">
            <div class="a-card-head"><h2>Orders by status</h2></div>
            @include('admin.reports.partials.breakdown', [
                'rows' => $byStatus, 'key' => 'status', 'total' => $orderCount + $cancelled,
            ])
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Top dishes</h2></div>
            @if ($topDishes->isEmpty())
                <div class="a-empty">Nothing sold in this range.</div>
            @else
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead>
                            <tr><th>Dish</th><th class="num">Sold</th><th class="num">Revenue</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($topDishes as $dish)
                                <tr>
                                    <td>{{ $dish->name }}</td>
                                    <td class="num">{{ $dish->qty }}</td>
                                    <td class="num">{{ $currency }} {{ number_format((float) $dish->revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Delivery vs pickup</h2></div>
            @include('admin.reports.partials.breakdown', [
                'rows' => $byType, 'key' => 'order_type', 'total' => $orderCount,
            ])
        </div>

        <div class="a-card">
            <div class="a-card-head"><h2>Payment method</h2></div>
            @include('admin.reports.partials.breakdown', [
                'rows' => $byPayment, 'key' => 'payment_method', 'total' => $orderCount,
            ])
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var tip = document.getElementById('chart-tip');

        function showTip(slot) {
            if (!tip) return;
            tip.innerHTML =
                '<strong>' + slot.dataset.label + '</strong>' +
                '<span>' + slot.dataset.value + '</span>' +
                '<span class="muted">' + slot.dataset.orders + '</span>';
            tip.hidden = false;

            var chart = slot.closest('.chart').getBoundingClientRect();
            var bar = slot.getBoundingClientRect();
            var left = bar.left - chart.left + bar.width / 2;
            tip.style.left = Math.min(Math.max(left, 60), chart.width - 60) + 'px';
        }

        document.querySelectorAll('.chart-bar-slot').forEach(function (slot) {
            slot.addEventListener('mouseenter', function () { showTip(slot); });
            slot.addEventListener('focus', function () { showTip(slot); });
            slot.addEventListener('mouseleave', function () { if (tip) tip.hidden = true; });
            slot.addEventListener('blur', function () { if (tip) tip.hidden = true; });
        });

        document.querySelectorAll('[data-toggle-table]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.getElementById(btn.getAttribute('data-toggle-table'));
                if (!target) return;
                target.hidden = !target.hidden;
                btn.textContent = target.hidden ? 'Show table' : 'Hide table';
            });
        });
    })();
</script>
@endpush

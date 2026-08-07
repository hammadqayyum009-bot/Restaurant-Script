@php
    $currency = config('site.currency');
    $isReceipt = ($format ?? 'invoice') === 'receipt';
    $paymentDrivers = app(\App\Payments\PaymentDriverRegistry::class);
    $paymentMethodLabel = $paymentDrivers->has($order->payment_method)
        ? $paymentDrivers->get($order->payment_method)->displayInfo()->label
        : ucfirst($order->payment_method);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $isReceipt ? 'Receipt' : 'Invoice' }} {{ $order->order_number }} &mdash; {{ config('site.name') }}</title>
    <style>
        :root {
            --ink: #201512;
            --soft: #6b5a52;
            --line: #ddd4c9;
            --accent: #6f1b28;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 24px;
            background: #f4f1ec;
            color: var(--ink);
            font-family: ui-sans-serif, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 14px;
            line-height: 1.55;
        }

        .sheet {
            background: #fff;
            margin: 0 auto;
            padding: 32px 34px;
            box-shadow: 0 10px 34px rgba(32, 21, 18, 0.12);
            max-width: {{ $isReceipt ? '320px' : '760px' }};
            {{ $isReceipt ? 'font-size:12.5px; padding:22px 20px;' : '' }}
        }

        .toolbar {
            max-width: {{ $isReceipt ? '320px' : '760px' }};
            margin: 0 auto 14px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .toolbar a, .toolbar button {
            font: inherit;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            cursor: pointer;
            text-decoration: none;
        }
        .toolbar button { background: var(--accent); border-color: var(--accent); color: #fff; }

        .head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--accent);
            {{ $isReceipt ? 'flex-direction:column; align-items:center; text-align:center; border-bottom-style:dashed; border-bottom-width:1px;' : '' }}
        }
        .brand-name { font-size: {{ $isReceipt ? '17px' : '21px' }}; font-weight: 700; color: var(--accent); }
        .brand-meta { font-size: {{ $isReceipt ? '11px' : '12.5px' }}; color: var(--soft); }
        .doc-title {
            text-align: {{ $isReceipt ? 'center' : 'right' }};
            font-size: {{ $isReceipt ? '13px' : '17px' }};
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .doc-meta { font-size: 12px; color: var(--soft); text-align: {{ $isReceipt ? 'center' : 'right' }}; }
        .logo { max-height: 46px; margin-bottom: 6px; }

        .parties { display: flex; gap: 26px; margin: 18px 0; {{ $isReceipt ? 'flex-direction:column; gap:10px; margin:12px 0;' : '' }} }
        .parties > div { flex: 1; }
        .label {
            font-size: 10.5px;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--soft);
            margin-bottom: 3px;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th {
            text-align: left;
            font-size: 10.5px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--soft);
            border-bottom: 1px solid var(--line);
            padding: 7px 6px;
        }
        td { padding: 8px 6px; border-bottom: 1px solid #f0ebe4; vertical-align: top; }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        tfoot td { border-bottom: none; padding: 5px 6px; }
        tfoot .grand td { border-top: 2px solid var(--ink); font-weight: 700; font-size: {{ $isReceipt ? '14px' : '16px' }}; padding-top: 9px; }

        .notes { margin-top: 16px; font-size: 12.5px; color: var(--soft); }
        .foot {
            margin-top: 22px;
            padding-top: 14px;
            border-top: 1px {{ $isReceipt ? 'dashed' : 'solid' }} var(--line);
            font-size: {{ $isReceipt ? '11px' : '12px' }};
            color: var(--soft);
            text-align: center;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; max-width: none; padding: 0; }
            .toolbar { display: none; }
            @page { margin: {{ $isReceipt ? '4mm' : '14mm' }}; size: {{ $isReceipt ? '80mm auto' : 'A4' }}; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('admin.orders.show', $order) }}">Back</a>
        <a href="{{ $isReceipt ? route('admin.orders.invoice', $order) : route('admin.orders.receipt', $order) }}">
            {{ $isReceipt ? 'A4 invoice' : '80mm receipt' }}
        </a>
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="sheet">
        <div class="head">
            <div>
                @if (config('site.logo'))
                    <img class="logo" src="{{ url(config('site.logo')) }}" alt="{{ config('site.name') }}">
                @endif
                <div class="brand-name">{{ config('site.name') }}</div>
                <div class="brand-meta">
                    {{ config('site.address') }}<br>
                    {{ config('site.phone') }} &middot; {{ config('site.email') }}
                </div>
            </div>
            <div>
                <div class="doc-title">{{ $isReceipt ? 'Receipt' : 'Invoice' }}</div>
                <div class="doc-meta">
                    {{ $order->order_number }}<br>
                    {{ $order->created_at->format('d M Y, H:i') }}
                </div>
            </div>
        </div>

        <div class="parties">
            <div>
                <div class="label">Billed to</div>
                <strong>{{ $order->customer_name }}</strong><br>
                {{ $order->phone }}
                @if ($order->email)<br>{{ $order->email }}@endif
            </div>
            <div>
                <div class="label">{{ $order->order_type === 'pickup' ? 'Collection' : 'Delivery address' }}</div>
                {{ $order->order_type === 'pickup' ? 'Pickup from the restaurant' : $order->address }}
                <br><span class="brand-meta">{{ ucfirst($order->order_type) }} &middot; {{ $paymentMethodLabel }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Qty</th>
                    @unless ($isReceipt)<th class="num">Unit</th>@endunless
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="num">{{ $item->quantity }}</td>
                        @unless ($isReceipt)<td class="num">{{ number_format((float) $item->price, 2) }}</td>@endunless
                        <td class="num">{{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php $cols = $isReceipt ? 2 : 3; @endphp
                <tr>
                    <td colspan="{{ $cols }}" class="num">Subtotal</td>
                    <td class="num">{{ $currency }} {{ number_format((float) $order->subtotal, 2) }}</td>
                </tr>
                @if ($order->delivery_fee > 0)
                    <tr>
                        <td colspan="{{ $cols }}" class="num">Delivery</td>
                        <td class="num">{{ $currency }} {{ number_format((float) $order->delivery_fee, 2) }}</td>
                    </tr>
                @endif
                @if ($order->tax > 0)
                    <tr>
                        <td colspan="{{ $cols }}" class="num">Tax</td>
                        <td class="num">{{ $currency }} {{ number_format((float) $order->tax, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td colspan="{{ $cols }}" class="num">Total</td>
                    <td class="num">{{ $currency }} {{ number_format((float) $order->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($order->notes)
            <div class="notes"><strong>Notes:</strong> {{ $order->notes }}</div>
        @endif

        <div class="foot">
            Thank you for ordering from {{ config('site.name') }}.<br>
            {{ config('site.opening_hours') }}
        </div>
    </div>
</body>
</html>

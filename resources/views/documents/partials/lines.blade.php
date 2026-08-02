<table style="width:100%; border-collapse:collapse; margin-block:14px; font-size:0.86rem;">
    <thead>
        <tr style="background:var(--doc-bg-soft); border-block:1px solid var(--doc-line);">
            <th style="text-align:start; padding:8px;">{{ __('documents.description') }} / Description</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.quantity') }} / Qty</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.unit_price') }} / Unit</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.net_amount') }} / Net</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.vat_rate') }} / VAT%</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.vat_amount') }} / VAT</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.line_total') }} / Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($document->lines as $line)
            <tr style="border-block-end:1px solid var(--doc-line);">
                <td style="padding:8px;">
                    {{ $line->name_ar ?: $line->name_en }}
                    @if ($line->name_ar && $line->name_en)
                        <div style="color:var(--doc-soft); font-size:0.8em;">{{ $line->name_en }}</div>
                    @endif
                    @if ($line->is_delivery_fee)
                        <div style="color:var(--doc-soft); font-size:0.78em;">{{ __('documents.delivery_fee_line') }}</div>
                    @endif
                </td>
                <td style="text-align:end; padding:8px;">{{ rtrim(rtrim(number_format($line->quantity_milli / 1000, 3), '0'), '.') }}</td>
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimal($line->unit_price_minor, $document->currency) }}</td>
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimal($line->line_net_minor, $document->currency) }}</td>
                <td style="text-align:end; padding:8px;">{{ $line->vat_category === 'S' ? number_format($line->vat_rate_bp / 100, 2).'%' : $line->vat_category }}</td>
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimal($line->line_vat_minor, $document->currency) }}</td>
                <td style="text-align:end; padding:8px;"><strong>{{ \App\Services\Billing\Money::toDecimal($line->line_total_minor, $document->currency) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="width:100%; border-collapse:collapse; margin-block:14px; font-size:0.86rem;">
    <thead>
        <tr style="background:var(--doc-bg-soft); border-block:1px solid var(--doc-line);">
            <th style="text-align:start; padding:8px;">{{ __('documents.description') }} / {{ __('documents.description', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.quantity') }} / {{ __('documents.quantity', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.unit_price') }} / {{ __('documents.unit_price', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.net_amount') }} / {{ __('documents.net_amount', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.vat_rate') }} / {{ __('documents.vat_rate', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.vat_amount') }} / {{ __('documents.vat_amount', [], 'en') }}</th>
            <th style="text-align:end; padding:8px;">{{ __('documents.line_total') }} / {{ __('documents.line_total', [], 'en') }}</th>
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
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimalFromExponent($line->unit_price_minor, $document->currency_exponent) }}</td>
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_net_minor, $document->currency_exponent) }}</td>
                <td style="text-align:end; padding:8px;">{{ $line->vat_category === 'S' ? number_format($line->vat_rate_bp / 100, 2).'%' : $line->vat_category }}</td>
                <td style="text-align:end; padding:8px;">{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_vat_minor, $document->currency_exponent) }}</td>
                <td style="text-align:end; padding:8px;"><strong>{{ \App\Services\Billing\Money::toDecimalFromExponent($line->line_total_minor, $document->currency_exponent) }}</strong></td>
            </tr>
        @endforeach
    </tbody>
</table>

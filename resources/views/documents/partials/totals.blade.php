<div style="display:flex; justify-content:flex-end;">
    <table style="min-width:280px; font-size:0.9rem;">
        <tr>
            <td style="padding:4px 8px;">{{ __('documents.subtotal') }} / {{ __('documents.subtotal', [], 'en') }}</td>
            <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimalFromExponent($document->subtotal_net_minor, $document->currency_exponent) }}</td>
        </tr>
        <tr>
            <td style="padding:4px 8px;">{{ __('documents.total_vat') }} / {{ __('documents.total_vat', [], 'en') }}</td>
            <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimalFromExponent($document->vat_total_minor, $document->currency_exponent) }}</td>
        </tr>
        @if ($document->rounding_adjustment_minor !== 0)
            <tr>
                <td style="padding:4px 8px;">{{ __('documents.rounding') }} / {{ __('documents.rounding', [], 'en') }}</td>
                <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimalFromExponent($document->rounding_adjustment_minor, $document->currency_exponent) }}</td>
            </tr>
        @endif
        <tr style="border-block-start:2px solid var(--doc-ink); font-weight:700; font-size:1.05rem;">
            <td style="padding:8px;">{{ __('documents.grand_total') }} / {{ __('documents.grand_total', [], 'en') }}</td>
            <td style="padding:8px; text-align:end;">{{ $document->currency }} {{ \App\Services\Billing\Money::toDecimalFromExponent($document->grand_total_minor, $document->currency_exponent) }}</td>
        </tr>
    </table>
</div>

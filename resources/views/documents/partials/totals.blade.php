<div style="display:flex; justify-content:flex-end;">
    <table style="min-width:280px; font-size:0.9rem;">
        <tr>
            <td style="padding:4px 8px;">{{ __('documents.subtotal') }} / Subtotal</td>
            <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimal($document->subtotal_net_minor, $document->currency) }}</td>
        </tr>
        <tr>
            <td style="padding:4px 8px;">{{ __('documents.total_vat') }} / Total VAT</td>
            <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimal($document->vat_total_minor, $document->currency) }}</td>
        </tr>
        @if ($document->rounding_adjustment_minor !== 0)
            <tr>
                <td style="padding:4px 8px;">{{ __('documents.rounding') }} / Rounding</td>
                <td style="padding:4px 8px; text-align:end;">{{ \App\Services\Billing\Money::toDecimal($document->rounding_adjustment_minor, $document->currency) }}</td>
            </tr>
        @endif
        <tr style="border-block-start:2px solid var(--doc-ink); font-weight:700; font-size:1.05rem;">
            <td style="padding:8px;">{{ __('documents.grand_total') }} / Grand Total</td>
            <td style="padding:8px; text-align:end;">{{ $document->currency }} {{ \App\Services\Billing\Money::toDecimal($document->grand_total_minor, $document->currency) }}</td>
        </tr>
    </table>
</div>

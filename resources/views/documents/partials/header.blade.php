<div class="doc-block" style="display:flex; justify-content:space-between; gap:24px; border-inline-start:none; padding-inline-start:0;">
    <div>
        @if ($document->seller_logo_path)
            <img src="{{ asset($document->seller_logo_path) }}" alt="" style="max-height:56px; max-width:180px; margin-block-end:8px;">
        @endif
        <div style="font-size:1.15rem; font-weight:700;">{{ $document->seller_name_ar ?: $document->seller_name_en }}</div>
        <div style="font-size:0.86rem; color:var(--doc-soft);">{{ $document->seller_name_en }}</div>

        @if ($document->seller_vat_number)
            <div style="font-size:0.82rem; margin-block-start:6px;">
                {{ __('documents.vat_number') }} / {{ __('documents.vat_number', [], 'en') }}: <strong>{{ $document->seller_vat_number }}</strong>
            </div>
        @endif
        @if ($document->seller_cr_number)
            <div style="font-size:0.82rem;">{{ __('documents.cr_number') }} / {{ __('documents.cr_number', [], 'en') }}: {{ $document->seller_cr_number }}</div>
        @endif
        @if ($document->seller_address_ar || $document->seller_address_en)
            <div style="font-size:0.8rem; color:var(--doc-soft); margin-block-start:4px;">
                {{ $document->seller_address_ar ?: $document->seller_address_en }}
            </div>
        @endif
    </div>

    <div class="doc-text-end">
        <div style="font-size:1.3rem; font-weight:700; color:var(--doc-accent);">
            {{ __('documents.'.$document->document_type) }}
        </div>
        <div style="font-size:0.9rem; color:var(--doc-soft);">
            {{ __('documents.'.$document->document_type, [], 'en') }}
        </div>

        <div style="margin-block-start:10px; font-size:0.86rem;">
            <div>{{ __('documents.document_number') }}: <strong class="doc-mono">{{ $document->document_number }}</strong></div>
            @if ($document->order_number)
                <div>{{ __('documents.order_number') }}: <strong class="doc-mono">{{ $document->order_number }}</strong></div>
            @endif
            <div>{{ __('documents.issue_date') }}: <strong>{{ optional($document->issue_date)->format('d/m/Y') }}</strong></div>
            @if ($document->document_type === 'credit_note' && $document->parentDocument)
                <div>
                    {{ __('documents.credited_against') }} / {{ __('documents.credited_against', [], 'en') }}:
                    <strong class="doc-mono">{{ $document->parentDocument->document_number }}</strong>
                    ({{ optional($document->parentDocument->issue_date)->format('d/m/Y') }})
                </div>
            @endif
            @if ($document->hijri_date)
                <div>{{ __('documents.hijri_date') }}: <strong>{{ $document->hijri_date }}</strong></div>
            @endif
        </div>
    </div>
</div>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $document->document_number }} &mdash; {{ $document->seller_name_en }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/document.css') }}">
    <style>
        @media print { .doc-toolbar { display: none; } }
    </style>
</head>
<body style="margin:0; padding:24px; background:var(--doc-bg-soft);">
    <div class="doc-toolbar" dir="rtl" style="max-width:820px; margin-inline:auto; margin-block-end:12px; display:flex; gap:10px; font-family:sans-serif;">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="doc" dir="rtl" style="max-width:820px; padding:32px; box-shadow:0 10px 34px rgba(32,21,18,0.12);">
        @include('documents.partials.header', ['document' => $document])

        @if ($document->buyer_name_en || $document->buyer_name_ar)
            <div class="doc-block">
                <div style="font-size:0.78rem; color:var(--doc-soft);">{{ __('documents.buyer') }} / {{ __('documents.buyer', [], 'en') }}</div>
                <div style="font-weight:700;">{{ $document->buyer_name_ar ?: $document->buyer_name_en }}</div>
                @if ($document->buyer_name_ar && $document->buyer_name_en)
                    <div style="font-size:0.86rem; color:var(--doc-soft);">{{ $document->buyer_name_en }}</div>
                @endif
                @if ($document->buyer_vat_number)
                    <div style="font-size:0.82rem;">{{ __('documents.vat_number') }}: {{ $document->buyer_vat_number }}</div>
                @endif
            </div>
        @endif

        @include('documents.partials.lines', ['document' => $document])
        @include('documents.partials.totals', ['document' => $document])

        @if ($document->document_type === 'credit_note' && $document->credit_reason)
            <div class="doc-block">
                <div style="font-size:0.78rem; color:var(--doc-soft);">{{ __('documents.credit_reason') }} / {{ __('documents.credit_reason', [], 'en') }}</div>
                <div>{{ $document->credit_reason }}</div>
            </div>
        @endif

        @if ($qrDataUri)
            @include('documents.partials.qr', ['document' => $document, 'qrDataUri' => $qrDataUri])
        @endif

        @if ($document->footer_ar || $document->footer_en)
            <div style="margin-block-start:20px; padding-block-start:14px; border-block-start:1px solid var(--doc-line); font-size:0.78rem; color:var(--doc-soft);">
                @if ($document->footer_ar)<div>{{ $document->footer_ar }}</div>@endif
                @if ($document->footer_en)<div>{{ $document->footer_en }}</div>@endif
            </div>
        @endif
    </div>
</body>
</html>

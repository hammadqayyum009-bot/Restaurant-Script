@php
    $preview = $current
        ? (Str::startsWith($current, ['http://', 'https://']) ? $current : asset($current))
        : null;
@endphp

<div class="a-field">
    <span class="a-label">{{ $label }}</span>
    <div class="a-media">
        <img id="{{ $previewId }}" class="a-media-preview" style="width:120px; height:78px;"
             src="{{ $preview ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
        <div class="a-media-body">
            <input type="file" name="{{ $field }}" accept="image/*" class="a-input" data-preview="{{ $previewId }}">
            <input type="text" name="{{ $urlField }}" class="a-input" style="margin-top:8px;"
                   value="{{ old($urlField, $current) }}" placeholder="…or paste an image URL">
            <span class="a-hint">An uploaded file always wins over the URL box.</span>
        </div>
    </div>
</div>

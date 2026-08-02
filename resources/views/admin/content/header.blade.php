@extends('admin.layouts.app')

@section('title', 'Header & navigation')

@section('content')
    <form method="POST" action="{{ route('admin.content.header.save') }}">
        @csrf @method('PUT')

        <div class="a-card" style="max-width:820px;">
            <div class="a-card-head"><h2>Navigation links</h2></div>
            <p class="a-card-sub">These appear in the top bar and the mobile menu. Leave a row blank to remove it.</p>

            <div id="nav-rows">
                @foreach ($navLinks as $link)
                    <div class="a-repeat-row">
                        <input type="text" name="nav_label[]" class="a-input" value="{{ $link['label'] }}" placeholder="Label">
                        <input type="text" name="nav_url[]" class="a-input" value="{{ $link['url'] }}" placeholder="/menu">
                        <button type="button" class="a-btn ghost sm" data-remove-row>Remove</button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="a-btn ghost sm" data-add-row="nav-rows">Add link</button>
        </div>

        <div class="a-card" style="max-width:820px;">
            <div class="a-card-head"><h2>Header button</h2></div>

            <label class="a-check">
                <input type="checkbox" name="header_show_cta" value="1" {{ $settings->bool('header_show_cta', true) ? 'checked' : '' }}>
                <span><strong>Show the call-to-action button</strong><small>Appears beside the links on wide screens and at the bottom of the mobile menu.</small></span>
            </label>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="header_cta_label">Button text</label>
                    <input type="text" id="header_cta_label" name="header_cta_label" class="a-input" maxlength="40"
                           value="{{ old('header_cta_label', $settings->get('header_cta_label', 'Order Online')) }}">
                </div>

                <div class="a-field">
                    <label for="header_cta_url">Button link</label>
                    <input type="text" id="header_cta_url" name="header_cta_url" class="a-input"
                           value="{{ old('header_cta_url', $settings->get('header_cta_url', '/menu')) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="header_brand_subtitle">Text under the restaurant name</label>
                <input type="text" id="header_brand_subtitle" name="header_brand_subtitle" class="a-input" maxlength="60"
                       value="{{ old('header_brand_subtitle', $settings->get('header_brand_subtitle', 'Gulf Cuisine')) }}">
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save header</button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('[data-add-row]');
        if (addBtn) {
            var host = document.getElementById(addBtn.getAttribute('data-add-row'));
            var first = host.querySelector('.a-repeat-row');
            var clone = first.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            host.appendChild(clone);
            return;
        }

        var removeBtn = e.target.closest('[data-remove-row]');
        if (removeBtn) {
            var row = removeBtn.closest('.a-repeat-row');
            var parent = row.parentNode;
            // Keep one row so there is always something to clone from.
            if (parent.querySelectorAll('.a-repeat-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            }
        }
    });
</script>
@endpush

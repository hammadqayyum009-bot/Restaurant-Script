@extends('admin.layouts.app')

@section('title', 'Footer')

@section('content')
    <form method="POST" action="{{ route('admin.content.footer.save') }}">
        @csrf @method('PUT')

        <div class="a-card" style="max-width:820px;">
            <div class="a-card-head"><h2>About block</h2></div>

            <div class="a-field">
                <label for="footer_about">Short description</label>
                <textarea id="footer_about" name="footer_about" class="a-textarea" maxlength="600">{{ old('footer_about', $settings->get('footer_about', "Authentic Gulf Cuisine, Served with Soul. We bring the warmth of Gulf hospitality and the rich flavours of Arabian grills, machboos and mandi to your table — whether you dine in, pick up, or order online.")) }}</textarea>
            </div>

            <div class="a-field">
                <label for="footer_copyright">Copyright line</label>
                <input type="text" id="footer_copyright" name="footer_copyright" class="a-input" maxlength="200"
                       value="{{ old('footer_copyright', $settings->get('footer_copyright')) }}"
                       placeholder="&copy; {{ date('Y') }} {{ config('site.name') }}. All rights reserved.">
                <span class="a-hint">Leave blank to use the default line with the current year.</span>
            </div>

            <div class="a-row cols-3">
                <div class="a-field">
                    <label for="footer_explore_heading">Links column heading</label>
                    <input type="text" id="footer_explore_heading" name="footer_explore_heading" class="a-input"
                           value="{{ old('footer_explore_heading', $settings->get('footer_explore_heading', 'Explore')) }}">
                </div>
                <div class="a-field">
                    <label for="footer_legal_heading">Legal column heading</label>
                    <input type="text" id="footer_legal_heading" name="footer_legal_heading" class="a-input"
                           value="{{ old('footer_legal_heading', $settings->get('footer_legal_heading', 'Legal')) }}">
                </div>
                <div class="a-field">
                    <label for="footer_contact_heading">Contact column heading</label>
                    <input type="text" id="footer_contact_heading" name="footer_contact_heading" class="a-input"
                           value="{{ old('footer_contact_heading', $settings->get('footer_contact_heading', 'Visit Us')) }}">
                </div>
            </div>
        </div>

        <div class="a-card" style="max-width:820px;">
            <div class="a-card-head"><h2>Explore links</h2></div>
            <div id="explore-rows">
                @foreach ($exploreLinks as $link)
                    <div class="a-repeat-row">
                        <input type="text" name="explore_label[]" class="a-input" value="{{ $link['label'] }}" placeholder="Label">
                        <input type="text" name="explore_url[]" class="a-input" value="{{ $link['url'] }}" placeholder="/menu">
                        <button type="button" class="a-btn ghost sm" data-remove-row>Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="a-btn ghost sm" data-add-row="explore-rows">Add link</button>
        </div>

        <div class="a-card" style="max-width:820px;">
            <div class="a-card-head"><h2>Legal links</h2></div>
            <div id="legal-rows">
                @foreach ($legalLinks as $link)
                    <div class="a-repeat-row">
                        <input type="text" name="legal_label[]" class="a-input" value="{{ $link['label'] }}" placeholder="Label">
                        <input type="text" name="legal_url[]" class="a-input" value="{{ $link['url'] }}" placeholder="/pages/privacy-policy">
                        <button type="button" class="a-btn ghost sm" data-remove-row>Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="a-btn ghost sm" data-add-row="legal-rows">Add link</button>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save footer</button>
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
            var clone = host.querySelector('.a-repeat-row').cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            host.appendChild(clone);
            return;
        }

        var removeBtn = e.target.closest('[data-remove-row]');
        if (removeBtn) {
            var row = removeBtn.closest('.a-repeat-row');
            var parent = row.parentNode;
            if (parent.querySelectorAll('.a-repeat-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            }
        }
    });
</script>
@endpush

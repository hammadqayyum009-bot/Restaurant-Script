@extends('admin.layouts.app')

@section('title', 'Admin panel settings')

@section('content')
    <form method="POST" action="{{ route('admin.settings.panel.save') }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="a-card" style="max-width:760px;">
            <div class="a-card-head"><h2>Panel branding</h2></div>
            <p class="a-card-sub">This is what you and your staff see — it is separate from the public website branding.</p>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="panel_name">Panel name</label>
                    <input type="text" id="panel_name" name="panel_name" class="a-input @error('panel_name') has-error @enderror"
                           value="{{ old('panel_name', config('panel.name')) }}" required>
                    @error('panel_name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="panel_short_name">Short label</label>
                    <input type="text" id="panel_short_name" name="panel_short_name" class="a-input" maxlength="40"
                           value="{{ old('panel_short_name', config('panel.short_name')) }}">
                    <span class="a-hint">Shown under the panel name in the sidebar.</span>
                </div>
            </div>

            <div class="a-field">
                <label for="panel_footer_note">Footer note</label>
                <input type="text" id="panel_footer_note" name="panel_footer_note" class="a-input" maxlength="150"
                       value="{{ old('panel_footer_note', config('panel.footer_note')) }}">
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <span class="a-label">Panel logo</span>
                    <div class="a-media">
                        <img id="panel-logo-preview" class="a-media-preview"
                             src="{{ config('panel.logo') ? asset(config('panel.logo')) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="logo" accept="image/*" class="a-input" data-preview="panel-logo-preview">
                            @if (config('panel.logo'))
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_logo" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove logo</strong></span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="a-field">
                    <span class="a-label">Panel favicon</span>
                    <div class="a-media">
                        <img id="panel-favicon-preview" class="a-media-preview" style="width:48px; height:48px;"
                             src="{{ config('panel.favicon') ? asset(config('panel.favicon')) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="favicon" accept="image/*" class="a-input" data-preview="panel-favicon-preview">
                            @if (config('panel.favicon'))
                                <label class="a-check" style="margin:8px 0 0; padding:7px 10px;">
                                    <input type="checkbox" name="remove_favicon" value="1">
                                    <span><strong style="font-size:0.82rem;">Remove favicon</strong></span>
                                </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save panel settings</button>
            </div>
        </div>
    </form>
@endsection

@extends('admin.layouts.app')

@section('title', $page->exists ? 'Edit page' : 'New page')

@section('content')
    <form method="POST" action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}">
        @csrf
        @if ($page->exists) @method('PUT') @endif

        <div class="a-card">
            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="title">Page title</label>
                    <input type="text" id="title" name="title" class="a-input @error('title') has-error @enderror"
                           value="{{ old('title', $page->title) }}" required>
                    @error('title')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="slug">Address</label>
                    <input type="text" id="slug" name="slug" class="a-input @error('slug') has-error @enderror"
                           value="{{ old('slug', $page->slug) }}" placeholder="auto from the title">
                    <span class="a-hint">The page will live at /pages/<em>slug</em>.</span>
                    @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="a-field">
                <label for="meta_description">Search description</label>
                <input type="text" id="meta_description" name="meta_description" class="a-input" maxlength="255"
                       value="{{ old('meta_description', $page->meta_description) }}">
                <span class="a-hint">Shown by Google under the page title.</span>
            </div>

            <div class="a-field">
                <label for="content">Content</label>
                <textarea id="content" name="content" class="a-textarea tall @error('content') has-error @enderror" required>{{ old('content', $page->content) }}</textarea>
                <span class="a-hint">HTML is allowed — use &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt; and &lt;li&gt; to structure the page.</span>
                @error('content')<span class="a-error">{{ $message }}</span>@enderror
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">{{ $page->exists ? 'Save changes' : 'Create page' }}</button>
                <a href="{{ route('admin.pages.index') }}" class="a-btn ghost">Cancel</a>
            </div>
        </div>
    </form>
@endsection

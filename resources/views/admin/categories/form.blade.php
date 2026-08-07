@extends('admin.layouts.app')

@section('title', $category->exists ? 'Edit category' : 'New category')

@section('content')
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists) @method('PUT') @endif

        <div class="a-card" style="max-width:680px;">
            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="name">Category name</label>
                    <input type="text" id="name" name="name" class="a-input @error('name') has-error @enderror"
                           value="{{ old('name', $category->name) }}" required>
                    @error('name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="icon">Icon</label>
                    <input type="text" id="icon" name="icon" class="a-input" maxlength="20"
                           value="{{ old('icon', $category->icon) }}" placeholder="🥗">
                    <span class="a-hint">A single emoji shown beside the name.</span>
                </div>
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="slug">URL slug</label>
                    <input type="text" id="slug" name="slug" class="a-input @error('slug') has-error @enderror"
                           value="{{ old('slug', $category->slug) }}" placeholder="auto from the name">
                    @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="sort_order">Position</label>
                    <input type="number" id="sort_order" name="sort_order" class="a-input" min="0"
                           value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                    <span class="a-hint">Lower numbers appear first.</span>
                </div>
            </div>

            <label class="a-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
                <span>
                    <strong>Show on the website</strong>
                    <small>Hidden categories keep their dishes but disappear from the menu.</small>
                </span>
            </label>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">{{ $category->exists ? 'Save changes' : 'Create category' }}</button>
                <a href="{{ route('admin.categories.index') }}" class="a-btn ghost">Cancel</a>
            </div>
        </div>
    </form>
@endsection

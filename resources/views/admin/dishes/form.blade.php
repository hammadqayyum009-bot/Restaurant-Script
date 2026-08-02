@extends('admin.layouts.app')

@section('title', $dish->exists ? 'Edit dish' : 'New dish')

@section('content')
    <form method="POST" enctype="multipart/form-data"
          action="{{ $dish->exists ? route('admin.dishes.update', $dish) : route('admin.dishes.store') }}">
        @csrf
        @if ($dish->exists) @method('PUT') @endif

        <div class="a-grid side">
            <div class="a-card">
                <div class="a-field">
                    <label for="name">Dish name</label>
                    <input type="text" id="name" name="name" class="a-input @error('name') has-error @enderror"
                           value="{{ old('name', $dish->name) }}" required>
                    @error('name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="a-textarea" maxlength="500"
                              placeholder="What's in it, how it's cooked…">{{ old('description', $dish->description) }}</textarea>
                </div>

                <div class="a-row cols-3">
                    <div class="a-field">
                        <label for="menu_category_id">Category</label>
                        <select id="menu_category_id" name="menu_category_id" class="a-select" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    {{ (string) old('menu_category_id', $dish->menu_category_id) === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="a-field">
                        <label for="price">Price ({{ config('site.currency') }})</label>
                        <input type="number" step="0.01" min="0" id="price" name="price"
                               class="a-input @error('price') has-error @enderror"
                               value="{{ old('price', $dish->price) }}" required>
                        @error('price')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label for="origin">Origin tag</label>
                        <input type="text" id="origin" name="origin" class="a-input" maxlength="60"
                               value="{{ old('origin', $dish->origin) }}" placeholder="UAE, Yemen…">
                    </div>
                </div>

                <div class="a-row cols-3">
                    <div class="a-field">
                        <label for="spice_level">Spice level</label>
                        <select id="spice_level" name="spice_level" class="a-select">
                            @foreach (['Not spicy', 'Mild', 'Medium', 'Hot'] as $level => $label)
                                <option value="{{ $level }}" {{ (string) old('spice_level', $dish->spice_level ?? 0) === (string) $level ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="a-field">
                        <label for="sort_order">Position</label>
                        <input type="number" id="sort_order" name="sort_order" class="a-input" min="0"
                               value="{{ old('sort_order', $dish->sort_order ?? 0) }}">
                    </div>

                    <div class="a-field">
                        <label for="slug">URL slug</label>
                        <input type="text" id="slug" name="slug" class="a-input @error('slug') has-error @enderror"
                               value="{{ old('slug', $dish->slug) }}" placeholder="auto from the name">
                        @error('slug')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div>
                <div class="a-card">
                    <h3>Photo</h3>
                    @php
                        $current = old('image_url', $dish->image);
                        $preview = $current
                            ? (Str::startsWith($current, ['http://', 'https://']) ? $current : asset($current))
                            : null;
                    @endphp

                    <div class="a-media" style="flex-direction:column; align-items:stretch;">
                        <img id="dish-preview" class="a-media-preview" style="width:100%; height:170px;"
                             src="{{ $preview ?? 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22/%3E' }}" alt="">
                        <div class="a-media-body">
                            <input type="file" name="image" accept="image/*" class="a-input" data-preview="dish-preview">
                            <span class="a-hint">JPG or PNG, up to 4&nbsp;MB. Landscape photos look best.</span>
                        </div>
                    </div>

                    <div class="a-field" style="margin-top:14px;">
                        <label for="image_url">…or paste an image URL</label>
                        <input type="text" id="image_url" name="image_url" class="a-input"
                               value="{{ old('image_url', $dish->image) }}" placeholder="https://…">
                        <span class="a-hint">An uploaded file always wins over this box.</span>
                    </div>
                </div>

                <div class="a-card">
                    <h3>Visibility</h3>
                    <label class="a-check">
                        <input type="checkbox" name="is_available" value="1" {{ old('is_available', $dish->is_available ?? true) ? 'checked' : '' }}>
                        <span>
                            <strong>Available to order</strong>
                            <small>Turn off when you run out — it stays in the menu list here.</small>
                        </span>
                    </label>

                    <label class="a-check">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $dish->is_featured ?? false) ? 'checked' : '' }}>
                        <span>
                            <strong>Signature dish</strong>
                            <small>Shows in the "Our Signature Dishes" row on the home page.</small>
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <div class="a-form-actions">
            <button type="submit" class="a-btn">{{ $dish->exists ? 'Save changes' : 'Create dish' }}</button>
            <a href="{{ route('admin.dishes.index') }}" class="a-btn ghost">Cancel</a>
        </div>
    </form>
@endsection

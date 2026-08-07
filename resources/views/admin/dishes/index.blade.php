@extends('admin.layouts.app')

@section('title', 'Dishes')

@section('actions')
    <a href="{{ route('admin.dishes.create') }}" class="a-btn sm">Add dish</a>
@endsection

@section('content')
    <div class="a-card">
        <form method="GET" class="a-filters">
            <input type="search" name="q" value="{{ $search }}" class="a-input" placeholder="Search dishes…">
            <select name="category" class="a-select">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) $activeCategory === (string) $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="a-btn ghost sm">Filter</button>
            @if ($search || $activeCategory)
                <a href="{{ route('admin.dishes.index') }}" class="a-btn ghost sm">Clear</a>
            @endif
        </form>

        @if ($dishes->isEmpty())
            <div class="a-empty">
                <strong>No dishes found</strong>
                @if ($search || $activeCategory)
                    Try a different search or category.
                @else
                    Add your first dish to start building the menu.
                @endif
            </div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Dish</th>
                            <th>Category</th>
                            <th class="num">Price</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dishes as $dish)
                            <tr>
                                <td>
                                    @if ($dish->image)
                                        <img src="{{ Str::startsWith($dish->image, ['http://', 'https://']) ? $dish->image : asset($dish->image) }}"
                                             alt="" class="thumb" loading="lazy">
                                    @else
                                        <div class="thumb" style="background:#efe9e1;"></div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $dish->name }}</strong>
                                    @if ($dish->is_featured)
                                        <span class="a-badge info" style="margin-left:6px;">Signature</span>
                                    @endif
                                    <div class="a-muted" style="font-size:0.78rem;">{{ Str::limit($dish->description, 62) }}</div>
                                </td>
                                <td>{{ $dish->category?->name ?? '—' }}</td>
                                <td class="num">{{ config('site.currency') }} {{ number_format((float) $dish->price, 2) }}</td>
                                <td>
                                    <span class="a-badge {{ $dish->is_available ? 'ok' : '' }}">
                                        {{ $dish->is_available ? 'Available' : 'Hidden' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('admin.dishes.edit', $dish) }}" class="a-btn ghost sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.dishes.destroy', $dish) }}" class="a-inline-form"
                                              data-confirm="Delete &quot;{{ $dish->name }}&quot;?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="a-btn danger sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $dishes->links() }}</div>
        @endif
    </div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Menu categories')

@section('actions')
    <a href="{{ route('admin.categories.create') }}" class="a-btn sm">Add category</a>
@endsection

@section('content')
    <div class="a-card">
        @if ($categories->isEmpty())
            <div class="a-empty">
                <strong>No categories yet</strong>
                Categories group your dishes on the menu page.
                <div style="margin-top:14px;"><a href="{{ route('admin.categories.create') }}" class="a-btn sm">Add your first category</a></div>
            </div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Category</th>
                            <th>Slug</th>
                            <th class="num">Dishes</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td class="a-muted">{{ $category->sort_order }}</td>
                                <td>
                                    <strong>{{ $category->icon }} {{ $category->name }}</strong>
                                </td>
                                <td class="a-mono a-muted">{{ $category->slug }}</td>
                                <td class="num">{{ $category->menu_items_count }}</td>
                                <td>
                                    <span class="a-badge {{ $category->is_active ? 'ok' : '' }}">
                                        {{ $category->is_active ? 'Visible' : 'Hidden' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="a-btn ghost sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="a-inline-form"
                                              data-confirm="Delete &quot;{{ $category->name }}&quot;? Its {{ $category->menu_items_count }} dishes will be deleted too.">
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
        @endif
    </div>
@endsection

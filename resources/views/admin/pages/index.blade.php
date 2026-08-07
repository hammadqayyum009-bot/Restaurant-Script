@extends('admin.layouts.app')

@section('title', 'Pages')

@section('actions')
    <a href="{{ route('admin.pages.create') }}" class="a-btn sm">Add page</a>
@endsection

@section('content')
    <div class="a-card">
        <p class="a-card-sub">
            These are the standalone pages linked from your footer and navigation — terms, privacy, FAQ and anything else you add.
        </p>

        @if ($pages->isEmpty())
            <div class="a-empty"><strong>No pages yet</strong>Add one to start.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Address</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td><strong>{{ $page->title }}</strong></td>
                                <td class="a-mono a-muted">/pages/{{ $page->slug }}</td>
                                <td class="a-muted">{{ $page->updated_at->format('d M Y') }}</td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('page.show', $page->slug) }}" target="_blank" rel="noopener" class="a-btn ghost sm">View</a>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="a-btn ghost sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="a-inline-form"
                                              data-confirm="Delete &quot;{{ $page->title }}&quot;?">
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

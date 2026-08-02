@extends('admin.layouts.app')

@section('title', 'Reviews')

@section('content')
    <div class="a-card">
        <div class="a-filters">
            <a href="{{ route('admin.reviews.index') }}" class="a-chip {{ $filter ? '' : 'active' }}">All reviews</a>
            <a href="{{ route('admin.reviews.index', ['filter' => 'pending']) }}" class="a-chip {{ $filter === 'pending' ? 'active' : '' }}">
                Awaiting approval{{ $pendingCount ? ' ('.$pendingCount.')' : '' }}
            </a>
        </div>

        @if ($reviews->isEmpty())
            <div class="a-empty"><strong>Nothing here</strong>Reviews left by guests will appear in this list.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>
                                    <strong>{{ $review->name }}</strong>
                                    <div class="a-muted" style="font-size:0.78rem;">{{ $review->created_at->format('d M Y') }}</div>
                                </td>
                                <td style="white-space:nowrap; color:#c9a24b;">
                                    {{ str_repeat('★', (int) $review->rating) }}<span style="color:#d9d1c8;">{{ str_repeat('★', 5 - (int) $review->rating) }}</span>
                                </td>
                                <td style="max-width:380px;">{{ $review->comment }}</td>
                                <td>
                                    <span class="a-badge {{ $review->is_approved ? 'ok' : 'warn' }}">
                                        {{ $review->is_approved ? 'Published' : 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="a-inline-form">
                                            @csrf @method('PUT')
                                            <button type="submit" class="a-btn ghost sm">{{ $review->is_approved ? 'Hide' : 'Publish' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" class="a-inline-form"
                                              data-confirm="Delete this review?">
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

            <div class="a-pagination">{{ $reviews->links() }}</div>
        @endif
    </div>
@endsection

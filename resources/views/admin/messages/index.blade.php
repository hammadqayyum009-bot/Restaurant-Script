@extends('admin.layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="a-card">
        <div class="a-filters">
            <a href="{{ route('admin.messages.index') }}" class="a-chip {{ $filter ? '' : 'active' }}">All messages</a>
            <a href="{{ route('admin.messages.index', ['filter' => 'unread']) }}" class="a-chip {{ $filter === 'unread' ? 'active' : '' }}">
                Unread{{ $unreadCount ? ' ('.$unreadCount.')' : '' }}
            </a>
        </div>

        @if ($messages->isEmpty())
            <div class="a-empty">
                <strong>No messages</strong>
                Anything sent through the website contact form arrives here.
            </div>
        @else
            <div class="a-stack">
                @foreach ($messages as $message)
                    <div class="a-card" style="box-shadow:none; {{ $message->is_read ? '' : 'border-left:3px solid var(--a-accent);' }}">
                        <div class="a-card-head" style="margin-bottom:10px;">
                            <div>
                                <h3 style="margin:0;">{{ $message->name }}</h3>
                                <div class="a-muted" style="font-size:0.8rem;">
                                    <a href="mailto:{{ $message->email }}">{{ $message->email }}</a>
                                    @if ($message->phone) &middot; <a href="tel:{{ $message->phone }}">{{ $message->phone }}</a> @endif
                                    &middot; {{ $message->created_at->format('d M Y, H:i') }}
                                </div>
                            </div>
                            <div class="a-actions">
                                <span class="a-badge {{ $message->is_read ? '' : 'warn' }}">{{ $message->is_read ? 'Read' : 'New' }}</span>
                            </div>
                        </div>

                        <p style="white-space:pre-line; margin-bottom:14px;">{{ $message->message }}</p>

                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: your message to '.config('site.name')) }}"
                               class="a-btn sm">Reply by email</a>
                            <form method="POST" action="{{ route('admin.messages.read', $message) }}" class="a-inline-form">
                                @csrf @method('PUT')
                                <button type="submit" class="a-btn ghost sm">Mark as {{ $message->is_read ? 'unread' : 'read' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" class="a-inline-form"
                                  data-confirm="Delete this message?">
                                @csrf @method('DELETE')
                                <button type="submit" class="a-btn danger sm">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="a-pagination">{{ $messages->links() }}</div>
        @endif
    </div>
@endsection

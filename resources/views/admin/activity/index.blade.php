@extends('admin.layouts.app')

@section('title', 'Activity log')

@section('content')
    <div class="a-card">
        <p class="a-card-sub">
            Every change made from this panel — who did it, when, and from which address.
        </p>

        <form method="GET" class="a-filters">
            <input type="search" name="q" value="{{ $search }}" class="a-input" placeholder="Search descriptions…">
            <select name="action" class="a-select">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" {{ $activeAction === $action ? 'selected' : '' }}>{{ ucfirst($action) }}</option>
                @endforeach
            </select>
            <select name="user" class="a-select">
                <option value="">Everyone</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" {{ (string) $activeUser === (string) $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="a-btn ghost sm">Filter</button>
            @if ($search || $activeAction || $activeUser)
                <a href="{{ route('admin.activity.index') }}" class="a-btn ghost sm">Clear</a>
            @endif
        </form>

        @if ($logs->isEmpty())
            <div class="a-empty">
                <strong>Nothing recorded yet</strong>
                Changes you make in the panel will be listed here.
            </div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Who</th>
                            <th>Action</th>
                            <th>What</th>
                            <th>From</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            @php
                                $tone = match ($log->action) {
                                    'created' => 'ok',
                                    'deleted' => 'danger',
                                    'settings', 'email' => 'info',
                                    default => '',
                                };
                            @endphp
                            <tr>
                                <td class="a-muted" style="white-space:nowrap;">
                                    {{ $log->created_at->format('d M, H:i') }}
                                    <div style="font-size:0.74rem;">{{ $log->created_at->diffForHumans() }}</div>
                                </td>
                                <td>{{ $log->user_name ?: '—' }}</td>
                                <td><span class="a-badge {{ $tone }}">{{ ucfirst($log->action) }}</span></td>
                                <td>
                                    {{ $log->description }}
                                    @if ($log->subject_type)
                                        <div class="a-muted" style="font-size:0.74rem;">
                                            {{ $log->subject_type }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="a-mono a-muted">{{ $log->ip ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection

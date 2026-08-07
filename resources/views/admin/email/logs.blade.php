@extends('admin.layouts.app')

@section('title', 'Delivery log')

@section('content')
    <div class="a-card">
        <div class="a-filters">
            <a href="{{ route('admin.email.logs') }}" class="a-chip {{ $activeStatus ? '' : 'active' }}">Everything</a>
            <a href="{{ route('admin.email.logs', ['status' => 'sent']) }}" class="a-chip {{ $activeStatus === 'sent' ? 'active' : '' }}">Sent</a>
            <a href="{{ route('admin.email.logs', ['status' => 'failed']) }}" class="a-chip {{ $activeStatus === 'failed' ? 'active' : '' }}">Failed</a>
        </div>

        @if ($logs->isEmpty())
            <div class="a-empty"><strong>Nothing sent yet</strong>Every email the site sends is recorded here.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="a-muted" style="white-space:nowrap;">{{ $log->created_at->format('d M, H:i') }}</td>
                                <td>
                                    {{ $log->to_email }}
                                    @if ($log->to_name)<div class="a-muted" style="font-size:0.78rem;">{{ $log->to_name }}</div>@endif
                                </td>
                                <td style="max-width:300px;">{{ $log->subject }}</td>
                                <td><span class="a-badge">{{ str_replace('_', ' ', $log->type) }}</span></td>
                                <td>
                                    <span class="a-badge {{ $log->status === 'sent' ? 'ok' : 'danger' }}">{{ ucfirst($log->status) }}</span>
                                    @if ($log->error)
                                        <div class="a-mono a-muted" style="font-size:0.74rem; margin-top:4px; max-width:340px; word-break:break-word;">
                                            {{ Str::limit($log->error, 160) }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $logs->links() }}</div>
        @endif
    </div>
@endsection

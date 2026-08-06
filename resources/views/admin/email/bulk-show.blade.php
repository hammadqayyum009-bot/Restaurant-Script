@extends('admin.layouts.app')

@section('title', 'Bulk email send')

@section('actions')
    @unless ($job->isCompleted())
        <form method="POST" action="{{ route('admin.email.bulk.process', $job) }}">
            @csrf
            <button type="submit" class="a-btn sm">Process next batch</button>
        </form>
    @endunless
@endsection

@section('content')
    <div class="a-card">
        <div class="a-card-head"><h2>{{ $job->subject }}</h2></div>
        <p class="a-card-sub" style="margin-top:0;">
            Audience: {{ $job->audience }}
            @if ($job->isCompleted())
                &middot; <span class="a-badge ok">Completed</span>
            @else
                &middot; <span class="a-badge warn">In progress</span>
            @endif
        </p>

        <div class="a-grid cols-3" style="margin-bottom:16px;">
            <div class="a-stat">
                <span class="label">Sent</span>
                <span class="value">{{ $job->sent_count }} / {{ $job->total_count }}</span>
            </div>
            <div class="a-stat">
                <span class="label">Failed</span>
                <span class="value">{{ $job->failed_count }}</span>
            </div>
            <div class="a-stat">
                <span class="label">Still pending</span>
                <span class="value">{{ $job->total_count - $job->sent_count - $job->failed_count }}</span>
            </div>
        </div>

        @unless ($job->isCompleted())
            <p class="a-card-sub">
                Not finished in one pass — the rest sends automatically within a few minutes
                (scheduled task), or click "Process next batch" above to send the next batch now.
            </p>
        @endunless

        @if ($job->recipients->isNotEmpty())
            <h3>Recent failures</h3>
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($job->recipients as $recipient)
                            <tr>
                                <td>{{ $recipient->name ?: $recipient->email }} &lt;{{ $recipient->email }}&gt;</td>
                                <td>{{ $recipient->error }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

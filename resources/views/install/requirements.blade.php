@extends('install.layout', ['step' => 2])

@section('content')
    <h3 style="margin-top:0;">Server Requirements</h3>

    <div class="fieldset-title">PHP Version</div>
    <div class="req-row">
        <span>PHP {{ PHP_VERSION }} (8.2+ required)</span>
        <span class="status {{ $phpOk ? 'ok' : 'fail' }}">{{ $phpOk ? 'OK' : 'FAILED' }}</span>
    </div>

    <div class="fieldset-title">PHP Extensions</div>
    @foreach ($extensions as $ext)
        <div class="req-row">
            <span>{{ $ext['name'] }}</span>
            <span class="status {{ $ext['loaded'] ? 'ok' : 'fail' }}">{{ $ext['loaded'] ? 'OK' : 'MISSING' }}</span>
        </div>
    @endforeach

    <div class="fieldset-title">Folder Permissions (writable)</div>
    @foreach ($permissions as $perm)
        <div class="req-row">
            <span>{{ $perm['path'] }}</span>
            <span class="status {{ $perm['writable'] ? 'ok' : 'fail' }}">{{ $perm['writable'] ? 'OK' : 'NOT WRITABLE' }}</span>
        </div>
    @endforeach

    @if (! $allOk)
        <div class="alert alert-error" style="margin-top:18px;">Please resolve the failed requirements above before continuing (e.g. run <code>chmod -R 775 storage bootstrap/cache</code> via cPanel File Manager).</div>
    @endif

    <div style="display:flex; gap:12px; margin-top:20px;">
        <a href="{{ route('install.requirements') }}" class="btn btn-outline on-light">Re-check</a>
        @if ($allOk)
            <a href="{{ route('install.database') }}" class="btn btn-primary" style="flex:1;">Continue</a>
        @else
            <button class="btn btn-primary" style="flex:1;" disabled>Continue</button>
        @endif
    </div>
@endsection

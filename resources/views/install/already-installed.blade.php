@extends('install.layout', ['step' => 4])

@section('content')
    <div class="text-center">
        <h3>Already Installed</h3>
        <p style="color:var(--ink-500)">Your restaurant website has already been set up. If you need to reinstall, delete <code>storage/installed.lock</code> from your server first.</p>
        <a href="{{ route('home') }}" class="btn btn-primary" style="margin-top:16px;">Visit Your Website</a>
    </div>
@endsection

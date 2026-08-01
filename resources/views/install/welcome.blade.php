@extends('install.layout', ['step' => 1])

@section('content')
    <h3 style="margin-top:0;">Welcome</h3>
    <p style="color:var(--ink-500)">This wizard will guide you through installing your restaurant website: checking server requirements, connecting your database, and creating your admin account. It only takes a couple of minutes.</p>
    <ul style="color:var(--ink-500); font-size:0.92rem; margin:16px 0; padding-left:20px; list-style:disc;">
        <li>Step 1 &mdash; Check server requirements</li>
        <li>Step 2 &mdash; Connect your database &amp; configure your restaurant</li>
        <li>Step 3 &mdash; Done! Your website goes live instantly</li>
    </ul>
    <a href="{{ route('install.requirements') }}" class="btn btn-primary btn-block">Start Installation</a>
@endsection

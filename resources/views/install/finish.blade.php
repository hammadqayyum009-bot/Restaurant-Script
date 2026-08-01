@extends('install.layout', ['step' => 4])

@section('content')
    <div class="text-center">
        <div class="success-icon" style="margin-bottom:18px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
        </div>
        <h3>Installation Complete!</h3>
        <p style="color:var(--ink-500)">Your restaurant website is now live with sample menu data. You can log in to manage your site and start customizing your menu.</p>
        <div style="display:flex; gap:12px; margin-top:20px; flex-wrap:wrap; justify-content:center;">
            <a href="{{ route('home') }}" class="btn btn-primary">Visit Your Website</a>
        </div>
        <div class="alert alert-success" style="margin-top:24px; text-align:left;">
            <strong>Important:</strong> For security, please delete or restrict access to the <code>/install</code> route now that setup is complete (this happens automatically since the installer locks itself, but restricting via your hosting control panel is recommended).
        </div>
    </div>
@endsection

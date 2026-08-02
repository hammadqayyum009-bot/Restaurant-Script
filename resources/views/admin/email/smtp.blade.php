@extends('admin.layouts.app')

@section('title', 'SMTP setup')

@section('content')
    @if (! config('mail.mailers.smtp.host'))
        <div class="a-alert warn">
            <div>
                <strong>No mail server configured.</strong>
                Until you fill this in, order confirmations, welcome emails and booking notices will not be delivered.
            </div>
        </div>
    @endif

    <div class="a-grid side">
        <form method="POST" action="{{ route('admin.email.smtp.save') }}">
            @csrf @method('PUT')

            <div class="a-card">
                <div class="a-card-head"><h2>Mail server</h2></div>
                <p class="a-card-sub">
                    Use the SMTP details from your hosting provider or mailbox — for cPanel these are usually
                    <span class="a-mono">mail.yourdomain.com</span>, port 465 with SSL.
                </p>

                <div class="a-row cols-2">
                    <div class="a-field">
                        <label for="mail_host">SMTP host</label>
                        <input type="text" id="mail_host" name="mail_host" class="a-input @error('mail_host') has-error @enderror"
                               value="{{ old('mail_host', config('mail.mailers.smtp.host')) }}" placeholder="mail.yourdomain.com" required>
                        @error('mail_host')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label for="mail_port">Port</label>
                        <input type="number" id="mail_port" name="mail_port" class="a-input @error('mail_port') has-error @enderror"
                               value="{{ old('mail_port', config('mail.mailers.smtp.port') ?: 465) }}" required>
                        @error('mail_port')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="a-row cols-2">
                    <div class="a-field">
                        <label for="mail_username">Username</label>
                        <input type="text" id="mail_username" name="mail_username" class="a-input"
                               value="{{ old('mail_username', config('mail.mailers.smtp.username')) }}" autocomplete="off">
                    </div>

                    <div class="a-field">
                        <label for="mail_password">Password</label>
                        <input type="password" id="mail_password" name="mail_password" class="a-input" autocomplete="new-password"
                               placeholder="{{ $settings->get('mail_password') ? 'Saved — leave blank to keep it' : '' }}">
                        <span class="a-hint">Stored in your database, not in a file.</span>
                    </div>
                </div>

                <div class="a-row cols-2">
                    <div class="a-field">
                        <label for="mail_encryption">Encryption</label>
                        <select id="mail_encryption" name="mail_encryption" class="a-select">
                            @foreach (['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'] as $value => $label)
                                <option value="{{ $value }}" {{ old('mail_encryption', $settings->get('mail_encryption', 'ssl')) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="a-field">
                        <label for="notify_admin_email">Send admin alerts to</label>
                        <input type="email" id="notify_admin_email" name="notify_admin_email" class="a-input"
                               value="{{ old('notify_admin_email', config('notifications.admin_email')) }}" placeholder="you@yourdomain.com">
                        <span class="a-hint">Where you receive new order and booking alerts.</span>
                    </div>
                </div>

                <div class="a-row cols-2">
                    <div class="a-field">
                        <label for="mail_from_address">Send from address</label>
                        <input type="email" id="mail_from_address" name="mail_from_address"
                               class="a-input @error('mail_from_address') has-error @enderror"
                               value="{{ old('mail_from_address', config('mail.from.address')) }}" required>
                        @error('mail_from_address')<span class="a-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="a-field">
                        <label for="mail_from_name">Send from name</label>
                        <input type="text" id="mail_from_name" name="mail_from_name"
                               class="a-input @error('mail_from_name') has-error @enderror"
                               value="{{ old('mail_from_name', config('mail.from.name') ?: config('site.name')) }}" required>
                        @error('mail_from_name')<span class="a-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="a-card">
                <div class="a-card-head"><h2>Automatic emails</h2></div>
                <p class="a-card-sub">Choose which events trigger an email. Wording lives under <a href="{{ route('admin.email.templates') }}">Templates</a>.</p>

                <label class="a-check">
                    <input type="checkbox" name="notify_on_register" value="1" {{ old('notify_on_register', config('notifications.on_register')) ? 'checked' : '' }}>
                    <span><strong>Welcome email when someone registers</strong></span>
                </label>
                <label class="a-check">
                    <input type="checkbox" name="notify_on_order" value="1" {{ old('notify_on_order', config('notifications.on_order')) ? 'checked' : '' }}>
                    <span><strong>Order confirmation to the customer</strong></span>
                </label>
                <label class="a-check">
                    <input type="checkbox" name="notify_on_order_status" value="1" {{ old('notify_on_order_status', config('notifications.on_order_status')) ? 'checked' : '' }}>
                    <span><strong>Update the customer when an order status changes</strong></span>
                </label>
                <label class="a-check">
                    <input type="checkbox" name="notify_on_reservation" value="1" {{ old('notify_on_reservation', config('notifications.on_reservation')) ? 'checked' : '' }}>
                    <span><strong>Confirmation when a table is booked or its status changes</strong></span>
                </label>
                <label class="a-check">
                    <input type="checkbox" name="notify_copy_admin_on_order" value="1" {{ old('notify_copy_admin_on_order', config('notifications.copy_admin_on_order')) ? 'checked' : '' }}>
                    <span><strong>Alert me about every new order</strong></span>
                </label>
                <label class="a-check">
                    <input type="checkbox" name="notify_copy_admin_on_reservation" value="1" {{ old('notify_copy_admin_on_reservation', config('notifications.copy_admin_on_reservation')) ? 'checked' : '' }}>
                    <span><strong>Alert me about every new table request</strong></span>
                </label>

                <div class="a-form-actions">
                    <button type="submit" class="a-btn">Save email settings</button>
                </div>
            </div>
        </form>

        <div>
            <div class="a-card">
                <h3>Send a test</h3>
                <p class="a-card-sub" style="margin-top:0;">Save your settings first, then send yourself a message to confirm they work.</p>
                <form method="POST" action="{{ route('admin.email.test') }}">
                    @csrf
                    <div class="a-field">
                        <label for="test_email">Send test to</label>
                        <input type="email" id="test_email" name="test_email" class="a-input"
                               value="{{ old('test_email', auth()->user()->email) }}" required>
                    </div>
                    <button type="submit" class="a-btn block">Send test email</button>
                </form>
            </div>

            @if ($lastFailure)
                <div class="a-card">
                    <h3>Last failure</h3>
                    <p class="a-muted" style="font-size:0.82rem;">
                        {{ $lastFailure->created_at->diffForHumans() }} &middot; to {{ $lastFailure->to_email }}
                    </p>
                    <p class="a-mono" style="font-size:0.78rem; background:#fbeceb; padding:10px; border-radius:8px; word-break:break-word;">
                        {{ Str::limit($lastFailure->error, 400) }}
                    </p>
                    <a href="{{ route('admin.email.logs', ['status' => 'failed']) }}" class="a-btn ghost sm">See all failures</a>
                </div>
            @endif
        </div>
    </div>
@endsection

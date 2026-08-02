@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit user' : 'New user')

@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <div class="a-card" style="max-width:680px;">
            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" class="a-input @error('name') has-error @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" class="a-input" value="{{ old('phone', $user->phone) }}">
                </div>
            </div>

            <div class="a-field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" class="a-input @error('email') has-error @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email')<span class="a-error">{{ $message }}</span>@enderror
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="password">{{ $user->exists ? 'New password' : 'Password' }}</label>
                    <input type="password" id="password" name="password" class="a-input @error('password') has-error @enderror"
                           autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                    @error('password')<span class="a-error">{{ $message }}</span>@enderror
                    @if ($user->exists)<span class="a-hint">Leave blank to keep the current password.</span>@endif
                </div>

                <div class="a-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="a-input"
                           autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                </div>
            </div>

            @php $isSelf = $user->exists && $user->id === auth()->id(); @endphp

            <label class="a-check">
                <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}
                       {{ $isSelf ? 'disabled checked' : '' }}>
                <span>
                    <strong>Admin access</strong>
                    <small>{{ $isSelf ? 'You cannot remove your own admin access.' : 'Can sign in to this panel and change everything.' }}</small>
                </span>
            </label>

            <label class="a-check">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}
                       {{ $isSelf ? 'disabled checked' : '' }}>
                <span>
                    <strong>Account active</strong>
                    <small>Suspended accounts cannot sign in and are skipped by bulk email.</small>
                </span>
            </label>

            @unless ($user->exists)
                <label class="a-check">
                    <input type="checkbox" name="send_welcome" value="1" checked>
                    <span>
                        <strong>Send the welcome email</strong>
                        <small>Uses your saved welcome template. Requires SMTP to be configured.</small>
                    </span>
                </label>
            @endunless

            <div class="a-form-actions">
                <button type="submit" class="a-btn">{{ $user->exists ? 'Save changes' : 'Create user' }}</button>
                <a href="{{ route('admin.users.index') }}" class="a-btn ghost">Cancel</a>
            </div>
        </div>
    </form>
@endsection

@extends('admin.layouts.app')

@section('title', 'My profile')

@section('content')
    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf @method('PUT')

        <div class="a-card" style="max-width:620px;">
            <div class="a-card-head"><h2>Your details</h2></div>

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
                <span class="a-hint">You sign in to the panel with this address.</span>
                @error('email')<span class="a-error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="a-card" style="max-width:620px;">
            <div class="a-card-head"><h2>Change password</h2></div>
            <p class="a-card-sub">Leave these blank to keep your current password.</p>

            <div class="a-field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password"
                       class="a-input @error('current_password') has-error @enderror" autocomplete="current-password">
                @error('current_password')<span class="a-error">{{ $message }}</span>@enderror
            </div>

            <div class="a-row cols-2">
                <div class="a-field">
                    <label for="password">New password</label>
                    <input type="password" id="password" name="password"
                           class="a-input @error('password') has-error @enderror" autocomplete="new-password">
                    <span class="a-hint">At least 8 characters.</span>
                    @error('password')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="a-input" autocomplete="new-password">
                </div>
            </div>

            <div class="a-form-actions">
                <button type="submit" class="a-btn">Save changes</button>
            </div>
        </div>
    </form>
@endsection

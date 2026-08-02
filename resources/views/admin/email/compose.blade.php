@extends('admin.layouts.app')

@section('title', 'Send email')

@section('content')
    <form method="POST" action="{{ route('admin.email.compose.send') }}">
        @csrf

        <div class="a-grid side">
            <div class="a-card">
                <div class="a-field">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="a-input @error('subject') has-error @enderror"
                           value="{{ old('subject') }}" required>
                    @error('subject')<span class="a-error">{{ $message }}</span>@enderror
                </div>

                <div class="a-field">
                    <label for="body">Message</label>
                    <textarea id="body" name="body" class="a-textarea tall @error('body') has-error @enderror" required>{{ old('body') }}</textarea>
                    <span class="a-hint">HTML is allowed. Your branded email layout is added around this automatically.</span>
                    <div class="a-tokens">
                        <span class="a-token">&#123;&#123;name&#125;&#125;</span>
                        <span class="a-token">&#123;&#123;email&#125;&#125;</span>
                        <span class="a-token">&#123;&#123;site_name&#125;&#125;</span>
                        <span class="a-token">&#123;&#123;site_phone&#125;&#125;</span>
                        <span class="a-token">&#123;&#123;site_address&#125;&#125;</span>
                    </div>
                    @error('body')<span class="a-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div>
                <div class="a-card">
                    <h3>Recipients</h3>

                    <div class="a-field">
                        <label for="audience">Send to</label>
                        <select id="audience" name="audience" class="a-select" onchange="document.getElementById('pick-list').hidden = this.value !== 'selected'; document.getElementById('pick-custom').hidden = this.value !== 'custom';">
                            <option value="selected" {{ old('audience', 'selected') === 'selected' ? 'selected' : '' }}>Selected people</option>
                            <option value="all_customers" {{ old('audience') === 'all_customers' ? 'selected' : '' }}>All customers</option>
                            <option value="all_users" {{ old('audience') === 'all_users' ? 'selected' : '' }}>All users including admins</option>
                            <option value="custom" {{ old('audience') === 'custom' ? 'selected' : '' }}>One typed address</option>
                        </select>
                    </div>

                    <div id="pick-custom" {{ old('audience') === 'custom' ? '' : 'hidden' }}>
                        <div class="a-field">
                            <label for="custom_email">Email address</label>
                            <input type="email" id="custom_email" name="custom_email" class="a-input" value="{{ old('custom_email') }}">
                        </div>
                    </div>

                    <div id="pick-list" {{ old('audience', 'selected') === 'selected' ? '' : 'hidden' }}>
                        <span class="a-label">Choose people</span>
                        <div style="max-height:320px; overflow-y:auto; border:1px solid var(--a-line); border-radius:8px; padding:8px;">
                            @forelse ($users as $user)
                                <label class="a-check" style="margin-bottom:6px; padding:8px 10px;">
                                    <input type="checkbox" name="recipients[]" value="{{ $user->id }}"
                                           {{ in_array($user->id, old('recipients', [])) ? 'checked' : '' }}>
                                    <span>
                                        <strong style="font-size:0.84rem;">{{ $user->name }}</strong>
                                        <small>{{ $user->email }}{{ $user->is_admin ? ' · admin' : '' }}</small>
                                    </span>
                                </label>
                            @empty
                                <p class="a-muted" style="margin:8px;">No users yet.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="a-form-actions">
                        <button type="submit" class="a-btn block">Send email</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

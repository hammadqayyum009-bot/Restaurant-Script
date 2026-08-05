<div class="a-card">
    <div class="a-card-head"><h3 style="margin:0;">Moyasar credentials</h3></div>
    <p class="a-card-sub" style="margin-top:0;">{{ __('payments.credentials_write_only') }}</p>

    @php
        $hasCredential = fn (string $field) => ! empty($method->credentials[$field] ?? null);
    @endphp

    <div class="a-row cols-2">
        <div class="a-field">
            <label for="secret_key_test">{{ __('payments.secret_key_test') }}</label>
            <input type="password" id="secret_key_test" name="secret_key_test" class="a-input" autocomplete="off" placeholder="sk_test_...">
            @if ($hasCredential('secret_key_test'))
                <span class="a-hint">{{ __('payments.credentials_saved_masked') }}</span>
            @endif
        </div>
        <div class="a-field">
            <label for="secret_key_live">{{ __('payments.secret_key_live') }}</label>
            <input type="password" id="secret_key_live" name="secret_key_live" class="a-input" autocomplete="off" placeholder="sk_live_...">
            @if ($hasCredential('secret_key_live'))
                <span class="a-hint">{{ __('payments.credentials_saved_masked') }}</span>
            @endif
        </div>
    </div>

    <div class="a-row cols-2">
        <div class="a-field">
            <label for="webhook_secret_test">{{ __('payments.webhook_secret_test') }}</label>
            <input type="password" id="webhook_secret_test" name="webhook_secret_test" class="a-input" autocomplete="off">
            @if ($hasCredential('webhook_secret_test'))
                <span class="a-hint">{{ __('payments.credentials_saved_masked') }}</span>
            @endif
        </div>
        <div class="a-field">
            <label for="webhook_secret_live">{{ __('payments.webhook_secret_live') }}</label>
            <input type="password" id="webhook_secret_live" name="webhook_secret_live" class="a-input" autocomplete="off">
            @if ($hasCredential('webhook_secret_live'))
                <span class="a-hint">{{ __('payments.credentials_saved_masked') }}</span>
            @endif
        </div>
    </div>

    <div class="a-alert warn">
        Webhook signature verification is not yet implemented (Phase 2 open item) — a configured webhook secret is stored but not yet used to verify anything. Online payments are confirmed via the callback-triggered check and the "Re-verify with provider" action on the transaction screen, not via the webhook, until this is closed out.
    </div>
</div>

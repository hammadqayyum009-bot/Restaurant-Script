@extends('install.layout', ['step' => 3])

@section('content')
    <h3 style="margin-top:0;">Database &amp; Restaurant Setup</h3>

    @if ($errors->any())
        <div class="alert alert-error">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('install.database.store') }}">
        @csrf

        <div class="fieldset-title">Database Connection</div>
        <div class="form-group">
            <label for="db_connection">Database Type</label>
            <select class="form-control" id="db_connection" name="db_connection" onchange="toggleDbFields(this.value)">
                <option value="mysql" {{ old('db_connection', 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL (recommended for cPanel)</option>
                <option value="sqlite" {{ old('db_connection') === 'sqlite' ? 'selected' : '' }}>SQLite (quick local testing)</option>
            </select>
        </div>

        <div id="mysql-fields">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label for="db_host">DB Host</label>
                    <input type="text" class="form-control" id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}">
                </div>
                <div class="form-group">
                    <label for="db_port">DB Port</label>
                    <input type="text" class="form-control" id="db_port" name="db_port" value="{{ old('db_port', '3306') }}">
                </div>
            </div>
            <div class="form-group">
                <label for="db_database">Database Name</label>
                <input type="text" class="form-control" id="db_database" name="db_database" value="{{ old('db_database') }}" placeholder="cpaneluser_restaurant">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label for="db_username">DB Username</label>
                    <input type="text" class="form-control" id="db_username" name="db_username" value="{{ old('db_username') }}" placeholder="cpaneluser_dbuser">
                </div>
                <div class="form-group">
                    <label for="db_password">DB Password</label>
                    <input type="password" class="form-control" id="db_password" name="db_password" value="{{ old('db_password') }}">
                </div>
            </div>
        </div>

        <div class="fieldset-title">Restaurant Details</div>
        <div class="form-group">
            <label for="site_name">Restaurant Name</label>
            <input type="text" class="form-control" id="site_name" name="site_name" value="{{ old('site_name', config('site.name')) }}" required>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="site_phone">Phone Number</label>
                <input type="text" class="form-control" id="site_phone" name="site_phone" value="{{ old('site_phone', config('site.phone')) }}" required>
            </div>
            <div class="form-group">
                <label for="site_whatsapp">WhatsApp Number (digits only, with country code)</label>
                <input type="text" class="form-control" id="site_whatsapp" name="site_whatsapp" value="{{ old('site_whatsapp', config('site.whatsapp')) }}" required>
            </div>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="site_email">Contact Email</label>
                <input type="email" class="form-control" id="site_email" name="site_email" value="{{ old('site_email', config('site.email')) }}" required>
            </div>
            <div class="form-group">
                <label for="site_address">Address</label>
                <input type="text" class="form-control" id="site_address" name="site_address" value="{{ old('site_address', config('site.address')) }}" required>
            </div>
        </div>

        <div class="fieldset-title">Admin Account</div>
        <div class="form-group">
            <label for="admin_name">Your Name</label>
            <input type="text" class="form-control" id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
        </div>
        <div class="form-group">
            <label for="admin_email">Admin Email</label>
            <input type="email" class="form-control" id="admin_email" name="admin_email" value="{{ old('admin_email') }}" required>
        </div>
        <div class="form-row cols-2">
            <div class="form-group">
                <label for="admin_password">Admin Password</label>
                <input type="password" class="form-control" id="admin_password" name="admin_password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="admin_password_confirmation">Confirm Password</label>
                <input type="password" class="form-control" id="admin_password_confirmation" name="admin_password_confirmation" required minlength="8">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" id="install-submit">Install Website</button>
    </form>

    <script>
        function toggleDbFields(value) {
            document.getElementById('mysql-fields').style.display = value === 'mysql' ? 'block' : 'none';
        }
        toggleDbFields(document.getElementById('db_connection').value);
        document.querySelector('form').addEventListener('submit', function () {
            var btn = document.getElementById('install-submit');
            btn.disabled = true;
            btn.textContent = 'Installing... please wait';
        });
    </script>
@endsection

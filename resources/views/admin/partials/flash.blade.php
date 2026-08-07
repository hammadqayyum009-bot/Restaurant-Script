@if (session('success'))
    <div class="a-alert ok">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="a-alert err">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="a-alert err">
        <div>
            <strong>Please fix the following:</strong>
            <ul style="margin:6px 0 0; padding-left:18px; list-style:disc;">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

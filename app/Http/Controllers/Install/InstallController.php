<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Throwable;

class InstallController extends Controller
{
    protected array $requiredExtensions = [
        'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo',
    ];

    public function welcome()
    {
        if ($this->isInstalled()) {
            return view('install.already-installed');
        }

        return view('install.welcome');
    }

    public function requirements()
    {
        if ($this->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');

        $extensions = collect($this->requiredExtensions)->map(fn ($ext) => [
            'name' => $ext,
            'loaded' => extension_loaded($ext),
        ]);

        $permissions = collect([
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ])->map(fn ($path, $label) => [
            'path' => $label,
            'writable' => is_writable($path),
        ]);

        $allOk = $phpOk && $extensions->every(fn ($e) => $e['loaded']) && $permissions->every(fn ($p) => $p['writable']);

        return view('install.requirements', compact('phpOk', 'extensions', 'permissions', 'allOk'));
    }

    public function databaseForm()
    {
        if ($this->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        return view('install.database');
    }

    public function databaseStore(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        $data = $request->validate([
            'db_connection' => ['required', 'in:mysql,sqlite'],
            'db_host' => ['nullable', 'string', 'max:255'],
            'db_port' => ['nullable', 'string', 'max:10'],
            'db_database' => ['nullable', 'string', 'max:255'],
            'db_username' => ['nullable', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'site_name' => ['required', 'string', 'max:120'],
            'site_phone' => ['required', 'string', 'max:40'],
            'site_whatsapp' => ['required', 'string', 'max:20'],
            'site_email' => ['required', 'email', 'max:150'],
            'site_address' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($data['db_connection'] === 'mysql') {
            $request->validate([
                'db_host' => ['required', 'string'],
                'db_database' => ['required', 'string'],
                'db_username' => ['required', 'string'],
            ]);
        }

        try {
            $this->configureRuntimeConnection($data);
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'db_database' => 'Could not connect to the database with these details: '.$e->getMessage(),
            ]);
        }

        try {
            $this->writeEnvironmentFile($data, $request->root());

            Artisan::call('config:clear');
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            $admin = User::query()->where('email', $data['admin_email'])->first();
            if (! $admin) {
                $admin = new User();
            }
            $admin->name = $data['admin_name'];
            $admin->email = $data['admin_email'];
            $admin->password = Hash::make($data['admin_password']);
            $admin->is_admin = true;
            $admin->is_active = true;
            $admin->save();

            // The restaurant name typed into the wizard becomes the first saved
            // setting, so the site is branded before the admin opens the panel.
            app(\App\Services\Settings::class)->setMany([
                'site_name' => $data['site_name'] ?? config('site.name'),
                'mail_from_name' => $data['site_name'] ?? config('site.name'),
                'notify_admin_email' => $data['admin_email'],
            ], 'site');

            File::put(storage_path('installed.lock'), now()->toDateTimeString());

            Artisan::call('cache:clear');
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'db_database' => 'Installation failed: '.$e->getMessage(),
            ]);
        }

        return redirect()->route('install.finish');
    }

    public function finish()
    {
        if (! $this->isInstalled()) {
            return redirect()->route('install.welcome');
        }

        return view('install.finish');
    }

    protected function isInstalled(): bool
    {
        return file_exists(storage_path('installed.lock'));
    }

    protected function configureRuntimeConnection(array $data): void
    {
        if ($data['db_connection'] === 'sqlite') {
            $path = database_path('database.sqlite');
            if (! file_exists($path)) {
                File::put($path, '');
            }

            Config::set('database.default', 'sqlite');
            Config::set('database.connections.sqlite.database', $path);
        } else {
            Config::set('database.default', 'mysql');
            Config::set('database.connections.mysql.host', $data['db_host']);
            Config::set('database.connections.mysql.port', $data['db_port'] ?: 3306);
            Config::set('database.connections.mysql.database', $data['db_database']);
            Config::set('database.connections.mysql.username', $data['db_username']);
            Config::set('database.connections.mysql.password', $data['db_password'] ?? '');
        }

        DB::purge($data['db_connection']);
    }

    protected function writeEnvironmentFile(array $data, string $appUrl): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        $stub = file_exists($envPath) ? file_get_contents($envPath) : file_get_contents($examplePath);

        $appKey = 'base64:'.base64_encode(random_bytes(32));

        $replacements = [
            'APP_NAME' => '"'.$data['site_name'].'"',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'APP_KEY' => $appKey,
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $data['db_port'] ?? 3306,
            'DB_DATABASE' => $data['db_connection'] === 'sqlite' ? database_path('database.sqlite') : ($data['db_database'] ?? ''),
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'SITE_NAME' => '"'.$data['site_name'].'"',
            'SITE_PHONE' => '"'.$data['site_phone'].'"',
            'SITE_WHATSAPP' => $data['site_whatsapp'],
            'SITE_EMAIL' => $data['site_email'],
            'SITE_ADDRESS' => '"'.$data['site_address'].'"',
        ];

        foreach ($replacements as $key => $value) {
            if (preg_match("/^{$key}=.*$/m", $stub)) {
                $stub = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $stub);
            } else {
                $stub .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $stub);
    }
}

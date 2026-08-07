<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Key/value store behind every editable part of the site.
 *
 * Values are read once per request and cached in memory. Reads are safe before
 * the installer has run — the table simply does not exist yet, and callers fall
 * back to the defaults baked into config/.
 */
class Settings
{
    /** @var array<string, string|null>|null */
    protected ?array $cache = null;

    protected ?bool $tableExists = null;

    public function available(): bool
    {
        // Only the positive result is memoised. A negative result is cheap to
        // re-check and must not be cached forever: this singleton is resolved
        // once at boot, which — under a per-test migrator (RefreshDatabase) —
        // can run before the table exists yet. Caching `false` there would
        // wrongly disable every settings write for the rest of that request.
        if ($this->tableExists === true) {
            return true;
        }

        try {
            $this->tableExists = Schema::hasTable('settings');
        } catch (Throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (! $this->available()) {
            return $this->cache = [];
        }

        try {
            $this->cache = Setting::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            $this->cache = [];
        }

        return $this->cache;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        if (! $this->available()) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'group' => $group]
        );

        $this->cache = null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function forget(string $key): void
    {
        if (! $this->available()) {
            return;
        }

        Setting::query()->where('key', $key)->delete();
        $this->cache = null;
    }

    public function flush(): void
    {
        $this->cache = null;
    }
}

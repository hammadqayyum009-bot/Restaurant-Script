<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records who changed what in the admin panel.
 *
 * Logging is never allowed to break the action being logged — if the write
 * fails, the change still goes through.
 */
class ActivityLogger
{
    public const ACTIONS = ['created', 'updated', 'deleted', 'status', 'settings', 'email', 'export'];

    public function log(string $action, string $description, ?Model $subject = null): void
    {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description, 250, ''),
                'ip' => Request::ip(),
            ]);
        } catch (Throwable) {
            // Deliberately swallowed.
        }
    }

    public function created(Model $subject, string $label): void
    {
        $this->log('created', 'Created '.$label, $subject);
    }

    public function updated(Model $subject, string $label): void
    {
        $this->log('updated', 'Updated '.$label, $subject);
    }

    public function deleted(string $label, ?Model $subject = null): void
    {
        $this->log('deleted', 'Deleted '.$label, $subject);
    }

    public function settings(string $area): void
    {
        $this->log('settings', 'Saved '.$area.' settings');
    }
}

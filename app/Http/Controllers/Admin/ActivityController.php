<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->query('user')) {
            $query->where('user_id', $userId);
        }

        if ($search = trim((string) $request->query('q'))) {
            $query->where('description', 'like', "%{$search}%");
        }

        return view('admin.activity.index', [
            'logs' => $query->latest()->paginate(40)->withQueryString(),
            'actions' => ActivityLogger::ACTIONS,
            'admins' => User::where('is_admin', true)->orderBy('name')->get(['id', 'name']),
            'activeAction' => $action,
            'activeUser' => $userId,
            'search' => $search,
        ]);
    }
}

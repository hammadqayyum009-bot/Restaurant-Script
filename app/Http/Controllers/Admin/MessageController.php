<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->query('filter') === 'unread') {
            $query->where('is_read', false);
        }

        return view('admin.messages.index', [
            'messages' => $query->latest()->paginate(20)->withQueryString(),
            'filter' => $request->query('filter'),
            'unreadCount' => ContactMessage::where('is_read', false)->count(),
        ]);
    }

    public function toggleRead(ContactMessage $message)
    {
        $message->update(['is_read' => ! $message->is_read]);

        return back()->with('success', $message->is_read ? 'Marked as read.' : 'Marked as unread.');
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}

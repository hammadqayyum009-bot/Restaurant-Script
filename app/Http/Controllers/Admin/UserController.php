<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public function index(Request $request)
    {
        $query = User::query()->withCount('orders');

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->query('role') === 'admin') {
            $query->where('is_admin', true);
        } elseif ($request->query('role') === 'customer') {
            $query->where('is_admin', false);
        }

        return view('admin.users.index', [
            'users' => $query->latest()->paginate(20)->withQueryString(),
            'search' => $search,
            'role' => $request->query('role'),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(Request $request, Mailer $mailer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $data['is_admin'] = $request->boolean('is_admin');
        $data['is_active'] = $request->boolean('is_active');

        $user = User::create($data);
        $this->activity->created($user, ($user->is_admin ? 'admin' : 'customer').' "'.$user->name.'"');

        if ($request->boolean('send_welcome')) {
            $mailer->dispatchTemplate('welcome', $user->email, $user->name, [
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $isSelf = $user->id === Auth::id();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'current_password' => $isSelf ? ['nullable', 'required_with:password', 'string'] : ['nullable'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required_with' => 'Enter your current password to set a new one.',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } elseif ($isSelf) {
            // Only self-edit is gated this way — an admin resetting a
            // different (locked-out, forgotten-password) user's password
            // legitimately has no way to know that other user's current
            // password, and isn't the one whose session could be riding on
            // this request. See ProfileController::update() for the
            // equivalent check on the route this mirrors.
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                return back()->withInput()->withErrors(['current_password' => 'That is not your current password.']);
            }
        }
        unset($data['current_password']);

        // Never let an admin lock themselves out of the panel they are using.
        if ($user->id === Auth::id()) {
            $data['is_admin'] = true;
            $data['is_active'] = true;
        } else {
            $data['is_admin'] = $request->boolean('is_admin');
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);
        $this->activity->updated($user, 'user "'.$user->name.'"');

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete the account you are signed in with.');
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'That is the last admin account — create another one first.');
        }

        $this->activity->deleted('user "'.$user->name.'"', $user);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}

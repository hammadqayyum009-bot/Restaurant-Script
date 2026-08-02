@extends('admin.layouts.app')

@section('title', 'Users')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="a-btn sm">Add user</a>
@endsection

@section('content')
    <div class="a-card">
        <form method="GET" class="a-filters">
            <input type="search" name="q" value="{{ $search }}" class="a-input" placeholder="Name, email or phone…">
            <select name="role" class="a-select">
                <option value="">Everyone</option>
                <option value="customer" {{ $role === 'customer' ? 'selected' : '' }}>Customers</option>
                <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Admins</option>
            </select>
            <button type="submit" class="a-btn ghost sm">Filter</button>
            @if ($search || $role)
                <a href="{{ route('admin.users.index') }}" class="a-btn ghost sm">Clear</a>
            @endif
        </form>

        @if ($users->isEmpty())
            <div class="a-empty"><strong>No users found</strong>Customers appear here when they create an account.</div>
        @else
            <div class="a-table-wrap">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th class="num">Orders</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    <div class="a-muted" style="font-size:0.78rem;">
                                        Joined {{ $user->created_at->format('d M Y') }}
                                        @if ($user->last_login_at) &middot; last seen {{ $user->last_login_at->diffForHumans() }} @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $user->email }}
                                    <div class="a-muted" style="font-size:0.78rem;">{{ $user->phone ?: '—' }}</div>
                                </td>
                                <td>
                                    <span class="a-badge {{ $user->is_admin ? 'info' : '' }}">{{ $user->is_admin ? 'Admin' : 'Customer' }}</span>
                                </td>
                                <td class="num">{{ $user->orders_count }}</td>
                                <td>
                                    <span class="a-badge {{ $user->is_active ? 'ok' : 'danger' }}">{{ $user->is_active ? 'Active' : 'Suspended' }}</span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="a-btn ghost sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="a-inline-form"
                                              data-confirm="Delete {{ $user->name }}?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="a-btn danger sm" {{ $user->id === auth()->id() ? 'disabled' : '' }}>Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="a-pagination">{{ $users->links() }}</div>
        @endif
    </div>
@endsection

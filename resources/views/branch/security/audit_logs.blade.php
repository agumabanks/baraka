@extends('branch.layout')

@section('title', 'Audit Logs')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4 text-yellow-400">Audit Logs</h1>
    <div class="bg-gray-800 rounded-lg shadow-lg p-4">
        <form method="GET" action="{{ route('branch.account.security.audit-logs') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <input type="text" name="user" placeholder="User" value="{{ request('user') }}" class="bg-gray-700 text-white rounded px-2 py-1" />
            <select name="action" class="bg-gray-700 text-white rounded px-2 py-1">
                <option value="">All Actions</option>
                <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Login</option>
                <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Logout</option>
                <option value="password_changed" {{ request('action') == 'password_changed' ? 'selected' : '' }}>Password Changed</option>
                <option value="account_locked" {{ request('action') == 'account_locked' ? 'selected' : '' }}>Account Locked</option>
            </select>
            <input type="date" name="date" value="{{ request('date') }}" class="bg-gray-700 text-white rounded px-2 py-1" />
            <button type="submit" class="col-span-1 md:col-span-3 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded">
                Filter
            </button>
        </form>
        <table class="w-full table-auto text-white">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-2">User</th>
                    <th class="px-4 py-2">Action</th>
                    <th class="px-4 py-2">IP</th>
                    <th class="px-4 py-2">User Agent</th>
                    <th class="px-4 py-2">Details</th>
                    <th class="px-4 py-2">When</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-b border-gray-600">
                    <td class="px-4 py-2">{{ $log->user->name ?? 'System' }}</td>
                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $log->action) }}</td>
                    <td class="px-4 py-2">{{ $log->ip_address }}</td>
                    <td class="px-4 py-2 truncate max-w-xs" title="{{ $log->user_agent }}">{{ $log->user_agent }}</td>
                    <td class="px-4 py-2">{{ $log->metadata }}</td>
                    <td class="px-4 py-2">{{ $log->performed_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4">No audit records found.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $logs->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection

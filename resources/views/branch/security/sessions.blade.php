@extends('branch.layout')

@section('title', 'Active Sessions')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4 text-yellow-400">Active Sessions</h1>
    <div class="bg-gray-800 rounded-lg shadow-lg p-4">
        <table class="w-full table-auto text-white">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-2">Device</th>
                    <th class="px-4 py-2">IP Address</th>
                    <th class="px-4 py-2">Location</th>
                    <th class="px-4 py-2">Logged In At</th>
                    <th class="px-4 py-2">Last Activity</th>
                    <th class="px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                <tr class="border-b border-gray-600">
                    <td class="px-4 py-2">{{ $session->device_name }} ({{ $session->device_type }})</td>
                    <td class="px-4 py-2">{{ $session->ip_address }}</td>
                    <td class="px-4 py-2">{{ $session->location ?? 'Unknown' }}</td>
                    <td class="px-4 py-2">{{ $session->logged_in_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-2">{{ $session->last_activity_at->format('Y-m-d H:i') }}</td>
                    <td class="px-4 py-2">
                        @if(!$session->isCurrent())
                        <form method="POST" action="{{ route('branch.account.security.session.revoke', $session->session_id) }}" onsubmit="return confirm('Revoke this session?');">
                            @csrf
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-1 px-2 rounded">
                                Revoke
                            </button>
                        </form>
                        @else
                        <span class="text-gray-400">Current</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4">No active sessions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@extends('admin.layout')

@section('title', 'Security Center')
@section('header', 'Security Center')

@section('content')
<div id="security-dashboard">
    {{-- Security Overview Cards --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">Login Attempts (24h)</div>
                    <div class="text-2xl font-bold text-emerald-400" id="stat-login-success">--</div>
                    <div class="text-rose-400 text-xs" id="stat-login-failed">-- failed</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">Active Sessions</div>
                    <div class="text-2xl font-bold" id="stat-active-sessions">--</div>
                    <div class="text-sky-400 text-xs">Currently online</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">API Requests (24h)</div>
                    <div class="text-2xl font-bold" id="stat-api-requests">--</div>
                    <div class="text-purple-400 text-xs">External API calls</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <div class="muted text-xs uppercase">Locked Accounts</div>
                    <div class="text-2xl font-bold text-rose-400" id="stat-locked-accounts">--</div>
                    <div class="text-amber-400 text-xs">Requires attention</div>
                </div>
                <div class="w-12 h-12 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid gap-6 lg:grid-cols-3 mb-6">
        {{-- Recent Security Events --}}
        <div class="lg:col-span-2 glass-panel p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Recent Security Events</h3>
                <a href="{{ route('admin.security.audit-logs') }}" class="btn btn-sm btn-secondary">View All</a>
            </div>
            <div class="space-y-3" id="security-events">
                <div class="text-center py-8 muted">Loading...</div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="glass-panel p-5">
            <h3 class="text-lg font-semibold mb-4">Security Actions</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.security.sessions') }}" class="flex items-center gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition">
                    <div class="w-10 h-10 rounded-lg bg-sky-500/20 text-sky-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">Active Sessions</div>
                        <div class="text-xs muted">Monitor & terminate</div>
                    </div>
                </a>
                <a href="{{ route('admin.security.audit-logs') }}" class="flex items-center gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition">
                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">Audit Logs</div>
                        <div class="text-xs muted">Activity history</div>
                    </div>
                </a>
                <a href="{{ route('admin.security.gdpr.retention') }}" class="flex items-center gap-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <div>
                        <div class="font-medium">Data Retention</div>
                        <div class="text-xs muted">GDPR compliance</div>
                    </div>
                </a>
            </div>

            <hr class="my-4 border-white/10">

            <h4 class="text-sm font-semibold mb-3 muted">System Health</h4>
            <div class="space-y-2" id="health-checks">
                <div class="flex items-center justify-between p-2 rounded bg-white/5">
                    <span class="text-sm">Database</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <div class="flex items-center justify-between p-2 rounded bg-white/5">
                    <span class="text-sm">Cache</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <div class="flex items-center justify-between p-2 rounded bg-white/5">
                    <span class="text-sm">Queue</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Active Sessions Table --}}
    <div class="glass-panel p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Active Sessions</h3>
            <span class="text-xs muted">Last 30 minutes</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-white/10">
                        <th class="text-left py-3 px-4">User</th>
                        <th class="text-left py-3 px-4">IP Address</th>
                        <th class="text-left py-3 px-4">Device</th>
                        <th class="text-left py-3 px-4">Last Activity</th>
                        <th class="text-center py-3 px-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="sessions-table">
                    <tr><td colspan="5" class="text-center py-8 muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSecurityOverview();
    loadActiveSessions();
    setInterval(loadSecurityOverview, 30000); // Refresh every 30s
});

function loadSecurityOverview() {
    fetch('{{ route("admin.security.overview") }}')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateOverview(data.data);
            }
        })
        .catch(err => console.error('Error:', err));
}

function loadActiveSessions() {
    fetch('{{ route("admin.security.sessions") }}', {
        headers: { 'Accept': 'application/json' }
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateSessionsTable(data.data);
            }
        });
}

function updateOverview(data) {
    document.getElementById('stat-login-success').textContent = data.login_attempts.successful;
    document.getElementById('stat-login-failed').textContent = `${data.login_attempts.failed} failed`;
    document.getElementById('stat-active-sessions').textContent = data.active_sessions;
    document.getElementById('stat-api-requests').textContent = data.api_requests.toLocaleString();
    document.getElementById('stat-locked-accounts').textContent = data.locked_accounts;

    // Update security events
    const eventsHtml = data.recent_security_events.slice(0, 8).map(event => `
        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5">
            <div class="w-8 h-8 rounded-full ${getEventColor(event.action)} flex items-center justify-center">
                ${getEventIcon(event.action)}
            </div>
            <div class="flex-1 min-w-0">
                <div class="font-medium truncate">${formatAction(event.action)}</div>
                <div class="text-xs muted">${event.ip_address || 'N/A'}</div>
            </div>
            <div class="text-xs muted">${formatTime(event.created_at)}</div>
        </div>
    `).join('');

    document.getElementById('security-events').innerHTML = eventsHtml || '<div class="text-center py-8 muted">No recent events</div>';
}

function updateSessionsTable(sessions) {
    const tbody = document.getElementById('sessions-table');
    
    if (!sessions || sessions.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 muted">No active sessions</td></tr>';
        return;
    }

    tbody.innerHTML = sessions.slice(0, 10).map(s => `
        <tr class="border-b border-white/5 hover:bg-white/5">
            <td class="py-3 px-4">
                <div class="font-medium">${s.name}</div>
                <div class="text-xs muted">${s.email}</div>
            </td>
            <td class="py-3 px-4 font-mono text-xs">${s.ip_address}</td>
            <td class="py-3 px-4 text-xs truncate max-w-xs">${parseUserAgent(s.user_agent)}</td>
            <td class="py-3 px-4 text-xs">${formatTime(s.last_activity_at)}</td>
            <td class="py-3 px-4 text-center">
                <button onclick="terminateSession(${s.id})" class="btn btn-xs btn-danger">Terminate</button>
            </td>
        </tr>
    `).join('');
}

function terminateSession(sessionId) {
    if (!confirm('Are you sure you want to terminate this session?')) return;

    fetch(`{{ url('admin/security/sessions') }}/${sessionId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadActiveSessions();
        }
    });
}

function getEventColor(action) {
    if (action.includes('failed') || action.includes('locked')) return 'bg-rose-500/20 text-rose-400';
    if (action.includes('success') || action.includes('login')) return 'bg-emerald-500/20 text-emerald-400';
    if (action.includes('password') || action.includes('changed')) return 'bg-amber-500/20 text-amber-400';
    return 'bg-slate-500/20 text-slate-400';
}

function getEventIcon(action) {
    if (action.includes('failed') || action.includes('locked')) return '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
    if (action.includes('login')) return '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>';
    return '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
}

function formatAction(action) {
    return action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatTime(timestamp) {
    if (!timestamp) return 'N/A';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return date.toLocaleDateString();
}

function parseUserAgent(ua) {
    if (!ua) return 'Unknown';
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    return ua.substring(0, 30) + '...';
}
</script>
@endpush
@endsection

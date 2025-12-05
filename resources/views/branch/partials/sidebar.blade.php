<aside class="glass-panel w-72 p-5 flex flex-col gap-6 transition-transform fixed inset-y-0 left-0 -translate-x-full lg:translate-x-0 lg:relative lg:inset-auto lg:transform-none lg:h-full lg:flex-shrink-0 z-40" data-sidebar>
    {{-- Branch Header --}}
    <div class="flex items-center justify-between flex-shrink-0">
        <div>
            <div class="text-xs uppercase muted">Branch</div>
            <div class="text-lg font-semibold">{{ $branch->code ?? 'BR' }}</div>
            <div class="muted text-sm">{{ $branch->name ?? '' }}</div>
        </div>
        <button class="lg:hidden text-white" type="button" data-sidebar-toggle>&times;</button>
    </div>

    {{-- Navigation Menu --}}
    <nav class="flex-1 space-y-1 text-sm overflow-y-auto">
        <a href="{{ route('branch.dashboard') }}" class="nav-link {{ request()->routeIs('branch.dashboard') ? 'nav-link-active' : '' }}">Overview</a>
        
        <div class="text-xs uppercase muted mt-4 mb-2">Shipments</div>
        <a href="{{ route('branch.pos.index') }}" class="nav-link {{ request()->routeIs('branch.pos.*') ? 'nav-link-active' : '' }}">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                Shipments POS
                <span class="text-2xs px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400">NEW</span>
            </span>
        </a>
        <a href="{{ route('branch.shipments.index') }}" class="nav-link {{ request()->routeIs('branch.shipments.*') ? 'nav-link-active' : '' }}">Shipments Management</a>
        
        <div class="text-xs uppercase muted mt-4 mb-2">Operations</div>
        <a href="{{ route('branch.operations') }}" class="nav-link {{ request()->routeIs('branch.operations*') ? 'nav-link-active' : '' }}">Operations Board</a>
        <a href="{{ route('branch.workforce') }}" class="nav-link {{ request()->routeIs('branch.workforce*') ? 'nav-link-active' : '' }}">Workforce</a>
        <a href="{{ route('branch.clients') }}" class="nav-link {{ request()->routeIs('branch.clients*') ? 'nav-link-active' : '' }}">Clients & CRM</a>
        <a href="{{ route('branch.finance') }}" class="nav-link {{ request()->routeIs('branch.finance*') && !request()->routeIs('branch.settlements*') ? 'nav-link-active' : '' }}">Finance</a>
        <a href="{{ route('branch.settlements.dashboard') }}" class="nav-link {{ request()->routeIs('branch.settlements*') ? 'nav-link-active' : '' }}">P&L / Settlements</a>
        <a href="{{ route('branch.warehouse') }}" class="nav-link {{ request()->routeIs('branch.warehouse*') ? 'nav-link-active' : '' }}">Warehouse</a>
        <a href="{{ route('branch.fleet') }}" class="nav-link {{ request()->routeIs('branch.fleet*') ? 'nav-link-active' : '' }}">Fleet</a>
        
        <div class="text-xs uppercase muted mt-4 mb-2">Security</div>
        <a href="{{ route('branch.account.security.sessions') }}" class="nav-link {{ request()->routeIs('branch.account.security.sessions') ? 'nav-link-active' : '' }}">Sessions</a>
        <a href="{{ route('branch.account.security.audit-logs') }}" class="nav-link {{ request()->routeIs('branch.account.security.audit-logs') ? 'nav-link-active' : '' }}">Audit Logs</a>
        
        <div class="text-xs uppercase muted mt-4 mb-2">Settings</div>
        <a href="{{ route('branch.settings') }}" class="nav-link {{ request()->routeIs('branch.settings') ? 'nav-link-active' : '' }}">Branch Settings</a>
    </nav>

    {{-- User Dropdown --}}
    <div class="relative flex-shrink-0" data-user-dropdown>
        <button type="button" class="glass-panel p-3 text-sm w-full hover:bg-white/5 transition-colors cursor-pointer" data-user-toggle>
            <div class="flex items-center gap-3">
                <div class="rounded-full bg-white/10 px-3 py-2 text-sm font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-sm">{{ auth()->user()->name ?? 'User' }}</div>
                    <div class="muted text-2xs">{{ auth()->user()->email ?? '' }}</div>
                </div>
                <svg class="w-4 h-4 transition-transform" data-user-icon xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </button>
        <div class="absolute bottom-full left-0 right-0 mb-2 glass-panel border border-white/10 rounded-lg hidden shadow-lg transition-all duration-200 z-50 p-2" data-user-menu>
            <div class="flex flex-col text-slate-100 gap-1 text-sm">
                <div class="px-3 py-1 text-2xs uppercase tracking-wide muted">Account</div>
                <a href="{{ route('branch.account.profile') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.profile') ? 'bg-white/10' : '' }}">Profile</a>
                <a href="{{ route('branch.account.security') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.security') ? 'bg-white/10' : '' }}">Security Settings</a>
                <a href="{{ route('branch.account.security.2fa') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.security.2fa') ? 'bg-white/10' : '' }}">Two-Factor Auth</a>
                <a href="{{ route('branch.account.devices') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.devices') ? 'bg-white/10' : '' }}">Devices & Sessions</a>
                <a href="{{ route('branch.account.notifications') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.notifications') ? 'bg-white/10' : '' }}">Notifications</a>
                <a href="{{ route('branch.account.preferences') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.preferences') ? 'bg-white/10' : '' }}">Preferences</a>

                <div class="px-3 pt-2 pb-1 text-2xs uppercase tracking-wide muted border-t border-white/10">Support</div>
                <a href="{{ route('branch.account.support') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.support') ? 'bg-white/10' : '' }}">Help & status</a>
                <a href="{{ route('branch.account.billing') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('branch.account.billing') ? 'bg-white/10' : '' }}">Billing</a>

                @if(auth()->user()?->hasRole(['admin', 'super-admin', 'operations_admin']))
                    <div class="px-3 pt-2 pb-1 text-2xs uppercase tracking-wide muted border-t border-white/10">Admin</div>
                    @if(Route::has('users.index'))
                        <a href="{{ route('users.index') }}" class="px-4 py-2 rounded-md hover:bg-white/5 transition-colors {{ request()->routeIs('users.*') ? 'bg-white/10' : '' }}">User management</a>
                    @endif
                @endif

                <form method="POST" action="{{ route('branch.logout') }}" class="border-t border-white/10 pt-2 mt-1">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 rounded-md hover:bg-white/5 transition-colors">Logout</button>
                </form>
            </div>
        </div>
    </div>
</aside>

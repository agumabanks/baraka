@extends('branch.layout')

@section('title', 'Workforce')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 space-y-3 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Team roster</div>
                    <p class="muted text-xs">Onboard, assign roles, manage status.</p>
                </div>
                <span class="pill-soft">{{ $workers->total() }} active</span>
            </div>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Worker</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($workers as $worker)
                            <tr>
                                <td>
                                    <div class="font-semibold text-sm">{{ $worker->user?->name }}</div>
                                    <div class="muted text-2xs">{{ $worker->user?->email }}</div>
                                </td>
                                <td class="muted text-xs">{{ $worker->role?->label() ?? $worker->role?->value }}</td>
                                <td>
                                    <span class="chip text-2xs">{{ $worker->employment_status?->label() ?? $worker->employment_status }}</span>
                                </td>
                                <td class="space-y-2">
                                    <form method="POST" action="{{ route('branch.workforce.update', $worker) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 text-xs">
                                            @foreach($roleOptions as $role)
                                                <option value="{{ $role['value'] }}" @selected($role['value'] === ($worker->role?->value ?? $worker->role))>{{ $role['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <select name="employment_status" class="bg-obsidian-700 border border-white/10 rounded px-2 py-1 text-xs">
                                            @foreach($statusOptions as $status)
                                                <option value="{{ $status['value'] }}" @selected($status['value'] === ($worker->employment_status?->value ?? $worker->employment_status))>{{ $status['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <button class="chip text-2xs" type="submit">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('branch.workforce.archive', $worker) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="chip text-2xs bg-rose-500/20 border border-rose-500/30 text-rose-100" type="submit">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 muted">No workers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $workers->links() }}
            </div>
        </div>

        <div class="glass-panel p-5 space-y-3">
            <div class="text-sm font-semibold">Onboard staff</div>
            <form method="POST" action="{{ route('branch.workforce.store') }}" class="space-y-2 text-sm">
                @csrf
                <input type="text" name="name" placeholder="Full name" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <input type="email" name="email" placeholder="Email" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <input type="text" name="mobile" placeholder="Mobile (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <select name="role" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    @foreach($roleOptions as $role)
                        <option value="{{ $role['value'] }}">{{ $role['label'] }}</option>
                    @endforeach
                </select>
                <select name="employment_status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    @foreach($statusOptions as $status)
                        <option value="{{ $status['value'] }}">{{ $status['label'] }}</option>
                    @endforeach
                </select>
                <textarea name="notes" placeholder="Notes" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2"></textarea>
                <button type="submit" class="chip w-full justify-center">Create assignment</button>
            </form>
            <div class="glass-panel px-3 py-2 border border-amber-500/30 text-amber-100 text-xs">
                Onboarding auto-creates the user if no existing account is provided.
            </div>
        </div>
    </div>
@endsection

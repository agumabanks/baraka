@extends('branch.layout')

@section('title', 'Edit Worker')

@section('content')
<div class="mb-4">
    <a href="{{ route('branch.workforce.show', $worker) }}" class="chip text-sm">&larr; Back to Worker</a>
</div>

<div class="max-w-2xl">
    <div class="glass-panel p-6">
        <div class="flex items-center gap-3 mb-6">
            @php
                $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                $colorIndex = crc32($worker->user?->email ?? '') % count($colors);
            @endphp
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-lg font-bold text-white">
                {{ strtoupper(substr($worker->user?->name ?? 'U', 0, 2)) }}
            </div>
            <div>
                <div class="text-lg font-semibold">Edit Worker Details</div>
                <div class="text-sm muted">{{ $worker->user?->email }}</div>
            </div>
        </div>

        @if($errors->any())
            <div class="bg-rose-500/20 border border-rose-500/30 rounded-lg p-4 mb-6">
                <div class="font-medium text-rose-400 mb-2">Please fix the following errors:</div>
                <ul class="list-disc list-inside text-sm text-rose-300">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('branch.workforce.update', $worker) }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="border-b border-white/10 pb-6">
                <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Personal Information</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name', $worker->user?->name) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $worker->user?->email) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $worker->user?->mobile) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" name="id_number" value="{{ old('id_number', $worker->id_number) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <div class="border-b border-white/10 pb-6">
                <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Employment Details</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Role <span class="text-rose-400">*</span></label>
                        <select name="role" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @foreach($roleOptions as $role)
                                <option value="{{ $role['value'] }}" @selected(($worker->role?->value ?? $worker->role) === $role['value'])>{{ $role['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Employment Status</label>
                        <select name="employment_status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            @foreach($statusOptions as $status)
                                <option value="{{ $status['value'] }}" @selected(($worker->employment_status?->value ?? $worker->employment_status) === $status['value'])>{{ $status['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="1" @selected($worker->status === \App\Enums\Status::ACTIVE)>Active</option>
                            <option value="0" @selected($worker->status === \App\Enums\Status::INACTIVE)>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Hourly Rate</label>
                        <input type="number" name="hourly_rate" step="0.01" min="0" value="{{ old('hourly_rate', $worker->hourly_rate) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Notes</label>
                <textarea name="notes" rows="3"
                    class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">{{ old('notes', $worker->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Save Changes
                </button>
                <a href="{{ route('branch.workforce.show', $worker) }}" class="px-6 py-2.5 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <div class="glass-panel p-6 mt-6 border-rose-500/30">
        <div class="text-lg font-semibold text-rose-400 mb-4">Danger Zone</div>
        <p class="text-sm text-zinc-400 mb-4">Archiving a worker will deactivate them and reassign their active shipments.</p>
        <form method="POST" action="{{ route('branch.workforce.archive', $worker) }}" onsubmit="return confirm('Are you sure you want to archive this worker? Their active shipments will be reassigned.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Archive Worker
            </button>
        </form>
    </div>
</div>
@endsection

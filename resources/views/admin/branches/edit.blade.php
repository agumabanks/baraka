@extends('admin.layout')

@section('title', 'Edit Branch')
@section('header', 'Edit Branch')

@section('content')
    <form method="POST" action="{{ route('admin.branches.update', $branch) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="glass-panel p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Branch Name</label>
                    <input type="text" name="name" value="{{ old('name', $branch->name) }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Code</label>
                    <input type="text" name="code" value="{{ old('code', $branch->code) }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2" required>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Type</label>
                    <select name="type" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <option value="branch" @selected(old('type', $branch->type) === 'branch')>Branch</option>
                        <option value="hub" @selected(old('type', $branch->type) === 'hub')>Hub</option>
                        <option value="regional" @selected(old('type', $branch->type) === 'regional')>Regional</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Parent Hub (optional)</label>
                    <select name="parent_branch_id" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <option value="">None</option>
                        @foreach($branches as $hub)
                            <option value="{{ $hub->id }}" @selected(old('parent_branch_id', $branch->parent_branch_id) == $hub->id)>{{ $hub->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $branch->email) }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $branch->phone) }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-200 mb-1">Address</label>
                <textarea name="address" rows="3" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">{{ old('address', $branch->address) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" id="is_hub" name="is_hub" value="1" class="rounded border-white/20 bg-obsidian-800" @checked(old('is_hub', $branch->is_hub))>
                <label for="is_hub" class="text-sm text-slate-200">Mark as Hub</label>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Branch</button>
        </div>
    </form>
@endsection

@extends('admin.layout')

@section('title', 'Add User')
@section('header', 'Create New User')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
        @csrf

        <div class="glass-panel p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2" required>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Mobile (optional)</label>
                    <input type="text" name="mobile" value="{{ old('mobile') }}" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Role</label>
                    <select name="role_id" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <option value="">No Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Primary Branch</label>
                    <select name="primary_branch_id" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2">
                        <option value="">Unassigned</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(old('primary_branch_id') == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-200 mb-1">Password</label>
                    <input type="password" name="password" class="w-full bg-obsidian-800 border border-white/10 rounded px-3 py-2" placeholder="Leave blank to auto-generate">
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create User</button>
        </div>
    </form>
@endsection

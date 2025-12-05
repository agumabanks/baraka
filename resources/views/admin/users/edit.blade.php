@extends('admin.layout')

@section('title', 'Edit User')
@section('header', 'Edit User')

@section('content')
<div class="max-w-2xl">
    <div class="glass-panel">
        <div class="p-4 border-b border-white/10">
            <h2 class="text-lg font-semibold">Edit User: {{ $user->name }}</h2>
            <p class="text-sm text-zinc-400 mt-1">Update user information and permissions</p>
        </div>

        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">Email Address <span class="text-red-400">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">Mobile Number</label>
                    <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}"
                           class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500 @error('mobile') border-red-500 @enderror">
                    @error('mobile')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-2">Role</label>
                    <select name="role_id" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                        <option value="">No Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-300 mb-2">Primary Branch</label>
                    <select name="primary_branch_id" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-white focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                        <option value="">No Branch (System User)</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('primary_branch_id', $user->primary_branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('primary_branch_id')
                        <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="glass-panel p-4 bg-white/5">
                <h3 class="text-sm font-medium text-zinc-300 mb-3">Account Status</h3>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-400">Status:</span>
                        @if($user->status)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-500/20 text-emerald-400">Active</span>
                        @else
                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-500/20 text-red-400">Inactive</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-400">Last Login:</span>
                        <span class="text-sm text-white">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-white/10">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <div class="flex items-center gap-3">
                    @if(auth()->id() !== $user->id)
                        <form action="{{ route('admin.users.reset-password', $user) }}" method="POST" class="inline" onsubmit="return confirm('Reset password? A new random password will be generated.')">
                            @csrf
                            <button type="submit" class="btn bg-sky-600 hover:bg-sky-500 text-white">
                                Reset Password
                            </button>
                        </form>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('branch.layout')

@section('title', 'Account – Profile')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Profile</h1>
            <p class="muted text-sm">Update your personal details and profile picture.</p>
        </div>

        <form method="POST" action="{{ route('branch.account.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Profile Image Section --}}
            <div class="glass-panel p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">Profile Picture</div>
                        <div class="muted text-xs">Your photo appears on your profile and in notifications.</div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <div class="relative">
                        <img id="profile-preview" 
                             src="{{ auth()->user()->image ?? asset('images/default/user.png') }}" 
                             alt="Profile" 
                             class="w-24 h-24 rounded-full border-2 border-white/20 object-cover">
                        <label for="profile-image" 
                               class="absolute bottom-0 right-0 bg-emerald-500 hover:bg-emerald-600 rounded-full p-2 cursor-pointer transition-colors">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </label>
                        <input type="file" id="profile-image" name="image" accept="image/*" class="hidden" data-profile-upload>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">{{ auth()->user()->name }}</div>
                        <div class="muted text-xs">{{ auth()->user()->email }}</div>
                        <div class="mt-2 text-2xs muted">JPG, PNG or GIF. Max 2MB.</div>
                    </div>
                </div>
            </div>

            {{-- Personal Information Section --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-4">Personal Information</div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Full Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">Full Name <span class="text-rose-400">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', auth()->user()->name) }}"
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('name') border-rose-500/50 @enderror"
                               required>
                        @error('name')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">Email Address <span class="text-rose-400">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', auth()->user()->email) }}"
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('email') border-rose-500/50 @enderror"
                               required>
                        @error('email')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Mobile --}}
                    <div>
                        <label for="mobile" class="block text-sm font-medium mb-2">Mobile Number</label>
                        <input type="tel" 
                               id="mobile" 
                               name="mobile" 
                               value="{{ old('mobile', auth()->user()->mobile) }}"
                               class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('mobile') border-rose-500/50 @enderror"
                               placeholder="+1 (555) 000-0000">
                        @error('mobile')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Role (Read-only) --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Role</label>
                        <div class="w-full bg-obsidian-800/50 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-slate-400">
                            {{ auth()->user()->role?->name ?? 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <label for="address" class="block text-sm font-medium mb-2">Address</label>
                    <textarea id="address" 
                              name="address" 
                              rows="3"
                              class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500/50 transition-all @error('address') border-rose-500/50 @enderror"
                              placeholder="Enter your address">{{ old('address', auth()->user()->address) }}</textarea>
                    @error('address')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Employment Information Section --}}
            <div class="glass-panel p-6 space-y-5">
                <div>
                    <div class="text-sm font-semibold mb-4">Employment Information</div>
                    <p class="muted text-xs">These details are managed by your administrator.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Designation --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Designation</label>
                        <div class="w-full bg-obsidian-800/50 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-slate-400">
                            {{ auth()->user()->designation?->title ?? 'N/A' }}
                        </div>
                    </div>

                    {{-- Department --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Department</label>
                        <div class="w-full bg-obsidian-800/50 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-slate-400">
                            {{ auth()->user()->department?->title ?? 'N/A' }}
                        </div>
                    </div>

                    {{-- Primary Branch --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Primary Branch</label>
                        <div class="w-full bg-obsidian-800/50 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-slate-400">
                            {{ auth()->user()->primaryBranch?->name ?? 'N/A' }}
                        </div>
                    </div>

                    {{-- Joining Date --}}
                    <div>
                        <label class="block text-sm font-medium mb-2">Joining Date</label>
                        <div class="w-full bg-obsidian-800/50 border border-white/5 rounded-lg px-4 py-2.5 text-sm text-slate-400">
                            {{ auth()->user()->joining_date ? \Carbon\Carbon::parse(auth()->user()->joining_date)->format('M d, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('branch.dashboard') }}" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
@endsection

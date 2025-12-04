@extends('branch.layout')

@section('title', 'Edit Client')
@section('header', 'Edit Client')

@section('content')
    <div class="mb-4">
        <a href="{{ route('branch.clients.show', $client) }}" class="chip text-sm">&larr; Back to Client</a>
    </div>

    <div class="max-w-2xl">
        <div class="glass-panel p-6">
            <div class="flex items-center gap-3 mb-6">
                @php
                    $colors = ['from-emerald-500 to-teal-600', 'from-blue-500 to-indigo-600', 'from-purple-500 to-pink-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-red-600'];
                    $colorIndex = crc32($client->customer_code ?? '') % count($colors);
                @endphp
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $colors[$colorIndex] }} flex items-center justify-center text-sm font-bold text-white">
                    {{ strtoupper(substr($client->company_name ?: $client->contact_person, 0, 2)) }}
                </div>
                <div>
                    <div class="text-lg font-semibold">Edit Client Information</div>
                    <div class="text-sm muted">{{ $client->customer_code }}</div>
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

            <form method="POST" action="{{ route('branch.clients.update', $client) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Company Name <span class="text-rose-400">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}" required
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                        @error('company_name')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $client->email) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $client->phone) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Status <span class="text-rose-400">*</span></label>
                        <select name="status" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive</option>
                            <option value="suspended" @selected(old('status', $client->status) === 'suspended')>Suspended</option>
                            <option value="blacklisted" @selected(old('status', $client->status) === 'blacklisted')>Blacklisted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Customer Type <span class="text-rose-400">*</span></label>
                        <select name="customer_type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <option value="regular" @selected(old('customer_type', $client->customer_type) === 'regular')>Regular</option>
                            <option value="vip" @selected(old('customer_type', $client->customer_type) === 'vip')>VIP</option>
                            <option value="prospect" @selected(old('customer_type', $client->customer_type) === 'prospect')>Prospect</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Credit Limit</label>
                        <input type="number" name="credit_limit" step="0.01" value="{{ old('credit_limit', $client->credit_limit) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ old('payment_terms', $client->payment_terms) }}"
                            placeholder="e.g., NET30, COD"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">{{ old('notes', $client->notes) }}</textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Changes
                    </button>
                    <a href="{{ route('branch.clients.show', $client) }}" class="px-6 py-2.5 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <div class="glass-panel p-6 mt-6 border-rose-500/30">
            <div class="text-lg font-semibold text-rose-400 mb-4">Danger Zone</div>
            <p class="text-sm text-zinc-400 mb-4">Once you delete a client, there is no going back. Please be certain.</p>
            <form method="POST" action="{{ route('branch.clients.destroy', $client) }}" onsubmit="return confirm('Are you sure you want to delete this client? This action cannot be undone.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Client
                </button>
            </form>
        </div>
    </div>
@endsection

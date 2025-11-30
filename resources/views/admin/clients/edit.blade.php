@extends('admin.layout')

@section('title', 'Edit Client')
@section('header', 'Edit Client')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.clients.show', $client) }}" class="chip text-sm">&larr; Back to Client</a>
    </div>

    <div class="max-w-2xl">
        <div class="glass-panel p-6">
            <div class="text-lg font-semibold mb-6">Edit Client Information</div>
            
            <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium mb-1">Company Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                        @error('company_name')
                            <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $client->email) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone', $client->phone) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Primary Branch</label>
                        <select name="primary_branch_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                            <option value="">Unassigned</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('primary_branch_id', $client->primary_branch_id) == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select name="status" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2" required>
                            <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive</option>
                            <option value="suspended" @selected(old('status', $client->status) === 'suspended')>Suspended</option>
                            <option value="blacklisted" @selected(old('status', $client->status) === 'blacklisted')>Blacklisted</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Customer Type</label>
                        <select name="customer_type" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2" required>
                            <option value="regular" @selected(old('customer_type', $client->customer_type) === 'regular')>Regular</option>
                            <option value="vip" @selected(old('customer_type', $client->customer_type) === 'vip')>VIP</option>
                            <option value="prospect" @selected(old('customer_type', $client->customer_type) === 'prospect')>Prospect</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Credit Limit</label>
                        <input type="number" name="credit_limit" step="0.01" value="{{ old('credit_limit', $client->credit_limit) }}"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Payment Terms</label>
                        <input type="text" name="payment_terms" value="{{ old('payment_terms', $client->payment_terms) }}"
                            placeholder="e.g., NET30, COD"
                            class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">{{ old('notes', $client->notes) }}</textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="chip">Save Changes</button>
                    <a href="{{ route('admin.clients.show', $client) }}" class="chip bg-slate-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

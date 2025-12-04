@extends('client.layout')

@section('title', 'Address Book')
@section('header', 'Address Book')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <p class="text-zinc-400">Manage your saved addresses for faster checkout</p>
        <button onclick="document.getElementById('addAddressModal').classList.remove('hidden')" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Address
        </button>
    </div>

    @if($addresses->count() > 0)
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($addresses as $address)
                <div class="glass-panel p-5 {{ $address->is_default ? 'border-emerald-500/50' : '' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $address->label }}</span>
                            @if($address->is_default)
                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 text-xs rounded">Default</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('client.addresses.delete', $address) }}" onsubmit="return confirm('Delete this address?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-1 hover:bg-white/10 rounded transition-colors text-zinc-400 hover:text-red-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </form>
                    </div>
                    <div class="text-sm space-y-1">
                        <div class="font-medium">{{ $address->contact_name }}</div>
                        <div class="text-zinc-400">{{ $address->phone }}</div>
                        <div class="text-zinc-400">{{ $address->address_line_1 }}</div>
                        @if($address->address_line_2)
                            <div class="text-zinc-400">{{ $address->address_line_2 }}</div>
                        @endif
                        <div class="text-zinc-400">{{ $address->city }}, {{ $address->country }} {{ $address->postal_code }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="glass-panel p-12 text-center">
            <svg class="w-16 h-16 text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
            <h3 class="text-lg font-medium mb-2">No Saved Addresses</h3>
            <p class="text-zinc-400 mb-4">Add addresses for faster shipping</p>
            <button onclick="document.getElementById('addAddressModal').classList.remove('hidden')" class="btn-primary inline-flex">Add Your First Address</button>
        </div>
    @endif

    {{-- Add Address Modal --}}
    <div id="addAddressModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="glass-panel p-6 w-full max-w-lg mx-4">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold">Add New Address</h3>
                <button onclick="document.getElementById('addAddressModal').classList.add('hidden')" class="p-2 hover:bg-white/10 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('client.addresses.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-2">Label <span class="text-red-400">*</span></label>
                        <input type="text" name="label" class="input-field" placeholder="e.g., Home, Office" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Contact Name <span class="text-red-400">*</span></label>
                        <input type="text" name="contact_name" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Phone <span class="text-red-400">*</span></label>
                        <input type="text" name="phone" class="input-field" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-2">Address Line 1 <span class="text-red-400">*</span></label>
                        <input type="text" name="address_line_1" class="input-field" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium mb-2">Address Line 2</label>
                        <input type="text" name="address_line_2" class="input-field">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">City <span class="text-red-400">*</span></label>
                        <input type="text" name="city" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Country <span class="text-red-400">*</span></label>
                        <input type="text" name="country" class="input-field" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Postal Code</label>
                        <input type="text" name="postal_code" class="input-field">
                    </div>
                    <div class="flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_default" value="1" class="rounded bg-zinc-800 border-white/20">
                            <span class="text-sm">Set as default</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="btn-primary flex-1">Save Address</button>
                    <button type="button" onclick="document.getElementById('addAddressModal').classList.add('hidden')" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
@endsection

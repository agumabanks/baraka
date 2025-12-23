@extends('client.layout')

@section('title', trans_db('client.addresses.title', [], null, 'Address Book'))
@section('header', trans_db('client.addresses.header', [], null, 'Address Book'))

@section('content')
    @php
        $deleteConfirm = trans_db('client.addresses.delete_confirm', [], null, 'Delete this address?');
    @endphp
	    <div class="flex justify-between items-center mb-6">
	        <p class="text-zinc-400">{{ trans_db('client.addresses.subtitle', [], null, 'Manage your saved addresses for faster checkout') }}</p>
	        <button onclick="document.getElementById('addAddressModal').classList.remove('hidden')" class="btn-primary">
	            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
	            {{ trans_db('client.addresses.actions.add', [], null, 'Add Address') }}
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
	                                <span class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 text-xs rounded">{{ trans_db('client.addresses.default', [], null, 'Default') }}</span>
	                            @endif
	                        </div>
	                        <form method="POST" action="{{ route('client.addresses.delete', $address) }}" onsubmit="return confirm(@json($deleteConfirm))">
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
	            <h3 class="text-lg font-medium mb-2">{{ trans_db('client.addresses.empty.title', [], null, 'No Saved Addresses') }}</h3>
	            <p class="text-zinc-400 mb-4">{{ trans_db('client.addresses.empty.subtitle', [], null, 'Add addresses for faster shipping') }}</p>
	            <button onclick="document.getElementById('addAddressModal').classList.remove('hidden')" class="btn-primary inline-flex">{{ trans_db('client.addresses.empty.cta', [], null, 'Add Your First Address') }}</button>
	        </div>
	    @endif

    {{-- Add Address Modal --}}
	    <div id="addAddressModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
	        <div class="glass-panel p-6 w-full max-w-lg mx-4">
	            <div class="flex items-center justify-between mb-6">
	                <h3 class="text-lg font-semibold">{{ trans_db('client.addresses.modal.title', [], null, 'Add New Address') }}</h3>
	                <button onclick="document.getElementById('addAddressModal').classList.add('hidden')" class="p-2 hover:bg-white/10 rounded-lg">
	                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
	                </button>
	            </div>
            <form method="POST" action="{{ route('client.addresses.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-4">
	                    <div class="col-span-2">
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.label', [], null, 'Label') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="label" class="input-field" placeholder="{{ trans_db('client.addresses.form.label_placeholder', [], null, 'e.g., Home, Office') }}" required>
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.contact_name', [], null, 'Contact Name') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="contact_name" class="input-field" required>
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.phone', [], null, 'Phone') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="phone" class="input-field" required>
	                    </div>
	                    <div class="col-span-2">
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.address_1', [], null, 'Address Line 1') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="address_line_1" class="input-field" required>
	                    </div>
	                    <div class="col-span-2">
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.address_2', [], null, 'Address Line 2') }}</label>
	                        <input type="text" name="address_line_2" class="input-field">
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.city', [], null, 'City') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="city" class="input-field" required>
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.country', [], null, 'Country') }} <span class="text-red-400">*</span></label>
	                        <input type="text" name="country" class="input-field" required>
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.addresses.form.postal_code', [], null, 'Postal Code') }}</label>
	                        <input type="text" name="postal_code" class="input-field">
	                    </div>
	                    <div class="flex items-center">
	                        <label class="flex items-center gap-2 cursor-pointer">
	                            <input type="checkbox" name="is_default" value="1" class="rounded bg-zinc-800 border-white/20">
	                            <span class="text-sm">{{ trans_db('client.addresses.form.set_default', [], null, 'Set as default') }}</span>
	                        </label>
	                    </div>
	                </div>
	                <div class="flex gap-3 pt-4">
	                    <button type="submit" class="btn-primary flex-1">{{ trans_db('client.addresses.actions.save', [], null, 'Save Address') }}</button>
	                    <button type="button" onclick="document.getElementById('addAddressModal').classList.add('hidden')" class="btn-secondary">{{ trans_db('client.common.cancel', [], null, 'Cancel') }}</button>
	                </div>
	            </form>
	        </div>
	    </div>
@endsection

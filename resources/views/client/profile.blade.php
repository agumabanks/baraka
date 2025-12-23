@extends('client.layout')

@section('title', trans_db('client.profile.title', [], null, 'Profile'))
@section('header', trans_db('client.profile.header', [], null, 'My Profile'))

@section('content')
	    <div class="grid lg:grid-cols-3 gap-6">
	        <div class="lg:col-span-2 space-y-6">
	            {{-- Profile Info --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-6">{{ trans_db('client.profile.info.title', [], null, 'Profile Information') }}</h3>
	                <form method="POST" action="{{ route('client.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
	                    <div class="grid md:grid-cols-2 gap-4">
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.company_name', [], null, 'Company Name') }}</label>
	                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" 
	                                class="input-field" placeholder="{{ trans_db('client.profile.fields.company_name_placeholder', [], null, 'Your company name') }}">
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.contact_person', [], null, 'Contact Person') }} <span class="text-red-400">*</span></label>
	                            <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}" 
	                                class="input-field" required>
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.phone', [], null, 'Phone') }} <span class="text-red-400">*</span></label>
	                            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" 
	                                class="input-field" required>
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.mobile', [], null, 'Mobile') }}</label>
	                            <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}" 
	                                class="input-field">
	                        </div>
	                        <div class="md:col-span-2">
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.billing_address', [], null, 'Billing Address') }}</label>
	                            <textarea name="billing_address" rows="2" class="input-field">{{ old('billing_address', $customer->billing_address) }}</textarea>
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.city', [], null, 'City') }}</label>
	                            <input type="text" name="city" value="{{ old('city', $customer->city) }}" 
	                                class="input-field">
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.fields.country', [], null, 'Country') }}</label>
	                            <input type="text" name="country" value="{{ old('country', $customer->country) }}" 
	                                class="input-field">
	                        </div>
	                    </div>

	                    <div class="pt-4">
	                        <button type="submit" class="btn-primary">{{ trans_db('client.common.save_changes', [], null, 'Save Changes') }}</button>
	                    </div>
	                </form>
	            </div>

	            {{-- Change Password --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-6">{{ trans_db('client.profile.password.title', [], null, 'Change Password') }}</h3>
	                <form method="POST" action="{{ route('client.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.password.current', [], null, 'Current Password') }}</label>
	                        <input type="password" name="current_password" class="input-field" required>
                        @error('current_password')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
	                    <div class="grid md:grid-cols-2 gap-4">
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.password.new', [], null, 'New Password') }}</label>
	                            <input type="password" name="password" class="input-field" required>
	                        </div>
	                        <div>
	                            <label class="block text-sm font-medium mb-2">{{ trans_db('client.profile.password.confirm', [], null, 'Confirm New Password') }}</label>
	                            <input type="password" name="password_confirmation" class="input-field" required>
	                        </div>
	                    </div>

	                    <div class="pt-4">
	                        <button type="submit" class="btn-secondary">{{ trans_db('client.profile.password.update', [], null, 'Update Password') }}</button>
	                    </div>
	                </form>
	            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Account Card --}}
            <div class="glass-panel p-6">
                <div class="text-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                        {{ strtoupper(substr($customer->contact_person ?? 'U', 0, 1)) }}
                    </div>
                    <div class="font-semibold text-lg">{{ $customer->contact_person }}</div>
                    <div class="text-sm text-zinc-400">{{ $customer->email }}</div>
                </div>
	                <div class="space-y-3 text-sm">
	                    <div class="flex justify-between py-2 border-b border-white/10">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.customer_id', [], null, 'Customer ID') }}</span>
	                        <span class="font-mono">{{ $customer->customer_code }}</span>
	                    </div>
	                    <div class="flex justify-between py-2 border-b border-white/10">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.account_type', [], null, 'Account Type') }}</span>
	                        <span class="capitalize">{{ $customer->customer_type ?? trans_db('client.dashboard.account_summary.account_type_regular', [], null, 'Regular') }}</span>
	                    </div>
	                    <div class="flex justify-between py-2 border-b border-white/10">
	                        <span class="text-zinc-400">{{ trans_db('client.profile.member_since', [], null, 'Member Since') }}</span>
	                        <span>{{ ($customer->customer_since ?? $customer->created_at)->locale(app()->getLocale())->translatedFormat('M Y') }}</span>
	                    </div>
	                    @if($customer->discount_rate > 0)
	                        <div class="flex justify-between py-2 border-b border-white/10">
	                            <span class="text-zinc-400">{{ trans_db('client.dashboard.account_summary.discount_rate', [], null, 'Discount Rate') }}</span>
	                            <span class="text-emerald-400">{{ $customer->discount_rate }}%</span>
	                        </div>
	                    @endif
	                </div>
	            </div>

	            {{-- Statistics --}}
	            <div class="glass-panel p-6">
	                <h3 class="font-semibold mb-4">{{ trans_db('client.profile.stats.title', [], null, 'Your Statistics') }}</h3>
	                <div class="space-y-3 text-sm">
	                    <div class="flex justify-between py-2">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.stats.total_shipments', [], null, 'Total Shipments') }}</span>
	                        <span class="font-medium">{{ number_format($customer->total_shipments ?? 0) }}</span>
	                    </div>
	                    <div class="flex justify-between py-2">
	                        <span class="text-zinc-400">{{ trans_db('client.dashboard.stats.total_spent', [], null, 'Total Spent') }}</span>
	                        <span class="font-medium">${{ number_format($customer->total_spent ?? 0, 2) }}</span>
	                    </div>
	                    @if($customer->last_shipment_date)
	                        <div class="flex justify-between py-2">
	                            <span class="text-zinc-400">{{ trans_db('client.profile.stats.last_shipment', [], null, 'Last Shipment') }}</span>
	                            <span>{{ $customer->last_shipment_date->locale(app()->getLocale())->diffForHumans() }}</span>
	                        </div>
	                    @endif
	                </div>
	            </div>
	        </div>
	    </div>
@endsection

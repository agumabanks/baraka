@extends('client.layout')

@section('title', 'Profile')
@section('header', 'My Profile')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Profile Info --}}
            <div class="glass-panel p-6">
                <h3 class="font-semibold mb-6">Profile Information</h3>
                <form method="POST" action="{{ route('client.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Company Name</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" 
                                class="input-field" placeholder="Your company name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Contact Person <span class="text-red-400">*</span></label>
                            <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}" 
                                class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Phone <span class="text-red-400">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" 
                                class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}" 
                                class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2">Billing Address</label>
                            <textarea name="billing_address" rows="2" class="input-field">{{ old('billing_address', $customer->billing_address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">City</label>
                            <input type="text" name="city" value="{{ old('city', $customer->city) }}" 
                                class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Country</label>
                            <input type="text" name="country" value="{{ old('country', $customer->country) }}" 
                                class="input-field">
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>

            {{-- Change Password --}}
            <div class="glass-panel p-6">
                <h3 class="font-semibold mb-6">Change Password</h3>
                <form method="POST" action="{{ route('client.profile.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Current Password</label>
                        <input type="password" name="current_password" class="input-field" required>
                        @error('current_password')
                            <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">New Password</label>
                            <input type="password" name="password" class="input-field" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="input-field" required>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="btn-secondary">Update Password</button>
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
                        <span class="text-zinc-400">Customer ID</span>
                        <span class="font-mono">{{ $customer->customer_code }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-white/10">
                        <span class="text-zinc-400">Account Type</span>
                        <span class="capitalize">{{ $customer->customer_type ?? 'Regular' }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-white/10">
                        <span class="text-zinc-400">Member Since</span>
                        <span>{{ $customer->customer_since?->format('M Y') ?? $customer->created_at->format('M Y') }}</span>
                    </div>
                    @if($customer->discount_rate > 0)
                        <div class="flex justify-between py-2 border-b border-white/10">
                            <span class="text-zinc-400">Discount Rate</span>
                            <span class="text-emerald-400">{{ $customer->discount_rate }}%</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Statistics --}}
            <div class="glass-panel p-6">
                <h3 class="font-semibold mb-4">Your Statistics</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between py-2">
                        <span class="text-zinc-400">Total Shipments</span>
                        <span class="font-medium">{{ number_format($customer->total_shipments ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-zinc-400">Total Spent</span>
                        <span class="font-medium">${{ number_format($customer->total_spent ?? 0, 2) }}</span>
                    </div>
                    @if($customer->last_shipment_date)
                        <div class="flex justify-between py-2">
                            <span class="text-zinc-400">Last Shipment</span>
                            <span>{{ $customer->last_shipment_date->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

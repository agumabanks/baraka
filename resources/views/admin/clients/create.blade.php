@extends('admin.layout')

@section('title', 'New Client')
@section('header', 'New Client')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.clients.index') }}" class="chip text-sm">&larr; Back to Clients</a>
    </div>

    <div class="max-w-4xl">
        <div class="glass-panel p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <div class="text-lg font-semibold">Create New Client</div>
                    <div class="text-sm muted">Add a new customer to the system</div>
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

            <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-6">
                @csrf

                {{-- Company Information --}}
                <div class="border-b border-white/10 pb-6">
                    <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Company Information</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Company Name <span class="text-rose-400">*</span></label>
                            <input type="text" name="company_name" value="{{ old('company_name') }}" required
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="Acme Corporation">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Contact Person <span class="text-rose-400">*</span></label>
                            <input type="text" name="contact_person" value="{{ old('contact_person') }}" required
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email <span class="text-rose-400">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="contact@company.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Phone <span class="text-rose-400">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" required
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="+1 234 567 8900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="+1 234 567 8900">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Tax ID / VAT Number</label>
                            <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="VAT123456789">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Industry</label>
                            <select name="industry" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="">Select Industry</option>
                                <option value="retail" @selected(old('industry') === 'retail')>Retail</option>
                                <option value="manufacturing" @selected(old('industry') === 'manufacturing')>Manufacturing</option>
                                <option value="ecommerce" @selected(old('industry') === 'ecommerce')>E-Commerce</option>
                                <option value="healthcare" @selected(old('industry') === 'healthcare')>Healthcare</option>
                                <option value="technology" @selected(old('industry') === 'technology')>Technology</option>
                                <option value="food" @selected(old('industry') === 'food')>Food & Beverage</option>
                                <option value="logistics" @selected(old('industry') === 'logistics')>Logistics</option>
                                <option value="other" @selected(old('industry') === 'other')>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Address Information --}}
                <div class="border-b border-white/10 pb-6">
                    <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Address Information</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Billing Address</label>
                            <textarea name="billing_address" rows="2"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="123 Main Street, Suite 100">{{ old('billing_address') }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-1">Shipping Address</label>
                            <textarea name="shipping_address" rows="2"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="456 Warehouse Road">{{ old('shipping_address') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">City</label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="Istanbul">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Country</label>
                            <input type="text" name="country" value="{{ old('country') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="Turkey">
                        </div>
                    </div>
                </div>

                {{-- Account Settings --}}
                <div class="border-b border-white/10 pb-6">
                    <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Account Settings</h3>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Primary Branch <span class="text-rose-400">*</span></label>
                            <select name="primary_branch_id" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('primary_branch_id') == $branch->id)>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Account Manager</label>
                            <select name="account_manager_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="">No Manager Assigned</option>
                                @foreach($accountManagers as $manager)
                                    <option value="{{ $manager->id }}" @selected(old('account_manager_id') == $manager->id)>
                                        {{ $manager->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Status <span class="text-rose-400">*</span></label>
                            <select name="status" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="prospect" @selected(old('status') === 'prospect')>Prospect</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Customer Type <span class="text-rose-400">*</span></label>
                            <select name="customer_type" required class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="regular" @selected(old('customer_type', 'regular') === 'regular')>Regular</option>
                                <option value="vip" @selected(old('customer_type') === 'vip')>VIP</option>
                                <option value="prospect" @selected(old('customer_type') === 'prospect')>Prospect</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Financial Settings --}}
                <div class="border-b border-white/10 pb-6">
                    <h3 class="text-sm font-medium uppercase text-zinc-400 mb-4">Financial Settings</h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Credit Limit ({{ $currency }})</label>
                            <input type="number" name="credit_limit" step="0.01" min="0" value="{{ old('credit_limit', '0') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="5000.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Discount Rate (%)</label>
                            <input type="number" name="discount_rate" step="0.01" min="0" max="100" value="{{ old('discount_rate', '0') }}"
                                class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                                placeholder="5.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Payment Terms</label>
                            <select name="payment_terms" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                                <option value="cod" @selected(old('payment_terms') === 'cod')>Cash on Delivery</option>
                                <option value="prepaid" @selected(old('payment_terms') === 'prepaid')>Prepaid</option>
                                <option value="net15" @selected(old('payment_terms') === 'net15')>Net 15</option>
                                <option value="net30" @selected(old('payment_terms') === 'net30')>Net 30</option>
                                <option value="net45" @selected(old('payment_terms') === 'net45')>Net 45</option>
                                <option value="net60" @selected(old('payment_terms') === 'net60')>Net 60</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="3"
                        class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        placeholder="Any additional notes about this client...">{{ old('notes') }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Client
                    </button>
                    <a href="{{ route('admin.clients.index') }}" class="px-6 py-2.5 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-medium transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

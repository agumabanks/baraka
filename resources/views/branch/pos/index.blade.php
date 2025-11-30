@extends('branch.layout')

@section('title', 'Shipments POS')

@section('content')
<div class="min-h-screen p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">Shipments POS</h1>
            <p class="text-slate-400 text-sm">Quick shipment creation for frontdesk operations</p>
        </div>
        <div class="flex items-center gap-4">
            {{-- Quick Track --}}
            <div class="flex items-center gap-2">
                <input type="text" id="quickTrackInput" placeholder="Track shipment..." 
                    class="bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 w-48">
                <button onclick="quickTrack()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm transition">Track</button>
            </div>
            {{-- Today's Stats --}}
            <div class="glass-panel px-4 py-2 flex items-center gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-emerald-400">{{ $todayStats['shipments_count'] ?? 0 }}</div>
                    <div class="text-xs text-slate-400">Today's Shipments</div>
                </div>
                <div class="text-center border-l border-white/10 pl-6">
                    <div class="text-2xl font-bold text-white">${{ number_format($todayStats['total_revenue'] ?? 0, 2) }}</div>
                    <div class="text-xs text-slate-400">Revenue</div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main POS Form --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Customer Section --}}
            <div class="glass-panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Customer
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm text-slate-400 mb-1">Search Customer</label>
                        <div class="relative">
                            <input type="text" id="customerSearch" placeholder="Search by name, phone, or email..."
                                class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                            <div id="customerResults" class="absolute top-full left-0 right-0 bg-obsidian-900 border border-white/10 rounded-lg mt-1 max-h-48 overflow-y-auto hidden z-50"></div>
                        </div>
                        <input type="hidden" id="customerId" name="customer_id">
                    </div>
                    <div id="selectedCustomer" class="col-span-2 hidden">
                        <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4 flex items-center justify-between">
                            <div>
                                <div class="font-medium text-white" id="customerName"></div>
                                <div class="text-sm text-slate-400" id="customerPhone"></div>
                            </div>
                            <button onclick="clearCustomer()" class="text-rose-400 hover:text-rose-300 text-sm">Change</button>
                        </div>
                    </div>
                    <div class="col-span-2 border-t border-white/10 pt-4 mt-2">
                        <button onclick="showNewCustomerForm()" class="text-emerald-400 hover:text-emerald-300 text-sm flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Add New Customer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Shipment Details --}}
            <div class="glass-panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Shipment Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Origin Branch</label>
                        <div class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white">
                            {{ $currentBranch->name ?? 'Current Branch' }}
                        </div>
                        <input type="hidden" id="originBranch" value="{{ $branchId }}">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Destination Branch</label>
                        <select id="destBranch" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500" onchange="calculateRate()">
                            <option value="">Select destination...</option>
                            @foreach($branches as $branch)
                                @if($branch->id != $branchId)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Service Level</label>
                        <select id="serviceLevel" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500" onchange="calculateRate()">
                            <option value="economy">Economy (5-7 days)</option>
                            <option value="standard" selected>Standard (3-5 days)</option>
                            <option value="express">Express (1-2 days)</option>
                            <option value="priority">Priority (Same day)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Payer</label>
                        <select id="payerType" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
                            <option value="sender">Sender (Prepaid)</option>
                            <option value="receiver">Receiver (Collect)</option>
                            <option value="third_party">Third Party</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Package Details --}}
            <div class="glass-panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    Package Details
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Weight (kg) <span class="text-rose-400">*</span></label>
                        <input type="number" id="weight" step="0.01" min="0.01" required
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500" 
                            onchange="calculateRate()">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Length (cm)</label>
                        <input type="number" id="length" step="1" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500"
                            onchange="calculateRate()">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Width (cm)</label>
                        <input type="number" id="width" step="1" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500"
                            onchange="calculateRate()">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Height (cm)</label>
                        <input type="number" id="height" step="1" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500"
                            onchange="calculateRate()">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-slate-400 mb-1">Description</label>
                        <input type="text" id="description" placeholder="Package contents..."
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Pieces</label>
                        <input type="number" id="pieces" value="1" min="1"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Declared Value ($)</label>
                        <input type="number" id="declaredValue" step="0.01" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500"
                            onchange="calculateRate()">
                    </div>
                </div>
                
                {{-- Options --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-white/10">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="isFragile" class="rounded bg-white/5 border-white/20 text-emerald-500">
                        <label for="isFragile" class="text-sm text-slate-300">Fragile</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="requiresSignature" class="rounded bg-white/5 border-white/20 text-emerald-500">
                        <label for="requiresSignature" class="text-sm text-slate-300">Signature Required</label>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Insurance</label>
                        <select id="insuranceType" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-emerald-500" onchange="calculateRate()">
                            <option value="none">No Insurance</option>
                            <option value="basic">Basic (1%)</option>
                            <option value="full">Full (2%)</option>
                            <option value="premium">Premium (3%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">COD Amount ($)</label>
                        <input type="number" id="codAmount" step="0.01" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:border-emerald-500"
                            onchange="calculateRate()">
                    </div>
                </div>
            </div>

            {{-- Receiver Details --}}
            <div class="glass-panel p-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Receiver Details
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Receiver Name</label>
                        <input type="text" id="receiverName"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Receiver Phone</label>
                        <input type="text" id="receiverPhone"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-slate-400 mb-1">Delivery Address</label>
                        <textarea id="deliveryAddress" rows="2"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500"></textarea>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-slate-400 mb-1">Special Instructions</label>
                        <textarea id="specialInstructions" rows="2" placeholder="Delivery notes, handling instructions..."
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:border-emerald-500"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Sidebar - Pricing & Actions --}}
        <div class="space-y-6">
            {{-- Pricing Card --}}
            <div class="glass-panel p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    Price Calculation
                </h3>
                
                <div id="pricingDetails" class="space-y-3 text-sm">
                    <div class="flex justify-between text-slate-400">
                        <span>Base Rate</span>
                        <span id="baseRate">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Weight Charge</span>
                        <span id="weightCharge">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400" id="surchargeRow" style="display: none;">
                        <span>Surcharges</span>
                        <span id="surcharges">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400" id="insuranceRow" style="display: none;">
                        <span>Insurance</span>
                        <span id="insuranceAmount">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400" id="codRow" style="display: none;">
                        <span>COD Fee</span>
                        <span id="codFee">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Tax</span>
                        <span id="taxAmount">$0.00</span>
                    </div>
                    <div class="border-t border-white/10 pt-3 flex justify-between font-bold text-white text-lg">
                        <span>Total</span>
                        <span id="totalAmount" class="text-emerald-400">$0.00</span>
                    </div>
                </div>

                {{-- Payment Section --}}
                <div class="mt-6 pt-6 border-t border-white/10">
                    <label class="block text-sm text-slate-400 mb-2">Payment Method</label>
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <button type="button" onclick="selectPayment('cash')" class="payment-btn px-3 py-2 border border-white/20 rounded-lg text-sm text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition" data-method="cash">
                            Cash
                        </button>
                        <button type="button" onclick="selectPayment('card')" class="payment-btn px-3 py-2 border border-white/20 rounded-lg text-sm text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition" data-method="card">
                            Card
                        </button>
                        <button type="button" onclick="selectPayment('mobile_money')" class="payment-btn px-3 py-2 border border-white/20 rounded-lg text-sm text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition" data-method="mobile_money">
                            Mobile Money
                        </button>
                        <button type="button" onclick="selectPayment('cod')" class="payment-btn px-3 py-2 border border-white/20 rounded-lg text-sm text-slate-300 hover:border-emerald-500 hover:text-emerald-400 transition" data-method="cod">
                            COD
                        </button>
                    </div>
                    <input type="hidden" id="paymentMethod" value="cash">

                    <div id="amountReceivedSection">
                        <label class="block text-sm text-slate-400 mb-1">Amount Received ($)</label>
                        <input type="number" id="amountReceived" step="0.01" min="0"
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 space-y-3">
                    <button onclick="createShipment()" id="createBtn"
                        class="w-full py-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                        Create Shipment
                    </button>
                    <button onclick="resetForm()" class="w-full py-3 border border-white/20 text-slate-300 hover:border-white/40 rounded-xl transition">
                        Clear Form
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Shipments --}}
    <div class="glass-panel p-6 mt-6">
        <h3 class="text-lg font-semibold text-white mb-4">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-400 border-b border-white/10">
                        <th class="pb-3 font-medium">Tracking #</th>
                        <th class="pb-3 font-medium">Customer</th>
                        <th class="pb-3 font-medium">Destination</th>
                        <th class="pb-3 font-medium">Service</th>
                        <th class="pb-3 font-medium">Amount</th>
                        <th class="pb-3 font-medium">Status</th>
                        <th class="pb-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($recentShipments as $shipment)
                    <tr class="text-slate-300 hover:bg-white/5">
                        <td class="py-3 font-mono">{{ $shipment->tracking_number }}</td>
                        <td class="py-3">{{ $shipment->customer?->name ?? 'N/A' }}</td>
                        <td class="py-3">{{ $shipment->destBranch?->name ?? 'N/A' }}</td>
                        <td class="py-3">
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $shipment->service_level === 'priority' ? 'bg-rose-500/20 text-rose-400' : '' }}
                                {{ $shipment->service_level === 'express' ? 'bg-amber-500/20 text-amber-400' : '' }}
                                {{ $shipment->service_level === 'standard' ? 'bg-sky-500/20 text-sky-400' : '' }}
                                {{ $shipment->service_level === 'economy' ? 'bg-slate-500/20 text-slate-400' : '' }}">
                                {{ ucfirst($shipment->service_level) }}
                            </span>
                        </td>
                        <td class="py-3">${{ number_format($shipment->price_amount, 2) }}</td>
                        <td class="py-3">
                            <span class="px-2 py-1 text-xs rounded-full bg-emerald-500/20 text-emerald-400">{{ ucfirst($shipment->status) }}</span>
                        </td>
                        <td class="py-3">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('branch.pos.label', $shipment) }}" target="_blank" class="text-slate-400 hover:text-white" title="Print Label">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                                <a href="{{ route('branch.pos.receipt', $shipment) }}" target="_blank" class="text-slate-400 hover:text-white" title="Print Receipt">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No shipments today</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- New Customer Modal --}}
<div id="newCustomerModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="glass-panel p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-white mb-4">Add New Customer</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-1">Name <span class="text-rose-400">*</span></label>
                <input type="text" id="newCustomerName" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Phone <span class="text-rose-400">*</span></label>
                <input type="text" id="newCustomerPhone" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Email</label>
                <input type="email" id="newCustomerEmail" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Company</label>
                <input type="text" id="newCustomerCompany" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-emerald-500">
            </div>
        </div>
        <div class="flex gap-3 mt-6">
            <button onclick="hideNewCustomerForm()" class="flex-1 py-3 border border-white/20 text-slate-300 rounded-lg hover:border-white/40 transition">Cancel</button>
            <button onclick="createCustomer()" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">Create Customer</button>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div id="successModal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
    <div class="glass-panel p-8 w-full max-w-md mx-4 text-center">
        <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <h3 class="text-xl font-semibold text-white mb-2">Shipment Created!</h3>
        <p class="text-slate-400 mb-2">Tracking Number:</p>
        <p class="text-2xl font-mono text-emerald-400 mb-6" id="newTrackingNumber"></p>
        <div class="flex gap-3">
            <a href="#" id="printLabelBtn" target="_blank" class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Print Label
            </a>
            <a href="#" id="printReceiptBtn" target="_blank" class="flex-1 py-3 bg-white/10 hover:bg-white/20 text-white rounded-lg transition flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Print Receipt
            </a>
        </div>
        <button onclick="closeSuccessAndReset()" class="w-full mt-4 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg transition">
            Create Another Shipment
        </button>
    </div>
</div>

@push('scripts')
<script>
let selectedCustomer = null;
let currentPricing = null;
let debounceTimer = null;
let searchResults = [];

// Customer Search with improved AJAX
const customerSearchInput = document.getElementById('customerSearch');
const customerResultsDiv = document.getElementById('customerResults');

customerSearchInput.addEventListener('input', function(e) {
    clearTimeout(debounceTimer);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        customerResultsDiv.classList.add('hidden');
        customerResultsDiv.innerHTML = '';
        return;
    }
    
    // Show loading state
    customerResultsDiv.innerHTML = '<div class="p-3 text-slate-400 text-sm"><svg class="animate-spin inline w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Searching...</div>';
    customerResultsDiv.classList.remove('hidden');
    
    debounceTimer = setTimeout(() => searchCustomer(query), 300);
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!customerSearchInput.contains(e.target) && !customerResultsDiv.contains(e.target)) {
        customerResultsDiv.classList.add('hidden');
    }
});

// Handle customer selection via event delegation
customerResultsDiv.addEventListener('click', function(e) {
    const resultItem = e.target.closest('[data-customer-index]');
    if (resultItem) {
        const index = parseInt(resultItem.dataset.customerIndex);
        if (searchResults[index]) {
            selectCustomer(searchResults[index]);
        }
    }
});

async function searchCustomer(query) {
    try {
        const response = await fetch(`{{ route('branch.pos.search-customer') }}?q=${encodeURIComponent(query)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Search failed');
        }
        
        const data = await response.json();
        searchResults = data.results || [];
        
        if (searchResults.length === 0) {
            customerResultsDiv.innerHTML = '<div class="p-3 text-slate-400 text-sm">No customers found. <a href="#" onclick="showNewCustomerForm(); return false;" class="text-emerald-400 hover:underline">Create new?</a></div>';
        } else {
            customerResultsDiv.innerHTML = searchResults.map((c, i) => `
                <div class="p-3 hover:bg-white/10 cursor-pointer border-b border-white/5 last:border-0 transition-colors" data-customer-index="${i}">
                    <div class="font-medium text-white">${escapeHtml(c.display || c.name)}</div>
                    <div class="text-sm text-slate-400">${escapeHtml(c.phone || '')} ${c.email ? '&bull; ' + escapeHtml(c.email) : ''}</div>
                </div>
            `).join('');
        }
        customerResultsDiv.classList.remove('hidden');
    } catch (error) {
        console.error('Search error:', error);
        customerResultsDiv.innerHTML = '<div class="p-3 text-rose-400 text-sm">Search failed. Please try again.</div>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function selectCustomer(customer) {
    selectedCustomer = customer;
    document.getElementById('customerId').value = customer.id;
    document.getElementById('customerName').textContent = customer.display;
    document.getElementById('customerPhone').textContent = customer.phone || customer.email;
    document.getElementById('selectedCustomer').classList.remove('hidden');
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerResults').classList.add('hidden');
}

function clearCustomer() {
    selectedCustomer = null;
    document.getElementById('customerId').value = '';
    document.getElementById('selectedCustomer').classList.add('hidden');
}

// Payment Method
function selectPayment(method) {
    document.querySelectorAll('.payment-btn').forEach(btn => {
        btn.classList.remove('border-emerald-500', 'text-emerald-400');
        btn.classList.add('border-white/20', 'text-slate-300');
    });
    document.querySelector(`[data-method="${method}"]`).classList.remove('border-white/20', 'text-slate-300');
    document.querySelector(`[data-method="${method}"]`).classList.add('border-emerald-500', 'text-emerald-400');
    document.getElementById('paymentMethod').value = method;
    
    if (method === 'cod') {
        document.getElementById('amountReceivedSection').style.display = 'none';
    } else {
        document.getElementById('amountReceivedSection').style.display = 'block';
    }
}

// Calculate Rate
async function calculateRate() {
    const originBranch = document.getElementById('originBranch').value;
    const destBranch = document.getElementById('destBranch').value;
    const serviceLevel = document.getElementById('serviceLevel').value;
    const weight = parseFloat(document.getElementById('weight').value) || 0;
    
    if (!destBranch || weight <= 0) return;
    
    try {
        const response = await fetch('{{ route('branch.pos.calculate-rate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                origin_branch_id: originBranch,
                dest_branch_id: destBranch,
                service_level: serviceLevel,
                weight: weight,
                length: parseFloat(document.getElementById('length').value) || 0,
                width: parseFloat(document.getElementById('width').value) || 0,
                height: parseFloat(document.getElementById('height').value) || 0,
                declared_value: parseFloat(document.getElementById('declaredValue').value) || 0,
                insurance_type: document.getElementById('insuranceType').value,
                cod_amount: parseFloat(document.getElementById('codAmount').value) || 0,
            })
        });
        
        const data = await response.json();
        currentPricing = data;
        updatePricingDisplay(data);
    } catch (error) {
        console.error('Rate calculation error:', error);
    }
}

function updatePricingDisplay(pricing) {
    document.getElementById('baseRate').textContent = '$' + (pricing.base_rate || 0).toFixed(2);
    document.getElementById('weightCharge').textContent = '$' + (pricing.weight_charge || 0).toFixed(2);
    
    const surcharges = pricing.surcharges?.total || 0;
    document.getElementById('surchargeRow').style.display = surcharges > 0 ? 'flex' : 'none';
    document.getElementById('surcharges').textContent = '$' + surcharges.toFixed(2);
    
    const insurance = pricing.insurance?.amount || 0;
    document.getElementById('insuranceRow').style.display = insurance > 0 ? 'flex' : 'none';
    document.getElementById('insuranceAmount').textContent = '$' + insurance.toFixed(2);
    
    const codFee = pricing.cod_fee || 0;
    document.getElementById('codRow').style.display = codFee > 0 ? 'flex' : 'none';
    document.getElementById('codFee').textContent = '$' + codFee.toFixed(2);
    
    document.getElementById('taxAmount').textContent = '$' + (pricing.tax || 0).toFixed(2);
    document.getElementById('totalAmount').textContent = '$' + (pricing.total || 0).toFixed(2);
}

// Create Shipment
async function createShipment() {
    if (!selectedCustomer) {
        alert('Please select a customer');
        return;
    }
    
    const weight = parseFloat(document.getElementById('weight').value);
    if (!weight || weight <= 0) {
        alert('Please enter a valid weight');
        return;
    }
    
    const destBranch = document.getElementById('destBranch').value;
    if (!destBranch) {
        alert('Please select a destination branch');
        return;
    }
    
    const btn = document.getElementById('createBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Creating...';
    
    try {
        const response = await fetch('{{ route('branch.pos.create-shipment') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                customer_id: selectedCustomer.id,
                dest_branch_id: destBranch,
                service_level: document.getElementById('serviceLevel').value,
                payer_type: document.getElementById('payerType').value,
                weight: weight,
                length: parseFloat(document.getElementById('length').value) || null,
                width: parseFloat(document.getElementById('width').value) || null,
                height: parseFloat(document.getElementById('height').value) || null,
                description: document.getElementById('description').value,
                pieces: parseInt(document.getElementById('pieces').value) || 1,
                declared_value: parseFloat(document.getElementById('declaredValue').value) || 0,
                insurance_type: document.getElementById('insuranceType').value,
                cod_amount: parseFloat(document.getElementById('codAmount').value) || 0,
                payment_method: document.getElementById('paymentMethod').value,
                amount_received: parseFloat(document.getElementById('amountReceived').value) || 0,
                receiver_name: document.getElementById('receiverName').value,
                receiver_phone: document.getElementById('receiverPhone').value,
                delivery_address: document.getElementById('deliveryAddress').value,
                special_instructions: document.getElementById('specialInstructions').value,
                is_fragile: document.getElementById('isFragile').checked,
                requires_signature: document.getElementById('requiresSignature').checked,
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.shipment, data.urls);
        } else {
            alert(data.message || 'Failed to create shipment');
        }
    } catch (error) {
        console.error('Create error:', error);
        alert('An error occurred while creating the shipment');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Create Shipment';
    }
}

function showSuccess(shipment, urls) {
    document.getElementById('newTrackingNumber').textContent = shipment.tracking_number;
    document.getElementById('printLabelBtn').href = urls.label;
    document.getElementById('printReceiptBtn').href = urls.receipt;
    document.getElementById('successModal').classList.remove('hidden');
    document.getElementById('successModal').classList.add('flex');
}

function closeSuccessAndReset() {
    document.getElementById('successModal').classList.add('hidden');
    document.getElementById('successModal').classList.remove('flex');
    resetForm();
    location.reload();
}

function resetForm() {
    clearCustomer();
    document.getElementById('destBranch').value = '';
    document.getElementById('serviceLevel').value = 'standard';
    document.getElementById('payerType').value = 'sender';
    document.getElementById('weight').value = '';
    document.getElementById('length').value = '';
    document.getElementById('width').value = '';
    document.getElementById('height').value = '';
    document.getElementById('description').value = '';
    document.getElementById('pieces').value = '1';
    document.getElementById('declaredValue').value = '';
    document.getElementById('insuranceType').value = 'none';
    document.getElementById('codAmount').value = '';
    document.getElementById('receiverName').value = '';
    document.getElementById('receiverPhone').value = '';
    document.getElementById('deliveryAddress').value = '';
    document.getElementById('specialInstructions').value = '';
    document.getElementById('isFragile').checked = false;
    document.getElementById('requiresSignature').checked = false;
    document.getElementById('amountReceived').value = '';
    selectPayment('cash');
    updatePricingDisplay({});
}

// New Customer Modal
function showNewCustomerForm() {
    document.getElementById('newCustomerModal').classList.remove('hidden');
    document.getElementById('newCustomerModal').classList.add('flex');
}

function hideNewCustomerForm() {
    document.getElementById('newCustomerModal').classList.add('hidden');
    document.getElementById('newCustomerModal').classList.remove('flex');
}

async function createCustomer() {
    const name = document.getElementById('newCustomerName').value;
    const phone = document.getElementById('newCustomerPhone').value;
    
    if (!name || !phone) {
        alert('Name and phone are required');
        return;
    }
    
    try {
        const response = await fetch('{{ route('branch.pos.quick-create-customer') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                name: name,
                phone: phone,
                email: document.getElementById('newCustomerEmail').value,
                company: document.getElementById('newCustomerCompany').value,
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            selectCustomer({
                id: data.customer.id,
                display: data.customer.company ? `${data.customer.name} (${data.customer.company})` : data.customer.name,
                phone: data.customer.phone,
                email: data.customer.email,
            });
            hideNewCustomerForm();
            document.getElementById('newCustomerName').value = '';
            document.getElementById('newCustomerPhone').value = '';
            document.getElementById('newCustomerEmail').value = '';
            document.getElementById('newCustomerCompany').value = '';
        } else {
            alert(data.message || 'Failed to create customer');
        }
    } catch (error) {
        console.error('Create customer error:', error);
        alert('An error occurred');
    }
}

// Quick Track
async function quickTrack() {
    const tracking = document.getElementById('quickTrackInput').value;
    if (!tracking) return;
    
    try {
        const response = await fetch(`{{ route('branch.pos.quick-track') }}?tracking=${encodeURIComponent(tracking)}`);
        const data = await response.json();
        
        if (data.success) {
            alert(`Status: ${data.shipment.status}\nOrigin: ${data.shipment.origin}\nDestination: ${data.shipment.destination}`);
        } else {
            alert('Shipment not found');
        }
    } catch (error) {
        console.error('Track error:', error);
    }
}

// Initialize
selectPayment('cash');
</script>
@endpush
@endsection

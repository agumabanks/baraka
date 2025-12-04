@extends('client.layout')

@section('title', 'Support')
@section('header', 'Support')

@section('content')
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass-panel p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                <form class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Subject</label>
                        <select class="input-field">
                            <option value="">Select a topic...</option>
                            <option value="shipment">Shipment Issue</option>
                            <option value="billing">Billing Question</option>
                            <option value="account">Account Support</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Message</label>
                        <textarea rows="5" class="input-field" placeholder="How can we help you?"></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </div>

            <div class="glass-panel p-6">
                <h3 class="text-lg font-semibold mb-4">Frequently Asked Questions</h3>
                <div class="space-y-4" x-data="{ open: null }">
                    <div class="border border-white/10 rounded-lg">
                        <button @click="open = open === 1 ? null : 1" class="w-full p-4 text-left flex items-center justify-between">
                            <span class="font-medium">How do I track my shipment?</span>
                            <svg class="w-5 h-5 transform transition-transform" :class="open === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === 1" x-collapse class="px-4 pb-4 text-sm text-zinc-400">
                            You can track your shipment by entering your tracking number in the Track Shipment page or using the quick track bar in the header.
                        </div>
                    </div>
                    <div class="border border-white/10 rounded-lg">
                        <button @click="open = open === 2 ? null : 2" class="w-full p-4 text-left flex items-center justify-between">
                            <span class="font-medium">What are the delivery times?</span>
                            <svg class="w-5 h-5 transform transition-transform" :class="open === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === 2" x-collapse class="px-4 pb-4 text-sm text-zinc-400">
                            Delivery times vary by service level: Economy (7-10 days), Standard (5-7 days), Express (2-3 days), Priority (1-2 days).
                        </div>
                    </div>
                    <div class="border border-white/10 rounded-lg">
                        <button @click="open = open === 3 ? null : 3" class="w-full p-4 text-left flex items-center justify-between">
                            <span class="font-medium">How do I get a quote?</span>
                            <svg class="w-5 h-5 transform transition-transform" :class="open === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open === 3" x-collapse class="px-4 pb-4 text-sm text-zinc-400">
                            Use our Get Quote tool to enter your shipment details and receive instant pricing for all service levels.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-panel p-6">
                <h3 class="font-semibold mb-4">Contact Information</h3>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm text-zinc-400">Email</div>
                            <div>support@baraka.com</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm text-zinc-400">Phone</div>
                            <div>+1 (800) 123-4567</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <div class="text-sm text-zinc-400">Hours</div>
                            <div>24/7 Support</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-panel p-6">
                <h3 class="font-semibold mb-4">Your Account Manager</h3>
                @if($customer->accountManager)
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center font-bold">
                            {{ strtoupper(substr($customer->accountManager->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-medium">{{ $customer->accountManager->name }}</div>
                            <div class="text-sm text-zinc-400">{{ $customer->accountManager->email ?? 'Account Manager' }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-zinc-400 text-sm">No account manager assigned. Contact support for assistance.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

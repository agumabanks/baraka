@extends('branch.layout')

@section('title', 'Account – Support')

@section('content')
    <div class="max-w-4xl space-y-6">
        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-semibold mb-1">Support & Help</h1>
            <p class="muted text-sm">Get help, check system status, and contact our support team.</p>
        </div>

        {{-- System Status --}}
        <div class="glass-panel p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">System Status</div>
                    <div class="muted text-xs">Current operational status of all services.</div>
                </div>
                <span class="badge badge-success">All Systems Operational</span>
            </div>

            <div class="space-y-2">
                @php
                    $services = [
                        ['name' => 'Branch Portal', 'status' => 'operational'],
                        ['name' => 'Shipment Tracking', 'status' => 'operational'],
                        ['name' => 'API Services', 'status' => 'operational'],
                        ['name' => 'Email Notifications', 'status' => 'operational'],
                    ];
                @endphp

                @foreach($services as $service)
                    <div class="flex items-center justify-between py-2 border-b border-white/5 last:border-0">
                        <div class="text-sm">{{ $service['name'] }}</div>
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                            <span class="text-xs text-emerald-100">Operational</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Help Links --}}
        <div class="glass-panel p-6 space-y-4">
            <div>
                <div class="text-sm font-semibold mb-1">Quick Help</div>
                <div class="muted text-xs">Frequently accessed resources and guides.</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <a href="#" class="flex items-center gap-3 p-4 border border-white/10 rounded-lg bg-white/5 hover:bg-white/10 hover:border-emerald-500/30 transition-all">
                    <div class="p-2 rounded-lg bg-emerald-500/20">
                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium">User Guide</div>
                        <div class="text-xs muted">Complete documentation</div>
                    </div>
                </a>

                <a href="#" class="flex items-center gap-3 p-4 border border-white/10 rounded-lg bg-white/5 hover:bg-white/10 hover:border-emerald-500/30 transition-all">
                    <div class="p-2 rounded-lg bg-blue-500/20">
                        <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium">Video Tutorials</div>
                        <div class="text-xs muted">Watch and learn</div>
                    </div>
                </a>

                <a href="#" class="flex items-center gap-3 p-4 border border-white/10 rounded-lg bg-white/5 hover:bg-white/10 hover:border-emerald-500/30 transition-all">
                    <div class="p-2 rounded-lg bg-purple-500/20">
                        <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium">FAQs</div>
                        <div class="text-xs muted">Common questions</div>
                    </div>
                </a>

                <a href="#" class="flex items-center gap-3 p-4 border border-white/10 rounded-lg bg-white/5 hover:bg-white/10 hover:border-emerald-500/30 transition-all">
                    <div class="p-2 rounded-lg bg-yellow-500/20">
                        <svg class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium">What's New</div>
                        <div class="text-xs muted">Latest updates</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Contact Support --}}
        <div class="glass-panel p-6 space-y-5">
            <div>
                <div class="text-sm font-semibold mb-1">Contact Support</div>
                <div class="muted text-xs">Need help? Send us a message and we'll get back to you.</div>
            </div>

            <form method="POST" action="{{ route('branch.account.support.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="subject" class="block text-sm font-medium mb-2">Subject <span class="text-rose-400">*</span></label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                           placeholder="Brief description of your issue"
                           required>
                </div>

                <div>
                    <label for="category" class="block text-sm font-medium mb-2">Category <span class="text-rose-400">*</span></label>
                    <select id="category" 
                            name="category" 
                            class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                            required>
                        <option value="">Select a category</option>
                        <option value="technical">Technical Issue</option>
                        <option value="account">Account & Billing</option>
                        <option value="shipment">Shipment Related</option>
                        <option value="feature">Feature Request</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium mb-2">Priority <span class="text-rose-400">*</span></label>
                    <select id="priority" 
                            name="priority" 
                            class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                            required>
                        <option value="low">Low - General Question</option>
                        <option value="medium" selected>Medium - Issue affecting work</option>
                        <option value="high">High - Urgent, blocking operations</option>
                        <option value="critical">Critical - System down</option>
                    </select>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium mb-2">Message <span class="text-rose-400">*</span></label>
                    <textarea id="message" 
                              name="message" 
                              rows="6"
                              class="w-full bg-obsidian-700/50 border border-white/10 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50"
                              placeholder="Please provide as much detail as possible..."
                              required></textarea>
                    <p class="text-2xs muted mt-1">Include any error messages, screenshots, or steps to reproduce the issue.</p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button type="reset" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-white/5 hover:bg-white/10 border border-white/10 transition-colors">
                        Clear
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-500 hover:bg-emerald-600 text-white transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>

        {{-- Emergency Contact --}}
        <div class="glass-panel p-6 space-y-4 border border-rose-500/30 bg-rose-500/5">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <div class="text-sm font-semibold text-rose-100 mb-2">Emergency Support</div>
                    <div class="text-xs text-rose-200/80 space-y-1">
                        <p>For critical issues requiring immediate attention:</p>
                        <div class="flex items-center gap-2 mt-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <a href="tel:+254123456789" class="font-semibold hover:text-rose-100 transition-colors">+254 123 456 789</a>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <a href="mailto:emergency@baraka.co" class="font-semibold hover:text-rose-100 transition-colors">emergency@baraka.co</a>
                        </div>
                        <p class="text-2xs mt-2 opacity-80">Available 24/7 for critical incidents</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $notif = $settings['notifications'] ?? [];
@endphp

<form id="notificationsForm" onsubmit="event.preventDefault(); saveSettings('notificationsForm', '{{ route('branch.settings.notifications') }}');">
    @csrf
    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Notification Channels -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Notification Channels</h3>
                <p class="text-xs text-slate-400 mt-1">Choose how you receive notifications</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <i class="bi bi-envelope text-blue-400"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-white">Email Notifications</span>
                            <p class="text-2xs text-slate-500">Receive alerts via email</p>
                        </div>
                    </div>
                    <input type="checkbox" name="email_notifications" value="1" {{ ($notif['email_notifications'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                            <i class="bi bi-chat-dots text-green-400"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-white">SMS Notifications</span>
                            <p class="text-2xs text-slate-500">Receive critical alerts via SMS</p>
                        </div>
                    </div>
                    <input type="checkbox" name="sms_notifications" value="1" {{ ($notif['sms_notifications'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-4 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                            <i class="bi bi-bell text-purple-400"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-white">Push Notifications</span>
                            <p class="text-2xs text-slate-500">Browser and mobile push alerts</p>
                        </div>
                    </div>
                    <input type="checkbox" name="push_notifications" value="1" {{ ($notif['push_notifications'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>
            </div>
        </div>

        <!-- Notification Events -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Notification Events</h3>
                <p class="text-xs text-slate-400 mt-1">Select which events trigger notifications</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">New Shipment Booked</span>
                        <p class="text-2xs text-slate-500">When a new shipment is created at this branch</p>
                    </div>
                    <input type="checkbox" name="notify_new_shipment" value="1" {{ ($notif['notify_new_shipment'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Status Changes</span>
                        <p class="text-2xs text-slate-500">When shipment status is updated</p>
                    </div>
                    <input type="checkbox" name="notify_status_change" value="1" {{ ($notif['notify_status_change'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Delivery Completed</span>
                        <p class="text-2xs text-slate-500">When a shipment is delivered</p>
                    </div>
                    <input type="checkbox" name="notify_delivery" value="1" {{ ($notif['notify_delivery'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Exceptions & Issues</span>
                        <p class="text-2xs text-slate-500">Failed delivery attempts, returns, damages</p>
                    </div>
                    <input type="checkbox" name="notify_exception" value="1" {{ ($notif['notify_exception'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">SLA Breach Warning</span>
                        <p class="text-2xs text-slate-500">When shipments are at risk of missing SLA</p>
                    </div>
                    <input type="checkbox" name="notify_sla_breach" value="1" {{ ($notif['notify_sla_breach'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>
            </div>
        </div>

        <!-- Reports & Summaries -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Reports & Summaries</h3>
                <p class="text-xs text-slate-400 mt-1">Automated periodic reports</p>
            </div>

            <div class="space-y-3">
                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Daily Summary</span>
                        <p class="text-2xs text-slate-500">End-of-day operations summary</p>
                    </div>
                    <input type="checkbox" name="daily_summary" value="1" {{ ($notif['daily_summary'] ?? false) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>

                <label class="flex items-center justify-between p-3 bg-obsidian-700 rounded-lg cursor-pointer">
                    <div>
                        <span class="text-sm font-medium text-white">Weekly Performance Report</span>
                        <p class="text-2xs text-slate-500">Weekly KPIs and performance metrics</p>
                    </div>
                    <input type="checkbox" name="weekly_report" value="1" {{ ($notif['weekly_report'] ?? true) ? 'checked' : '' }}
                           class="rounded border-white/20 bg-obsidian-600 text-emerald-500 focus:ring-emerald-500/50 w-5 h-5">
                </label>
            </div>
        </div>

        <!-- Escalation Contacts -->
        <div class="glass-panel p-6 space-y-5">
            <div class="border-b border-white/10 pb-4">
                <h3 class="text-lg font-semibold text-white">Escalation Contacts</h3>
                <p class="text-xs text-slate-400 mt-1">Who to contact for critical issues</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Escalation Email</label>
                    <input type="email" name="escalation_email" value="{{ $notif['escalation_email'] ?? '' }}" 
                           placeholder="manager@branch.example.com"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">Critical alerts go directly to this email</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Escalation Phone</label>
                    <input type="tel" name="escalation_phone" value="{{ $notif['escalation_phone'] ?? '' }}" 
                           placeholder="+256 700 000 000"
                           class="w-full bg-obsidian-700 border border-white/10 rounded-lg px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                    <p class="text-2xs text-slate-500 mt-1">For urgent after-hours escalations</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="bi bi-check-lg mr-2"></i>Save Notification Settings
        </button>
    </div>
</form>

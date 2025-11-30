@php
    // $settings is passed from controller as the 'notifications' section of preferences
    $s = $settings ?? [];
@endphp

@extends('settings.layouts.tailwind')

@section('title', 'Notifications')
@section('header', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-orange-500 flex items-center justify-center shadow-lg">
                <i class="bi bi-bell-fill text-2xl text-white"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Notifications</h1>
                <p class="text-slate-500 dark:text-slate-400">Configure how you receive alerts and updates</p>
            </div>
        </div>
        <button type="submit" form="notificationsForm" class="btn-primary shadow-lg shadow-orange-500/25">
            <i class="bi bi-check-lg mr-2"></i>
            Save Changes
        </button>
    </div>

    <form id="notificationsForm" method="POST" action="{{ route('settings.notifications.update') }}" class="ajax-form space-y-6">
        @csrf

        <!-- Notification Style -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-app-indicator text-purple-500"></i>
                <div>
                    <h3 class="pref-card-title">Notification Style</h3>
                    <p class="pref-card-desc">How notifications appear in the app</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Show Notifications</span>
                        <span class="pref-hint">Display in-app notification banners</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_notifications" value="1" {{ ($s['show_notifications'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Play Sound</span>
                        <span class="pref-hint">Audio alert for new notifications</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="notification_sound" value="1" {{ ($s['notification_sound'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Badge Count</span>
                        <span class="pref-hint">Show unread count on menu icons</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="badge_count" value="1" {{ ($s['badge_count'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Channels -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-broadcast text-blue-500"></i>
                <div>
                    <h3 class="pref-card-title">Notification Channels</h3>
                    <p class="pref-card-desc">How notifications are delivered</p>
                </div>
            </div>
            <div class="pref-card-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach([
                        ['key' => 'email_notifications', 'icon' => 'envelope', 'label' => 'Email', 'desc' => 'Receive notifications via email', 'default' => true],
                        ['key' => 'sms_notifications', 'icon' => 'chat-dots', 'label' => 'SMS', 'desc' => 'Text message notifications', 'default' => false],
                        ['key' => 'push_notifications', 'icon' => 'phone', 'label' => 'Push', 'desc' => 'Mobile push notifications', 'default' => true],
                        ['key' => 'slack_notifications', 'icon' => 'slack', 'label' => 'Slack', 'desc' => 'Slack channel alerts', 'default' => false],
                    ] as $channel)
                        <label class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <i class="bi bi-{{ $channel['icon'] }} text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-900 dark:text-white">{{ $channel['label'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $channel['desc'] }}</div>
                                </div>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="{{ $channel['key'] }}" value="1" {{ ($s[$channel['key']] ?? $channel['default']) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 pref-row">
                    <div class="pref-label">
                        <span>Slack Webhook URL</span>
                        <span class="pref-hint">Webhook URL for Slack notifications</span>
                    </div>
                    <div class="pref-control w-full max-w-md">
                        <input type="url" name="slack_webhook" value="{{ $s['slack_webhook'] ?? '' }}" class="input-field w-full" placeholder="https://hooks.slack.com/services/...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Event Types -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-list-check text-green-500"></i>
                <div>
                    <h3 class="pref-card-title">Event Types</h3>
                    <p class="pref-card-desc">Which events trigger notifications</p>
                </div>
            </div>
            <div class="pref-card-body space-y-4">
                @foreach([
                    ['key' => 'notify_new_shipment', 'label' => 'New Shipments', 'desc' => 'When a new shipment is created', 'default' => true],
                    ['key' => 'notify_status_change', 'label' => 'Status Changes', 'desc' => 'When shipment status updates', 'default' => true],
                    ['key' => 'notify_delivery', 'label' => 'Deliveries', 'desc' => 'When shipments are delivered', 'default' => true],
                    ['key' => 'notify_payment', 'label' => 'Payments', 'desc' => 'Payment received or overdue', 'default' => true],
                    ['key' => 'notify_system', 'label' => 'System Alerts', 'desc' => 'System errors and warnings', 'default' => true],
                ] as $event)
                    <div class="pref-row">
                        <div class="pref-label">
                            <span>{{ $event['label'] }}</span>
                            <span class="pref-hint">{{ $event['desc'] }}</span>
                        </div>
                        <div class="pref-control">
                            <label class="toggle-switch">
                                <input type="checkbox" name="{{ $event['key'] }}" value="1" {{ ($s[$event['key']] ?? $event['default']) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Quiet Hours -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-moon text-indigo-500"></i>
                <div>
                    <h3 class="pref-card-title">Quiet Hours</h3>
                    <p class="pref-card-desc">Pause non-critical notifications during specified hours</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Quiet Hours</span>
                        <span class="pref-hint">Suppress notifications during quiet time</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="quiet_hours_enabled" value="1" {{ !empty($s['quiet_hours_enabled']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Start Time</span>
                        <span class="pref-hint">When quiet hours begin</span>
                    </div>
                    <div class="pref-control w-32">
                        <input type="time" name="quiet_start" value="{{ $s['quiet_start'] ?? '22:00' }}" class="input-field w-full">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>End Time</span>
                        <span class="pref-hint">When quiet hours end</span>
                    </div>
                    <div class="pref-control w-32">
                        <input type="time" name="quiet_end" value="{{ $s['quiet_end'] ?? '07:00' }}" class="input-field w-full">
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Allow Critical Alerts</span>
                        <span class="pref-hint">Still receive urgent notifications</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="allow_critical" value="1" {{ ($s['allow_critical'] ?? true) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Digest -->
        <div class="pref-card">
            <div class="pref-card-header">
                <i class="bi bi-journal-text text-amber-500"></i>
                <div>
                    <h3 class="pref-card-title">Digest Settings</h3>
                    <p class="pref-card-desc">Receive bundled notification summaries</p>
                </div>
            </div>
            <div class="pref-card-body space-y-5">
                <div class="pref-row">
                    <div class="pref-label">
                        <span>Enable Digest</span>
                        <span class="pref-hint">Receive periodic notification summaries</span>
                    </div>
                    <div class="pref-control">
                        <label class="toggle-switch">
                            <input type="checkbox" name="digest_enabled" value="1" {{ !empty($s['digest_enabled']) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

                <div class="pref-row">
                    <div class="pref-label">
                        <span>Digest Frequency</span>
                        <span class="pref-hint">How often to send digest emails</span>
                    </div>
                    <div class="pref-control w-40">
                        <select name="digest_frequency" class="input-field w-full">
                            <option value="daily" {{ ($s['digest_frequency'] ?? 'daily') == 'daily' ? 'selected' : '' }}>Daily</option>
                            <option value="weekly" {{ ($s['digest_frequency'] ?? 'daily') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

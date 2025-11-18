@extends('settings.layouts.app')

@section('title', 'Notification Settings')

@section('breadcrumb_current')
    <li class="breadcrumb-item active">Notifications</li>
@endsection

@section('page_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">
                <i class="fas fa-bell me-2 text-primary"></i>
                   Notifications
            </h1>
            <p class="text-muted mb-0">Channels and delivery configuration</p>
        </div>
        <div class="page-actions">
            <button type="submit" form="notificationSettingsForm" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Save Changes
            </button>
        </div>
    </div>
@endsection

@section('content')
    <form id="notificationSettingsForm" method="POST" action="{{ route('settings.notifications.update') }}" class="ajax-form settings-form-enhanced">
        @csrf

        <x-settings.card title="Channels" subtitle="Choose how Baraka communicates important events">
            <div class="row">
                <div class="col-md-6">
                    <x-settings.toggle
                        name="email_notifications"
                        label="Email Notifications"
                        :checked="old('email_notifications', $settings['email_notifications'] ?? true)"
                        help="Send alerts and reports via email."
                        icon="fas fa-envelope"
                    />
                </div>
                <div class="col-md-6">
                    <x-settings.toggle
                        name="sms_notifications"
                        label="SMS Notifications"
                        :checked="old('sms_notifications', $settings['sms_notifications'] ?? false)"
                        help="Send critical alerts via SMS."
                        icon="fas fa-sms"
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <x-settings.toggle
                        name="push_notifications"
                        label="Push Notifications"
                        :checked="old('push_notifications', $settings['push_notifications'] ?? true)"
                        help="Enable realtime in-app notifications."
                        icon="fas fa-bell"
                    />
                </div>
                <div class="col-md-6">
                    <x-settings.toggle
                        name="slack_notifications"
                        label="Slack Notifications"
                        :checked="old('slack_notifications', $settings['slack_notifications'] ?? false)"
                        help="Send alerts to a Slack channel."
                        icon="fab fa-slack"
                    />
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="mb-4">
                        <label for="slack_webhook" class="form-label fw-semibold">Slack Webhook URL</label>
                        <input
                            type="url"
                            id="slack_webhook"
                            name="slack_webhook"
                            class="form-control @error('slack_webhook') is-invalid @enderror"
                            value="{{ old('slack_webhook', $settings['slack_webhook'] ?? '') }}"
                            placeholder="https://hooks.slack.com/services/..."
                        >
                        @error('slack_webhook')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </x-settings.card>
    </form>
@endsection

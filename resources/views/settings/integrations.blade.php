@extends('settings.layouts.tailwind')

@section('title', 'Integrations')
@section('header', 'Third-Party Integrations')

@section('content')
    <div class="max-w-7xl mx-auto">
        <form method="POST" action="{{ route('settings.integrations.update') }}" class="ajax-form space-y-6">
            @csrf
            <div class="card">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold">API & Webhooks</h3>
                    <p class="text-sm text-slate-500">Connect external services and APIs</p>
                </div>
                <div class="p-6">
                    <p class="text-slate-600 dark:text-slate-400">Integration settings coming soon</p>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary"><i class="bi bi-check-circle mr-2"></i>Save Settings</button>
            </div>
        </form>
    </div>
@endsection

@extends('branch.layout')

@section('title', 'Branch Settings')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">Branch Settings</h1>
            <p class="text-slate-400 mt-1">Configure localization, operations, notifications, and security for {{ $branch->name }}.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 text-xs font-medium bg-emerald-500/20 text-emerald-400 rounded-lg">
                <i class="bi bi-building mr-1"></i> {{ $branch->code }}
            </span>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="glass-panel p-1 flex flex-wrap gap-1">
        <a href="{{ route('branch.settings', ['tab' => 'general']) }}" 
           class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'general' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <i class="bi bi-gear mr-2"></i>General
        </a>
        <a href="{{ route('branch.settings', ['tab' => 'operations']) }}" 
           class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'operations' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <i class="bi bi-truck mr-2"></i>Operations
        </a>
        <a href="{{ route('branch.settings', ['tab' => 'notifications']) }}" 
           class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'notifications' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <i class="bi bi-bell mr-2"></i>Notifications
        </a>
        <a href="{{ route('branch.settings', ['tab' => 'labels']) }}" 
           class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'labels' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <i class="bi bi-printer mr-2"></i>Labels & Printing
        </a>
        <a href="{{ route('branch.settings', ['tab' => 'security']) }}" 
           class="px-4 py-2 text-sm font-medium rounded-lg transition-all {{ $activeTab === 'security' ? 'bg-emerald-500/20 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <i class="bi bi-shield-lock mr-2"></i>Security
        </a>
    </div>

    <!-- Tab Content -->
    @if($activeTab === 'general')
        @include('branch.settings._general')
    @elseif($activeTab === 'operations')
        @include('branch.settings._operations')
    @elseif($activeTab === 'notifications')
        @include('branch.settings._notifications')
    @elseif($activeTab === 'labels')
        @include('branch.settings._labels')
    @elseif($activeTab === 'security')
        @include('branch.settings._security')
    @endif
</div>

<script>
function saveSettings(formId, endpoint) {
    const form = document.getElementById(formId);
    const btn = form.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin mr-2"></i>Saving...';
    
    const formData = new FormData(form);
    
    fetch(endpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Failed to save settings', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while saving', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-blue-500';
    toast.className = `fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium ${bgColor}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('opacity-0', 'transition-opacity');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
@endsection

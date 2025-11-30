@extends('branch.layout')

@section('title', 'Clients & CRM')

@section('content')
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass-panel p-5 space-y-3 lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-semibold">Client roster</div>
                    <p class="muted text-xs">KYC, credit flags, and managers.</p>
                </div>
                <form method="GET" action="{{ route('branch.clients') }}" class="text-sm">
                    <select name="status" class="bg-obsidian-700 border border-white/10 rounded px-3 py-2" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach(['ACTIVE', 'INACTIVE', 'SUSPENDED'] as $status)
                            <option value="{{ $status }}" @selected($statusFilter === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="table-card">
                <table class="dhl-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Status</th>
                            <th>KYC</th>
                            <th>Account manager</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                            @php $kyc = (array) ($client->kyc_data ?? []); @endphp
                            <tr>
                                <td>
                                    <div class="font-semibold text-sm">{{ $client->business_name }}</div>
                                    <div class="muted text-2xs">Created {{ optional($client->created_at)->shortRelativeDiffForHumans() }}</div>
                                </td>
                                <td><span class="chip text-2xs">{{ $client->status }}</span></td>
                                <td class="muted text-xs">
                                    @if(!empty($kyc['flag']))
                                        <span class="badge badge-warn">{{ $kyc['flag'] }}</span>
                                    @else
                                        <span class="muted text-2xs">Clear</span>
                                    @endif
                                    @if(!empty($kyc['credit_limit']))
                                        <div class="muted text-2xs">Limit: {{ $kyc['credit_limit'] }}</div>
                                    @endif
                                </td>
                                <td class="muted text-xs">
                                    @if(!empty($kyc['account_manager_id']))
                                        @php $manager = $accountManagers->firstWhere('id', $kyc['account_manager_id']); @endphp
                                        {{ $manager?->user?->name ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('branch.clients.update', $client) }}" class="space-y-1 text-xs">
                                        @csrf
                                        @method('PATCH')
                                        <input type="text" name="kyc_flag" placeholder="KYC flag" class="w-full bg-obsidian-700 border border-white/10 rounded px-2 py-1">
                                        <input type="text" name="credit_limit" placeholder="Credit limit" class="w-full bg-obsidian-700 border border-white/10 rounded px-2 py-1">
                                        <select name="account_manager_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-2 py-1">
                                            <option value="">Account manager</option>
                                            @foreach($accountManagers as $manager)
                                                <option value="{{ $manager->id }}">{{ $manager->user?->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="chip text-2xs w-full justify-center" type="submit">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 muted">No clients found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $clients->withQueryString()->links() }}
            </div>
        </div>

        <div class="glass-panel p-5 space-y-3">
            <div class="text-sm font-semibold">Onboard client</div>
            <form method="POST" action="{{ route('branch.clients.store') }}" class="space-y-2 text-sm">
                @csrf
                <input type="text" name="business_name" placeholder="Business name" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <input type="text" name="status" placeholder="Status (e.g., ACTIVE)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <input type="text" name="kyc_flag" placeholder="KYC flag (optional)" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <input type="text" name="credit_limit" placeholder="Credit limit" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                <select name="account_manager_id" class="w-full bg-obsidian-700 border border-white/10 rounded px-3 py-2">
                    <option value="">Account manager</option>
                    @foreach($accountManagers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->user?->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="chip w-full justify-center">Create client</button>
            </form>
            <div class="glass-panel px-3 py-2 border border-amber-500/30 text-amber-100 text-xs">
                Capture KYC flags and credit limits for dispatch approvals.
            </div>
        </div>
    </div>
@endsection

@props([
    'headers' => [],
    'emptyMessage' => 'No data available',
    'id' => 'data-table',
])

<div class="table-card">
    <div class="overflow-x-auto">
        <table class="dhl-table" id="{{ $id }}">
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th class="text-left text-xs font-semibold uppercase tracking-wider">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @if(trim($slot) === '')
        <div class="p-8 text-center muted">
            <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <p>{{ $emptyMessage }}</p>
        </div>
    @endif
</div>

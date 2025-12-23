@extends('client.layout')

@section('title', trans_db('client.quotes.title', [], null, 'Get Quote'))
@section('header', trans_db('client.quotes.header', [], null, 'Get a Quote'))

	@section('content')
	    <div class="max-w-4xl mx-auto">
	        <div class="glass-panel p-6 mb-6">
	            <h2 class="text-lg font-semibold mb-4">{{ trans_db('client.quotes.calculator.title', [], null, 'Shipping Quote Calculator') }}</h2>
	            <form id="quoteForm" class="space-y-4">
	                @csrf
	                <div class="grid md:grid-cols-2 gap-4">
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.from', [], null, 'From (Origin)') }}</label>
	                        <select name="origin_branch_id" id="originBranch" class="input-field" required>
	                            <option value="">{{ trans_db('client.quotes.form.select_origin', [], null, 'Select origin...') }}</option>
	                            @foreach($branches as $branch)
	                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
	                            @endforeach
	                        </select>
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.to', [], null, 'To (Destination)') }}</label>
	                        <select name="destination_branch_id" id="destBranch" class="input-field" required>
	                            <option value="">{{ trans_db('client.quotes.form.select_destination', [], null, 'Select destination...') }}</option>
	                            @foreach($branches as $branch)
	                                <option value="{{ $branch->id }}">{{ $branch->name }} ({{ $branch->code }})</option>
	                            @endforeach
	                        </select>
	                    </div>
	                </div>
	                <div class="grid md:grid-cols-4 gap-4">
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.weight', [], null, 'Weight (kg)') }}</label>
	                        <input type="number" name="weight" id="weight" step="0.1" min="0.1" class="input-field" required placeholder="0.0">
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.length', [], null, 'Length (cm)') }}</label>
	                        <input type="number" name="length" id="length" step="0.1" class="input-field" placeholder="0">
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.width', [], null, 'Width (cm)') }}</label>
	                        <input type="number" name="width" id="width" step="0.1" class="input-field" placeholder="0">
	                    </div>
	                    <div>
	                        <label class="block text-sm font-medium mb-2">{{ trans_db('client.quotes.form.height', [], null, 'Height (cm)') }}</label>
	                        <input type="number" name="height" id="height" step="0.1" class="input-field" placeholder="0">
	                    </div>
	                </div>
	                <div>
	                    <button type="submit" class="btn-primary">
	                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
	                        {{ trans_db('client.quotes.actions.calculate', [], null, 'Calculate Quote') }}
	                    </button>
	                </div>
	            </form>
	        </div>

	        <div id="quotesResult" class="hidden">
	            <h3 class="text-lg font-semibold mb-4">{{ trans_db('client.quotes.results.title', [], null, 'Available Services') }}</h3>
	            <div id="quotesGrid" class="grid md:grid-cols-2 gap-4"></div>
	        </div>
	    </div>
@endsection

@push('scripts')
@php
    $quotesI18n = [
        'calculating' => trans_db('client.quotes.state.calculating', [], null, 'Calculating...'),
        'calculate_quote' => trans_db('client.quotes.actions.calculate', [], null, 'Calculate Quote'),
        'error_calculating' => trans_db('client.quotes.errors.calculating', [], null, 'Error calculating quote. Please try again.'),
        'no_services' => trans_db('client.quotes.empty', [], null, 'No services available for this route.'),
        'base_rate' => trans_db('client.quotes.breakdown.base_rate', [], null, 'Base Rate'),
        'weight' => trans_db('client.quotes.breakdown.weight', [], null, 'Weight'),
        'discount' => trans_db('client.quotes.breakdown.discount', [], null, 'Discount'),
        'select_service' => trans_db('client.quotes.actions.select_service', ['service' => ':service'], null, 'Select :service'),
        'service_level' => [
            'economy' => trans_db('client.quotes.service.economy', [], null, 'Economy'),
            'standard' => trans_db('client.quotes.service.standard', [], null, 'Standard'),
            'express' => trans_db('client.quotes.service.express', [], null, 'Express'),
            'priority' => trans_db('client.quotes.service.priority', [], null, 'Priority'),
        ],
        'service_desc' => [
            'economy' => trans_db('client.quotes.service_desc.economy', [], null, '7-10 business days'),
            'standard' => trans_db('client.quotes.service_desc.standard', [], null, '5-7 business days'),
            'express' => trans_db('client.quotes.service_desc.express', [], null, '2-3 business days'),
            'priority' => trans_db('client.quotes.service_desc.priority', [], null, '1-2 business days'),
        ],
    ];
@endphp
<script>
const I18N = @json($quotesI18n);
document.getElementById('quoteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> ' + I18N.calculating;
    
    try {
        const response = await fetch('{{ route('client.quotes.calculate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                origin_branch_id: formData.get('origin_branch_id'),
                destination_branch_id: formData.get('destination_branch_id'),
                weight: formData.get('weight'),
                length: formData.get('length'),
                width: formData.get('width'),
                height: formData.get('height'),
            })
        });
        
        const data = await response.json();
        displayQuotes(data.quotes);
    } catch (error) {
        alert(I18N.error_calculating);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg> ' + I18N.calculate_quote;
    }
});

function displayQuotes(quotes) {
    const container = document.getElementById('quotesResult');
    const grid = document.getElementById('quotesGrid');
    
    if (Object.keys(quotes).length === 0) {
        grid.innerHTML = '<div class="md:col-span-2 glass-panel p-6 text-center"><p class="text-zinc-400">' + I18N.no_services + '</p></div>';
    } else {
        const serviceInfo = {
            economy: { icon: '🐢', desc: I18N.service_desc.economy, color: 'border-zinc-500' },
            standard: { icon: '📦', desc: I18N.service_desc.standard, color: 'border-blue-500' },
            express: { icon: '🚀', desc: I18N.service_desc.express, color: 'border-amber-500' },
            priority: { icon: '⚡', desc: I18N.service_desc.priority, color: 'border-red-500' },
        };
        
        grid.innerHTML = Object.entries(quotes).map(([level, quote]) => {
            const info = serviceInfo[level] || { icon: '📦', desc: '', color: 'border-zinc-500' };
            const serviceLabel = (I18N.service_level[level] || level);
            return `
                <div class="glass-panel p-6 border-l-4 ${info.color}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">${info.icon}</span>
                            <span class="font-semibold">${serviceLabel}</span>
                        </div>
                        <div class="text-2xl font-bold">$${quote.total.toFixed(2)}</div>
                    </div>
                    <div class="text-sm text-zinc-400 mb-4">${info.desc}</div>
                    <div class="space-y-1 text-sm border-t border-white/10 pt-3">
                        <div class="flex justify-between"><span class="text-zinc-400">${I18N.base_rate}</span><span>$${(quote.base_rate || 0).toFixed(2)}</span></div>
                        <div class="flex justify-between"><span class="text-zinc-400">${I18N.weight} (${quote.chargeable_weight}kg)</span><span>$${(quote.weight_charge || 0).toFixed(2)}</span></div>
                        ${quote.discount_amount > 0 ? `<div class="flex justify-between text-emerald-400"><span>${I18N.discount}</span><span>-$${quote.discount_amount.toFixed(2)}</span></div>` : ''}
                    </div>
                    <a href="{{ route('client.shipments.create') }}?service=${level}" class="btn-primary w-full justify-center mt-4">
                        ${I18N.select_service.replace(':service', serviceLabel)}
                    </a>
                </div>
            `;
        }).join('');
    }
    
    container.classList.remove('hidden');
}
</script>
@endpush

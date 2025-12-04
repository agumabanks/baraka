@extends('branch.layout')

@section('title', 'Booking Wizard')

@push('styles')
<style>
    .wizard-step { opacity: 0.5; transition: all 0.3s ease; }
    .wizard-step.active { opacity: 1; }
    .wizard-step.completed { opacity: 1; }
    .wizard-step.completed .step-circle { background: #22c55e; border-color: #22c55e; }
    .step-circle { 
        width: 40px; height: 40px; 
        border-radius: 50%; 
        border: 2px solid rgba(255,255,255,0.3);
        display: flex; align-items: center; justify-content: center;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .wizard-step.active .step-circle { border-color: #fbbf24; background: rgba(251,191,36,0.2); }
    .step-content { display: none; }
    .step-content.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .form-input {
        width: 100%;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 12px 16px;
        color: #fff;
        transition: all 0.2s ease;
    }
    .form-input:focus { outline: none; border-color: #fbbf24; background: rgba(255,255,255,0.08); }
    .form-input::placeholder { color: rgba(255,255,255,0.4); }
    .form-label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px; color: rgba(255,255,255,0.7); }
    .customer-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .customer-card:hover, .customer-card.selected { border-color: #fbbf24; background: rgba(251,191,36,0.1); }
    .destination-card {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .destination-card:hover, .destination-card.selected { border-color: #22c55e; background: rgba(34,197,94,0.1); }
</style>
@endpush

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">New Shipment Booking</h1>
            <p class="text-sm text-zinc-400 mt-1">Create a new shipment from {{ $currentBranch->name ?? 'your branch' }}</p>
        </div>
        <a href="{{ route('branch.shipments.index') }}" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Shipments
        </a>
    </div>

    <!-- Progress Steps -->
    <div class="glass-panel p-6">
        <div class="flex items-center justify-between">
            <div class="wizard-step active" data-step="1">
                <div class="flex items-center gap-3">
                    <div class="step-circle">1</div>
                    <div>
                        <div class="font-medium">Customer</div>
                        <div class="text-xs text-zinc-400">Select or create</div>
                    </div>
                </div>
            </div>
            <div class="flex-1 h-px bg-white/10 mx-4"></div>
            <div class="wizard-step" data-step="2">
                <div class="flex items-center gap-3">
                    <div class="step-circle">2</div>
                    <div>
                        <div class="font-medium">Destination</div>
                        <div class="text-xs text-zinc-400">Select branch</div>
                    </div>
                </div>
            </div>
            <div class="flex-1 h-px bg-white/10 mx-4"></div>
            <div class="wizard-step" data-step="3">
                <div class="flex items-center gap-3">
                    <div class="step-circle">3</div>
                    <div>
                        <div class="font-medium">Package</div>
                        <div class="text-xs text-zinc-400">Details & pricing</div>
                    </div>
                </div>
            </div>
            <div class="flex-1 h-px bg-white/10 mx-4"></div>
            <div class="wizard-step" data-step="4">
                <div class="flex items-center gap-3">
                    <div class="step-circle">4</div>
                    <div>
                        <div class="font-medium">Confirm</div>
                        <div class="text-xs text-zinc-400">Review & book</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Customer Selection -->
    <div class="step-content active" id="step1">
        <div class="glass-panel p-6">
            <h3 class="text-lg font-semibold mb-4">Select or Create Customer</h3>
            
            <div class="mb-6">
                <label class="form-label">Search Existing Customer</label>
                <input type="text" id="customerSearch" class="form-input" placeholder="Search by name, email, or phone...">
            </div>

            <div id="customerResults" class="grid gap-3 mb-6"></div>

            <div class="border-t border-white/10 pt-6">
                <h4 class="font-medium mb-4">Or Create New Customer</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Full Name *</label>
                        <input type="text" id="newCustomerName" class="form-input" placeholder="Customer name">
                    </div>
                    <div>
                        <label class="form-label">Phone *</label>
                        <input type="text" id="newCustomerPhone" class="form-input" placeholder="+256...">
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" id="newCustomerEmail" class="form-input" placeholder="email@example.com">
                    </div>
                    <div>
                        <label class="form-label">Company</label>
                        <input type="text" id="newCustomerCompany" class="form-input" placeholder="Company name">
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button onclick="goToStep(2)" class="btn btn-primary">
                    Continue
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: Destination -->
    <div class="step-content" id="step2">
        <div class="glass-panel p-6">
            <h3 class="text-lg font-semibold mb-4">Select Destination Branch</h3>
            
            <div class="grid grid-cols-2 gap-4">
                @foreach($branches as $branch)
                <div class="destination-card" onclick="selectDestination({{ $branch->id }}, '{{ $branch->name }}')">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <div class="font-medium">{{ $branch->name }}</div>
                            <div class="text-xs text-zinc-400">{{ $branch->address ?? $branch->city ?? 'Branch' }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="flex justify-between mt-6">
                <button onclick="goToStep(1)" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </button>
                <button onclick="goToStep(3)" class="btn btn-primary">
                    Continue
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 3: Package Details -->
    <div class="step-content" id="step3">
        <div class="glass-panel p-6">
            <h3 class="text-lg font-semibold mb-4">Package Details</h3>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="form-label">Weight (kg) *</label>
                    <input type="number" id="packageWeight" class="form-input" placeholder="0.5" step="0.1" min="0.1">
                </div>
                <div>
                    <label class="form-label">Declared Value (UGX)</label>
                    <input type="number" id="packageValue" class="form-input" placeholder="50000">
                </div>
                <div class="col-span-2">
                    <label class="form-label">Package Description</label>
                    <textarea id="packageDescription" class="form-input" rows="2" placeholder="Brief description of contents..."></textarea>
                </div>
            </div>

            <div class="bg-white/5 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-zinc-400">Estimated Cost:</span>
                    <span id="estimatedCost" class="text-xl font-bold text-amber-400">UGX 0</span>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button onclick="goToStep(2)" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </button>
                <button onclick="goToStep(4)" class="btn btn-primary">
                    Review Booking
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Step 4: Confirmation -->
    <div class="step-content" id="step4">
        <div class="glass-panel p-6">
            <h3 class="text-lg font-semibold mb-4">Review & Confirm Booking</h3>
            
            <div class="space-y-4 mb-6">
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-sm text-zinc-400 mb-1">Customer</div>
                    <div id="summaryCustomer" class="font-medium">-</div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/5 rounded-lg p-4">
                        <div class="text-sm text-zinc-400 mb-1">From</div>
                        <div class="font-medium">{{ $currentBranch->name ?? 'Current Branch' }}</div>
                    </div>
                    <div class="bg-white/5 rounded-lg p-4">
                        <div class="text-sm text-zinc-400 mb-1">To</div>
                        <div id="summaryDestination" class="font-medium">-</div>
                    </div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-sm text-zinc-400 mb-1">Package</div>
                    <div id="summaryPackage" class="font-medium">-</div>
                </div>
                <div class="bg-amber-500/10 border border-amber-500/30 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="text-amber-400">Total Cost:</span>
                        <span id="summaryTotal" class="text-2xl font-bold text-amber-400">UGX 0</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between">
                <button onclick="goToStep(3)" class="btn btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back
                </button>
                <button onclick="confirmBooking()" class="btn btn-primary bg-emerald-600 hover:bg-emerald-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Confirm Booking
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentStep = 1;
let bookingData = {
    customer: null,
    destination: null,
    package: {}
};

function goToStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.getElementById('step' + step).classList.add('active');
    
    document.querySelectorAll('.wizard-step').forEach(el => {
        const s = parseInt(el.dataset.step);
        el.classList.remove('active', 'completed');
        if (s < step) el.classList.add('completed');
        if (s === step) el.classList.add('active');
    });
    
    currentStep = step;
    
    if (step === 4) updateSummary();
}

function selectDestination(id, name) {
    bookingData.destination = { id, name };
    document.querySelectorAll('.destination-card').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
}

function updateSummary() {
    document.getElementById('summaryCustomer').textContent = bookingData.customer?.name || 'Not selected';
    document.getElementById('summaryDestination').textContent = bookingData.destination?.name || 'Not selected';
    
    const weight = document.getElementById('packageWeight').value || '0';
    const desc = document.getElementById('packageDescription').value || 'No description';
    document.getElementById('summaryPackage').textContent = `${weight} kg - ${desc}`;
    
    const cost = document.getElementById('estimatedCost').textContent;
    document.getElementById('summaryTotal').textContent = cost;
}

function confirmBooking() {
    alert('Booking functionality - integrate with your booking API');
}

document.getElementById('customerSearch')?.addEventListener('input', function(e) {
    // Implement customer search
});
</script>
@endpush
@endsection

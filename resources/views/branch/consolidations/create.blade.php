@extends('branch.layout')

@section('title', 'Create Consolidation')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create New Consolidation</h1>
        <a href="{{ route('branch.consolidations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Consolidation Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('branch.consolidations.store') }}" method="POST">
                        @csrf
                        
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Consolidation Type <span class="text-danger">*</span></label>
                                <select name="type" id="type" class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="BBX" {{ old('type') == 'BBX' ? 'selected' : '' }}>BBX (Break Bulk Express)</option>
                                    <option value="LBX" {{ old('type') == 'LBX' ? 'selected' : '' }}>LBX (Loose Bulk Express)</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">
                                    BBX: Physical consolidation in a bag/container. LBX: Virtual consolidation.
                                </small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="cutoff_time" class="form-label">Cutoff Time</label>
                                <input type="datetime-local" class="form-control @error('cutoff_time') is-invalid @enderror" 
                                       id="cutoff_time" name="cutoff_time" value="{{ old('cutoff_time') }}">
                                @error('cutoff_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Destination <span class="text-danger">*</span></label>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="dest_branch" name="destination_type" value="branch" class="custom-control-input" checked>
                                <label class="custom-control-label" for="dest_branch">Internal Branch</label>
                            </div>
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="dest_custom" name="destination_type" value="custom" class="custom-control-input">
                                <label class="custom-control-label" for="dest_custom">Custom Destination (External)</label>
                            </div>
                        </div>

                        <div class="form-group" id="branch_select_group">
                            <label for="destination_branch_id">Select Destination Branch</label>
                            <select name="destination_branch_id" id="destination_branch_id" class="form-control @error('destination_branch_id') is-invalid @enderror">
                                <option value="">Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('destination_branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('destination_branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group d-none" id="custom_dest_group">
                            <label for="destination">Custom Destination Name</label>
                            <input type="text" class="form-control @error('destination') is-invalid @enderror" 
                                   id="destination" name="destination" value="{{ old('destination') }}" 
                                   placeholder="e.g., New York Gateway">
                            @error('destination')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="max_weight_kg">Max Weight (kg)</label>
                                <input type="number" step="0.01" class="form-control" id="max_weight_kg" name="max_weight_kg" value="{{ old('max_weight_kg') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="max_pieces">Max Pieces</label>
                                <input type="number" class="form-control" id="max_pieces" name="max_pieces" value="{{ old('max_pieces') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="transport_mode">Transport Mode</label>
                                <select name="transport_mode" id="transport_mode" class="form-control">
                                    <option value="">Select Mode</option>
                                    <option value="AIR">Air</option>
                                    <option value="SEA">Sea</option>
                                    <option value="ROAD">Road</option>
                                    <option value="RAIL">Rail</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Create Consolidation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Information</h6>
                </div>
                <div class="card-body">
                    <p><strong>BBX (Break Bulk Express):</strong> Use this for physical consolidations where multiple shipments are bagged or containerized together. Requires a physical bag tag.</p>
                    <p><strong>LBX (Loose Bulk Express):</strong> Use this for virtual consolidations where shipments travel together but are not physically bagged (e.g., palletized).</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const branchRadio = document.getElementById('dest_branch');
        const customRadio = document.getElementById('dest_custom');
        const branchGroup = document.getElementById('branch_select_group');
        const customGroup = document.getElementById('custom_dest_group');
        const destinationInput = document.getElementById('destination');
        const branchSelect = document.getElementById('destination_branch_id');

        function toggleDest() {
            if (branchRadio.checked) {
                branchGroup.classList.remove('d-none');
                customGroup.classList.add('d-none');
                // When branch is selected, destination string is populated from branch name on submit
                // or we can leave destination empty and let controller handle it?
                // The controller expects 'destination' string.
                // We should probably set the destination input value to the selected branch name hiddenly or handle in JS.
                destinationInput.required = false;
            } else {
                branchGroup.classList.add('d-none');
                customGroup.classList.remove('d-none');
                destinationInput.required = true;
            }
        }

        branchRadio.addEventListener('change', toggleDest);
        customRadio.addEventListener('change', toggleDest);
        
        // Handle destination string population
        document.querySelector('form').addEventListener('submit', function(e) {
            if (branchRadio.checked) {
                const selectedOption = branchSelect.options[branchSelect.selectedIndex];
                if (selectedOption.value) {
                    // Create a hidden input for destination if it doesn't exist or update the visible one (which is hidden)
                    // But wait, the controller uses $validated['destination'].
                    // If we use branch, we need to populate 'destination' field with branch name.
                    destinationInput.value = selectedOption.text.trim();
                }
            }
        });
        
        // Initial state
        toggleDest();
    });
</script>
@endsection

@extends('backend.partials.master')
@section('title', 'Create Branch')
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Create New Branch</h2>
                    <p class="text-muted mb-0">Add a new branch to your network</p>
                </div>
                <div>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-9">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form id="branchForm" method="POST" action="{{ route('branches.store') }}">
                        @csrf
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">Branch Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Unique identifier for this branch</small>
                            </div>

                            <div class="col-md-4">
                                <label for="type" class="form-label">Branch Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    @foreach($branchTypes as $type)
                                        <option value="{{ $type }}" {{ old('type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="parent_branch_id" class="form-label">Parent Branch</label>
                                <select class="form-select @error('parent_branch_id') is-invalid @enderror" id="parent_branch_id" name="parent_branch_id">
                                    <option value="">None (Root Branch)</option>
                                    @foreach($potentialParents as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_branch_id') == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->name }} ({{ $parent->type }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="is_hub" class="form-label">Is Central Hub?</label>
                                <select class="form-select @error('is_hub') is-invalid @enderror" id="is_hub" name="is_hub">
                                    <option value="0" {{ old('is_hub') == '0' ? 'selected' : '' }}>No</option>
                                    <option value="1" {{ old('is_hub') == '1' ? 'selected' : '' }}>Yes</option>
                                </select>
                                @error('is_hub')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only one HUB allowed per system</small>
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="2">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="latitude" class="form-label">Latitude</label>
                                <input type="number" step="0.00000001" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}">
                                @error('latitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-2">
                                <label for="longitude" class="form-label">Longitude</label>
                                <input type="number" step="0.00000001" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}">
                                @error('longitude')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('branches.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Branch
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Branch Types</h6>
                    <div class="mb-3">
                        <span class="badge bg-primary mb-2">HUB</span>
                        <p class="small text-muted mb-0">Central hub for all operations. Only one allowed.</p>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-info mb-2">REGIONAL</span>
                        <p class="small text-muted mb-0">Regional branch serving multiple local branches.</p>
                    </div>
                    <div>
                        <span class="badge bg-success mb-2">LOCAL</span>
                        <p class="small text-muted mb-0">Local branch serving a specific area.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('branchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
    
    fetch('{{ route("branches.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '{{ route("branches.index") }}';
        } else {
            alert(data.message || 'Failed to create branch');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Branch';
        }
    })
    .catch(error => {
        alert('An error occurred while creating the branch');
        console.error('Error:', error);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Create Branch';
    });
});
</script>
@endpush
@endsection

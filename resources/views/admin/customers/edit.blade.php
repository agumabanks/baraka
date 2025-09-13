@extends('backend.partials.master')
@section('title')
    Edit Customer
@endsection
@section('maincontent')
<div class="container-fluid dashboard-content">
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="page-header">
                <div class="page-breadcrumb">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}" class="breadcrumb-link">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('admin.customers.index') }}" class="breadcrumb-link">Customers</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="customer-edit-form">
                        @csrf
                        <input type="hidden" name="_method" value="PUT" />
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ $customer->name }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="{{ $customer->email }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="{{ $customer->phone }}" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hub_id">Branch</label>
                                <select class="form-control" id="hub_id" name="hub_id">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $customer->hub_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="pickup_address">Pickup Address</label>
                                <input type="text" class="form-control" id="pickup_address" name="pickup_address" value="{{ $customer->pickup_address }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="delivery_address">Delivery Address</label>
                                <input type="text" class="form-control" id="delivery_address" name="delivery_address" value="{{ $customer->delivery_address }}">
                            </div>
                            <div class="form-group col-md-12">
                                <label for="notes">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ $customer->notes }}</textarea>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="status">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="ACTIVE" {{ ($customer->status ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                                    <option value="INACTIVE" {{ ($customer->status ?? '') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('customer-edit-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    try {
        const res = await fetch("{{ route('admin.customers.update', $customer) }}", {
            method: 'POST', // Using POST with _method=PUT
            headers: { 'X-CSRF-TOKEN': data.get('_token') },
            body: data
        });
        const json = await res.json();
        if (json.success) {
            window.location.href = "{{ route('admin.customers.show', $customer) }}";
        } else {
            alert(json.message || 'Failed to update customer');
        }
    } catch (err) {
        alert('Error updating customer');
        console.error(err);
    }
});
</script>
@endsection


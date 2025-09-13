@extends('backend.partials.master')
@section('title')
    Create Customer
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
                            <li class="breadcrumb-item active" aria-current="page">Create</li>
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
                    <form id="customer-create-form">
                        @csrf
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Optional">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="hub_id">Branch</label>
                                <select class="form-control" id="hub_id" name="hub_id">
                                    <option value="">Select branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6"></div>
                            <div class="form-group col-md-6">
                                <label for="pickup_address">Pickup Address</label>
                                <input type="text" class="form-control" id="pickup_address" name="pickup_address">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="delivery_address">Delivery Address</label>
                                <input type="text" class="form-control" id="delivery_address" name="delivery_address">
                            </div>
                            <div class="form-group col-md-12">
                                <label for="notes">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('customer-create-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    try {
        const res = await fetch("{{ route('admin.customers.store') }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': data.get('_token') },
            body: data
        });
        const json = await res.json();
        if (json.success && json.redirect) {
            window.location.href = json.redirect;
        } else {
            alert(json.message || 'Failed to create customer');
        }
    } catch (err) {
        alert('Error creating customer');
        console.error(err);
    }
});
</script>
@endsection


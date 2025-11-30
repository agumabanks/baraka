@extends('branch.layout')

@section('title', 'Create Manifest')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create Manifest</h1>
        <a href="{{ route('branch.manifests.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('branch.manifests.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mode">Transport Mode</label>
                            <select name="mode" id="mode" class="form-control" required>
                                <option value="road">Road</option>
                                <option value="air">Air</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Manifest Type</label>
                            <select name="type" id="type" class="form-control" required>
                                <option value="INTERNAL">Internal Fleet</option>
                                <option value="3PL">Third Party (3PL)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="destination_branch_id">Destination Branch</label>
                            <select name="destination_branch_id" id="destination_branch_id" class="form-control" required>
                                <option value="">Select Destination</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="departure_at">Scheduled Departure</label>
                            <input type="datetime-local" name="departure_at" id="departure_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                </div>

                <hr>
                <h6 class="font-weight-bold text-primary">Resource Assignment (Optional)</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="driver_id">Driver</label>
                            <select name="driver_id" id="driver_id" class="form-control">
                                <option value="">Select Driver</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->status }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="vehicle_id">Vehicle</label>
                            <select name="vehicle_id" id="vehicle_id" class="form-control">
                                <option value="">Select Vehicle</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate_no }} ({{ $vehicle->model }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create Manifest</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@extends('branch.layout')

@section('title', 'Consolidation Rules')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Consolidation Rules</h1>
        <a href="{{ route('branch.consolidations.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Active Rules</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Destination</th>
                            <th>Conditions</th>
                            <th>Cutoff</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rules as $rule)
                        <tr>
                            <td>{{ $rule->priority }}</td>
                            <td>{{ $rule->name }}</td>
                            <td>
                                <span class="badge badge-{{ $rule->consolidation_type === 'BBX' ? 'info' : 'warning' }}">
                                    {{ $rule->consolidation_type }}
                                </span>
                            </td>
                            <td>
                                @if($rule->destinationBranch)
                                    <i class="fas fa-building"></i> {{ $rule->destinationBranch->name }}
                                @elseif($rule->destination_city)
                                    <i class="fas fa-city"></i> {{ $rule->destination_city }}
                                @else
                                    <i class="fas fa-globe"></i> {{ $rule->destination_country }}
                                @endif
                            </td>
                            <td>
                                <ul class="list-unstyled mb-0 text-sm">
                                    @if($rule->max_weight_kg) <li>Max Weight: {{ $rule->max_weight_kg }}kg</li> @endif
                                    @if($rule->max_pieces) <li>Max Pieces: {{ $rule->max_pieces }}</li> @endif
                                    @if($rule->service_level) <li>Service: {{ $rule->service_level }}</li> @endif
                                </ul>
                            </td>
                            <td>{{ $rule->cutoff_time ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-{{ $rule->is_active ? 'success' : 'secondary' }}">
                                    {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No consolidation rules defined.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <small class="text-muted">Rules are processed in order of priority (lower number = higher priority).</small>
            </div>
        </div>
    </div>
</div>
@endsection

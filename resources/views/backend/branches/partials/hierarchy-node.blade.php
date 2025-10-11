<div class="branch-node" style="margin-left: {{ $level * 20 }}px">
    <div class="branch-card {{ strtolower($branch['type'] ?? '') }}">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <h5 class="mb-0 fw-bold">{{ $branch['name'] }}</h5>
                    @if($branch['is_hub'] ?? false)
                        <span class="badge bg-primary">HUB</span>
                    @else
                        <span class="badge bg-{{ $branch['type'] === 'REGIONAL' ? 'info' : 'secondary' }}">
                            {{ $branch['type'] ?? 'N/A' }}
                        </span>
                    @endif
                    @if(($branch['status'] ?? 1) == 1)
                        <i class="fas fa-check-circle text-success" title="Active"></i>
                    @else
                        <i class="fas fa-times-circle text-danger" title="Inactive"></i>
                    @endif
                </div>
                
                <div class="text-muted small mb-2">
                    <strong>Code:</strong> {{ $branch['code'] ?? 'N/A' }}
                </div>

                <div class="branch-stats">
                    @if(isset($branch['managers_count']))
                        <div class="stat-item">
                            <i class="fas fa-user-tie"></i>
                            <span>{{ $branch['managers_count'] }} {{ Str::plural('Manager', $branch['managers_count']) }}</span>
                        </div>
                    @endif
                    
                    @if(isset($branch['workers_count']))
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span>{{ $branch['workers_count'] }} {{ Str::plural('Worker', $branch['workers_count']) }}</span>
                        </div>
                    @endif

                    @if(isset($branch['capacity_utilization']))
                        <div class="stat-item">
                            <i class="fas fa-chart-line"></i>
                            <span>{{ number_format($branch['capacity_utilization'], 1) }}% Capacity</span>
                        </div>
                    @endif

                    @if(isset($branch['level']))
                        <div class="stat-item">
                            <i class="fas fa-layer-group"></i>
                            <span>Level {{ $branch['level'] }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="branch-actions">
                @if(!empty($branch['children']))
                    <button class="toggle-children" data-branch-id="{{ $branch['id'] }}">
                        <i class="fas fa-minus"></i>
                    </button>
                @endif
                
                <a href="{{ route('admin.branches.show', $branch['id']) }}" 
                   class="btn btn-sm btn-outline-primary" 
                   title="View Details">
                    <i class="fas fa-eye"></i>
                </a>
                
                <a href="{{ route('admin.branches.edit', $branch['id']) }}" 
                   class="btn btn-sm btn-outline-secondary" 
                   title="Edit">
                    <i class="fas fa-edit"></i>
                </a>
            </div>
        </div>
    </div>

    @if(!empty($branch['children']))
        <div class="branch-children" id="children-{{ $branch['id'] }}">
            @foreach($branch['children'] as $child)
                @include('backend.branches.partials.hierarchy-node', ['branch' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

@extends('backend.partials.master')
@section('title', 'Branch Hierarchy')
@section('maincontent')
<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Branch Hierarchy</h2>
                    <p class="text-muted mb-0">Visualize and manage your branch network structure</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.branches.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Branches
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    @if(empty($tree))
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-sitemap fa-3x mb-3 d-block"></i>
                            <p class="mb-0">No branches found in the hierarchy.</p>
                        </div>
                    @else
                        <div class="hierarchy-container">
                            @foreach($tree as $rootBranch)
                                @include('backend.branches.partials.hierarchy-node', ['branch' => $rootBranch, 'level' => 0])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Legend</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-primary me-2">HUB</span>
                                <span class="text-muted small">Hub Branch</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-info me-2">REGIONAL</span>
                                <span class="text-muted small">Regional Branch</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-secondary me-2">LOCAL</span>
                                <span class="text-muted small">Local Branch</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span class="text-muted small">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hierarchy-container {
    padding: 20px;
}

.branch-node {
    margin-bottom: 15px;
}

.branch-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: #fff;
    transition: all 0.3s ease;
    position: relative;
}

.branch-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
}

.branch-card.hub {
    border-color: #0d6efd;
    background: linear-gradient(135deg, #f8f9ff 0%, #fff 100%);
}

.branch-card.regional {
    border-color: #0dcaf0;
    background: linear-gradient(135deg, #f8fcfe 0%, #fff 100%);
}

.branch-card.local {
    border-color: #6c757d;
}

.branch-children {
    margin-left: 40px;
    margin-top: 15px;
    padding-left: 20px;
    border-left: 3px solid #e0e0e0;
    position: relative;
}

.branch-children::before {
    content: '';
    position: absolute;
    left: -3px;
    top: 0;
    width: 20px;
    height: 30px;
    border-left: 3px solid #e0e0e0;
    border-bottom: 3px solid #e0e0e0;
    border-bottom-left-radius: 8px;
}

.branch-stats {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items-center;
    gap: 5px;
    font-size: 0.875rem;
    color: #6c757d;
}

.stat-item i {
    width: 16px;
    text-align: center;
}

.toggle-children {
    cursor: pointer;
    padding: 2px 8px;
    font-size: 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background: #f8f9fa;
    transition: all 0.2s;
}

.toggle-children:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.branch-actions {
    display: flex;
    gap: 5px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle children visibility
    document.querySelectorAll('.toggle-children').forEach(button => {
        button.addEventListener('click', function() {
            const branchId = this.dataset.branchId;
            const childrenContainer = document.getElementById('children-' + branchId);
            
            if (childrenContainer) {
                if (childrenContainer.style.display === 'none') {
                    childrenContainer.style.display = 'block';
                    this.innerHTML = '<i class="fas fa-minus"></i>';
                } else {
                    childrenContainer.style.display = 'none';
                    this.innerHTML = '<i class="fas fa-plus"></i>';
                }
            }
        });
    });
});
</script>
@endsection

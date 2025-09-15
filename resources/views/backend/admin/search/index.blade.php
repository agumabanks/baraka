<div class="container-fluid py-4">
  <div class="row align-items-center mb-3">
    <div class="col-lg-8">
      <h3 class="mb-0">Global Search</h3>
      <small class="text-muted">Track shipments, find customers, scan events</small>
    </div>
    <div class="col-lg-4">
      <form method="get" action="{{ route('admin.search') }}">
        <div class="input-group">
          <span class="input-group-text"><i class="fa fa-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search tracking, SSCC, name, phone" autofocus />
          <button class="btn btn-primary" type="submit">Search</button>
        </div>
      </form>
    </div>
  </div>

  @if($q === '')
    <div class="alert alert-info">Enter a query to search across shipments and customers.</div>
  @else
    <div class="d-flex justify-content-between align-items-center mb-2">
      <p class="text-muted mb-0">Found {{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }} for “{{ $q }}”.</p>
    </div>

    @if($results->isEmpty())
      <div class="card border-0 bg-light text-center py-5"><div class="card-body">
        <i class="fa fa-search fa-2x text-muted mb-2"></i>
        <div class="text-muted">No results. Try a tracking ID or customer name.</div>
      </div></div>
    @else
      <div class="row g-3">
        @foreach($results as $row)
          <div class="col-12 col-md-6 col-xl-4">
            <a href="{{ $row->url ?? '#' }}" class="text-decoration-none">
              <div class="card h-100 shadow-sm">
                <div class="card-body d-flex">
                  <div class="me-3 text-primary">
                    <i class="fa {{ $row->type === 'shipment' ? 'fa-box' : 'fa-database' }} fa-lg"></i>
                  </div>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between">
                      <span class="badge bg-secondary text-uppercase">{{ $row->type }}</span>
                      @if(!empty($row->current_status))
                        <span class="badge bg-light text-dark">{{ $row->current_status }}</span>
                      @endif
                    </div>
                    <div class="fw-semibold mt-1">{{ $row->title ?? ($row->tracking ?? ('#'.$row->id)) }}</div>
                    @if(!empty($row->subtitle))
                      <div class="text-muted small">{{ $row->subtitle }}</div>
                    @endif
                  </div>
                </div>
              </div>
            </a>
          </div>
        @endforeach
      </div>
    @endif
  @endif
</div>

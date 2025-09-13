@php($title = 'Cash Office')
<div class="container py-4">
  <h3>{{ $title }}</h3>
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.cash-office.store') }}" class="row g-3 mb-4">
    @csrf
    <div class="col-md-3">
      <label class="form-label">Business Date</label>
      <input type="date" class="form-control" name="business_date" value="{{ now()->toDateString() }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">COD Collected</label>
      <input type="number" step="0.01" class="form-control" name="cod_collected" value="0.00">
    </div>
    <div class="col-md-3">
      <label class="form-label">Cash On Hand</label>
      <input type="number" step="0.01" class="form-control" name="cash_on_hand" value="0.00">
    </div>
    <div class="col-md-3">
      <label class="form-label">Banked Amount</label>
      <input type="number" step="0.01" class="form-control" name="banked_amount" value="0.00">
    </div>
    <div class="col-12">
      <button class="btn btn-primary" type="submit">Submit</button>
    </div>
  </form>

  <div class="card">
    <div class="card-header">Recent Days</div>
    <div class="card-body">
      <ul class="list-group">
        @forelse($items as $day)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>{{ $day->business_date->format('Y-m-d') }}</span>
            <span>Variance: {{ number_format($day->variance, 2) }}</span>
          </li>
        @empty
          <li class="list-group-item">No records yet.</li>
        @endforelse
      </ul>
      <div class="mt-3">{{ $items->links() }}</div>
    </div>
  </div>
</div>


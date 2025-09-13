@php($title = 'Create Quotation')
<div class="container py-4">
  <h3>{{ $title }}</h3>
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
    </div>
  @endif
  <form method="POST" action="{{ route('admin.quotations.store') }}" class="row g-3">
    @csrf
    <div class="col-md-4">
      <label class="form-label">Customer</label>
      <select name="customer_id" class="form-control">
        @foreach($customers as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Dest. Country</label>
      <input type="text" name="destination_country" maxlength="2" class="form-control" value="KE">
    </div>
    <div class="col-md-3">
      <label class="form-label">Service</label>
      <input type="text" name="service_type" class="form-control" value="EXPRESS">
    </div>
    <div class="col-md-3">
      <label class="form-label">Currency</label>
      <input type="text" name="currency" maxlength="3" class="form-control" value="USD">
    </div>
    <div class="col-md-2">
      <label class="form-label">Pieces</label>
      <input type="number" name="pieces" class="form-control" value="1" min="1">
    </div>
    <div class="col-md-2">
      <label class="form-label">Weight (kg)</label>
      <input type="number" step="0.001" name="weight_kg" class="form-control" value="1.000" min="0.001">
    </div>
    <div class="col-md-3">
      <label class="form-label">Volume (cm³)</label>
      <input type="number" name="volume_cm3" class="form-control" placeholder="e.g., 30000">
    </div>
    <div class="col-md-2">
      <label class="form-label">Dim Factor</label>
      <input type="number" name="dim_factor" class="form-control" value="5000">
    </div>
    <div class="col-md-3">
      <label class="form-label">Base Charge</label>
      <input type="number" step="0.01" name="base_charge" class="form-control" value="10.00">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-primary">Save Quotation</button>
      <a href="{{ route('admin.quotations.index') }}" class="btn btn-link">Cancel</a>
    </div>
  </form>
</div>


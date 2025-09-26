@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit AWB Stock</h3>
  <form method="post" action="{{ route('admin.awb-stock.update',$stock) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-2"><label class="form-label">Used</label><input type="number" name="used_count" class="form-control" value="{{ $stock->used_count }}"></div>
      <div class="col-md-2"><label class="form-label">Voided</label><input type="number" name="voided_count" class="form-control" value="{{ $stock->voided_count }}"></div>
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          @foreach(['active','exhausted','voided'] as $s)
            <option value="{{ $s }}" @selected($stock->status==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.awb-stock.index') }}">Cancel</a></div>
  </form>
</div>
@endsection


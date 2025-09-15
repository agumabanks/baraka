@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Quotation #{{ $quotation->id }}</h3>
  <form method="post" action="{{ route('admin.quotations.update',$quotation) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          @foreach(['draft','sent','accepted','expired'] as $s)
            <option value="{{ $s }}" @selected($quotation->status==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Valid Until</label><input type="date" class="form-control" name="valid_until" value="{{ optional($quotation->valid_until)->format('Y-m-d') }}"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.quotations.show',$quotation) }}">Cancel</a></div>
  </form>
</div>
@endsection


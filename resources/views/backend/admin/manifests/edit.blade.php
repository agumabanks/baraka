@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Edit Manifest</h3>
  <form method="post" action="{{ route('admin.manifests.update',$manifest) }}">@csrf @method('PUT')
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          @foreach(['open','departed','arrived','closed'] as $s)
            <option value="{{ $s }}" @selected($manifest->status==$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3"><label class="form-label">Arrival At</label><input type="datetime-local" class="form-control" name="arrival_at" value="{{ optional($manifest->arrival_at)->format('Y-m-d\\TH:i') }}"></div>
    </div>
    <div class="mt-3"><button class="btn btn-primary">Update</button> <a class="btn btn-secondary" href="{{ route('admin.manifests.show',$manifest) }}">Cancel</a></div>
  </form>
</div>
@endsection


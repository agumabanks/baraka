@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Denied-Party Screening</h3>
  <form method="post" action="{{ route('admin.dps.run') }}">@csrf
    <div class="input-group mb-3">
      <input name="query" class="form-control" placeholder="Name to screen">
      <button class="btn btn-primary">Run Screening</button>
    </div>
  </form>
  <p class="text-muted">Results appear in KYC record history.</p>
</div>
@endsection


@extends('backend.partials.master')
@section('maincontent')
<div class="container"><h3>Observability</h3>
  <p>Failed jobs: <strong>{{ $failedJobs }}</strong></p>
</div>
@endsection


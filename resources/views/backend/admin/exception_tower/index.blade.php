@extends('backend.partials.master')
@section('maincontent')
<div class="container"><h3>Exception Tower</h3>
  <p>Stale scans (> {{ $staleThreshold->diffForHumans() }}): <strong>{{ $staleCount }}</strong></p>
</div>
@endsection


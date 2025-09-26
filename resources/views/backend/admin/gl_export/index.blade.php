@extends('backend.partials.master')
@section('maincontent')
<div class="container"><h3>GL Export</h3>
  <a class="btn btn-primary btn-sm" href="{{ route('admin.gl-export.csv') }}">Download CSV</a>
</div>
@endsection


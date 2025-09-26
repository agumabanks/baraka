@extends('backend.partials.master')
@section('maincontent')
<div class="container">
  <h3>Create WhatsApp Template</h3>
  <form method="post" action="{{ route('admin.whatsapp-templates.store') }}">@csrf
    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
    <div class="form-group"><label>Language</label><input name="language" class="form-control" value="en"></div>
    <div class="form-group"><label>Body</label><textarea name="body" class="form-control" rows="5"></textarea></div>
    <div class="form-group form-check"><input class="form-check-input" type="checkbox" name="approved" value="1" id="approved"><label class="form-check-label" for="approved">Approved</label></div>
    <button class="btn btn-primary">Save</button>
  </form>
</div>
@endsection


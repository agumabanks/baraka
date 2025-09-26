@if(session()->has('impersonator_id'))
  <div class="impersonation-banner" data-impersonation-banner style="position:fixed;top:0;left:0;right:0;z-index:1050;background:#ffcc00;color:#222;padding:8px 16px;display:flex;align-items:center;justify-content:space-between;">
    <div>
      <strong>Impersonation Mode:</strong>
      You are acting as {{ Auth::user()->name }}
      (started at {{ session('impersonation_started_at') }})
    </div>
    <form action="{{ route('admin.impersonate.stop') }}" method="POST" style="margin:0;">
      @csrf
      <button class="btn btn-sm btn-dark">Stop Impersonating</button>
    </form>
  </div>
  <div style="height:40px;"></div>
@endif

<div class="container py-4">
    <h3>Global Search</h3>
    <form method="get" action="{{ route('admin.search') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search tracking, SSCC, name, phone" />
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>
    @if($q === '')
        <p class="text-muted">Enter a query to search.</p>
    @else
        <p class="text-muted">Found {{ $results->count() }} results for "{{ $q }}"</p>
        <ul class="list-group">
            @foreach($results as $row)
                <li class="list-group-item">
                    <strong>[{{ $row->type }}]</strong>
                    @if(isset($row->tracking)) Tracking: <code>{{ $row->tracking }}</code> @endif
                    @if(isset($row->customer)) — {{ $row->customer }} @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>


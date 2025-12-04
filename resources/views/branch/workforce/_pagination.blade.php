<div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 bg-zinc-800/50 rounded-lg border border-white/10">
    <div class="flex items-center gap-4">
        <div class="text-sm text-zinc-400">
            @if($workers->total() > 0)
                Showing {{ $workers->firstItem() }} to {{ $workers->lastItem() }} of {{ $workers->total() }} workers
            @else
                No workers found
            @endif
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-500">Show:</span>
            <select id="perPageSelect" onchange="changePerPage(this.value)" class="bg-zinc-700 border border-white/10 rounded px-2 py-1 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
                <option value="12" @selected(($perPage ?? 12) == 12)>12</option>
                <option value="25" @selected(($perPage ?? 12) == 25)>25</option>
                <option value="50" @selected(($perPage ?? 12) == 50)>50</option>
                <option value="100" @selected(($perPage ?? 12) == 100)>100</option>
            </select>
        </div>
    </div>
    
    @if($workers->lastPage() > 1)
        <div class="flex items-center gap-2">
            @if($workers->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-zinc-600 cursor-not-allowed">Previous</span>
            @else
                <a href="{{ $workers->previousPageUrl() }}&per_page={{ $perPage ?? 12 }}" class="px-3 py-1.5 text-sm bg-zinc-700 hover:bg-zinc-600 rounded-lg transition-colors">Previous</a>
            @endif
            
            <div class="flex items-center gap-1">
                @foreach($workers->getUrlRange(max(1, $workers->currentPage() - 2), min($workers->lastPage(), $workers->currentPage() + 2)) as $page => $url)
                    @if($page == $workers->currentPage())
                        <span class="px-3 py-1.5 text-sm bg-emerald-600 rounded-lg">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}&per_page={{ $perPage ?? 12 }}" class="px-3 py-1.5 text-sm bg-zinc-700 hover:bg-zinc-600 rounded-lg transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
            </div>
            
            @if($workers->hasMorePages())
                <a href="{{ $workers->nextPageUrl() }}&per_page={{ $perPage ?? 12 }}" class="px-3 py-1.5 text-sm bg-zinc-700 hover:bg-zinc-600 rounded-lg transition-colors">Next</a>
            @else
                <span class="px-3 py-1.5 text-sm text-zinc-600 cursor-not-allowed">Next</span>
            @endif
        </div>
    @endif
</div>

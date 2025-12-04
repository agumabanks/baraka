<div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 border-t border-white/10">
    <div class="flex items-center gap-4">
        <div class="text-sm text-zinc-400">
            @if($branches->total() > 0)
                Showing {{ $branches->firstItem() }} to {{ $branches->lastItem() }} of {{ $branches->total() }} branches
            @else
                No branches found
            @endif
        </div>
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-500">Show:</span>
            <select id="perPageSelect" onchange="changePerPage(this.value)" class="bg-white/5 border border-white/10 rounded px-2 py-1 text-sm focus:border-sky-500 focus:ring-1 focus:ring-sky-500">
                <option value="10" @selected(($perPage ?? 10) == 10)>10</option>
                <option value="25" @selected(($perPage ?? 10) == 25)>25</option>
                <option value="50" @selected(($perPage ?? 10) == 50)>50</option>
                <option value="100" @selected(($perPage ?? 10) == 100)>100</option>
            </select>
        </div>
    </div>
    
    @if($branches->lastPage() > 1)
        <div class="flex items-center gap-2">
            @if($branches->onFirstPage())
                <span class="px-3 py-1.5 text-sm text-zinc-600 cursor-not-allowed">Previous</span>
            @else
                <a href="{{ $branches->previousPageUrl() }}&per_page={{ $perPage ?? 10 }}" class="pagination-link px-3 py-1.5 text-sm bg-white/5 hover:bg-white/10 rounded-lg transition-colors">Previous</a>
            @endif
            
            <div class="flex items-center gap-1">
                @foreach($branches->getUrlRange(max(1, $branches->currentPage() - 2), min($branches->lastPage(), $branches->currentPage() + 2)) as $page => $url)
                    @if($page == $branches->currentPage())
                        <span class="px-3 py-1.5 text-sm bg-sky-600 rounded-lg">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}&per_page={{ $perPage ?? 10 }}" class="pagination-link px-3 py-1.5 text-sm bg-white/5 hover:bg-white/10 rounded-lg transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
            </div>
            
            @if($branches->hasMorePages())
                <a href="{{ $branches->nextPageUrl() }}&per_page={{ $perPage ?? 10 }}" class="pagination-link px-3 py-1.5 text-sm bg-white/5 hover:bg-white/10 rounded-lg transition-colors">Next</a>
            @else
                <span class="px-3 py-1.5 text-sm text-zinc-600 cursor-not-allowed">Next</span>
            @endif
        </div>
    @endif
</div>

<div class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="text-sm text-zinc-400">
        Showing <span class="font-medium text-white">{{ $users->firstItem() ?? 0 }}</span>
        to <span class="font-medium text-white">{{ $users->lastItem() ?? 0 }}</span>
        of <span class="font-medium text-white">{{ number_format($users->total()) }}</span> users
    </div>
    
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-400">Per page:</span>
            <select id="perPageSelect" onchange="changePerPage(this.value)" 
                    class="bg-white/5 border border-white/10 rounded px-2 py-1 text-sm focus:border-sky-500">
                @foreach([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" {{ ($perPage ?? 10) == $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        
        @if($users->hasPages())
            <div class="flex items-center gap-1">
                @if($users->onFirstPage())
                    <span class="px-3 py-1 text-sm text-zinc-600 cursor-not-allowed">&laquo; Prev</span>
                @else
                    <button onclick="goToPage({{ $users->currentPage() - 1 }})" class="px-3 py-1 text-sm text-zinc-400 hover:text-white hover:bg-white/10 rounded transition">&laquo; Prev</button>
                @endif
                
                @php
                    $start = max(1, $users->currentPage() - 2);
                    $end = min($users->lastPage(), $users->currentPage() + 2);
                @endphp
                
                @if($start > 1)
                    <button onclick="goToPage(1)" class="px-3 py-1 text-sm text-zinc-400 hover:text-white hover:bg-white/10 rounded transition">1</button>
                    @if($start > 2)
                        <span class="px-2 text-zinc-600">...</span>
                    @endif
                @endif
                
                @for($i = $start; $i <= $end; $i++)
                    <button onclick="goToPage({{ $i }})" 
                            class="px-3 py-1 text-sm rounded transition {{ $i == $users->currentPage() ? 'bg-sky-500 text-white' : 'text-zinc-400 hover:text-white hover:bg-white/10' }}">
                        {{ $i }}
                    </button>
                @endfor
                
                @if($end < $users->lastPage())
                    @if($end < $users->lastPage() - 1)
                        <span class="px-2 text-zinc-600">...</span>
                    @endif
                    <button onclick="goToPage({{ $users->lastPage() }})" class="px-3 py-1 text-sm text-zinc-400 hover:text-white hover:bg-white/10 rounded transition">{{ $users->lastPage() }}</button>
                @endif
                
                @if($users->hasMorePages())
                    <button onclick="goToPage({{ $users->currentPage() + 1 }})" class="px-3 py-1 text-sm text-zinc-400 hover:text-white hover:bg-white/10 rounded transition">Next &raquo;</button>
                @else
                    <span class="px-3 py-1 text-sm text-zinc-600 cursor-not-allowed">Next &raquo;</span>
                @endif
            </div>
        @endif
    </div>
</div>

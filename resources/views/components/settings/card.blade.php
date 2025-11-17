{{-- Settings Card Component --}}
<div class="settings-card fade-in">
    @if(isset($title) || isset($subtitle))
        <div class="settings-card-header">
            @if(isset($title))
                <h5 class="settings-card-title">
                    <i class="{{ $icon ?? 'fas fa-cog' }} me-2 text-primary"></i>
                    {{ $title }}
                </h5>
            @endif
            @if(isset($subtitle))
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    
    <div class="settings-card-body">
        {{ $slot }}
    </div>
</div>

{{-- Alternative compact version without header --}}
@if(!isset($title) && !isset($subtitle))
    <div class="settings-card fade-in">
        <div class="settings-card-body">
            {{ $slot }}
        </div>
    </div>
@endif
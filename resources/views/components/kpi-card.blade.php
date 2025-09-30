@props([
    'title' => '',
    'value' => null,
    'subtitle' => '',
    'icon' => 'fas fa-chart-line',
    'state' => 'loading', // loading, success, warning, error, empty
    'drilldownRoute' => null,
    'drilldownLabel' => 'View Details',
    'tooltip' => null,
    'trend' => null, // ['value' => 5.2, 'direction' => 'up|down|stable']
    'kpi' => null // data-kpi attribute
])

@php
    $trendDirection = $trend['direction'] ?? 'stable';
    $trendValue = $trend['value'] ?? 0;
    $trendColor = match($trendDirection) {
        'up' => 'var(--success)',
        'down' => 'var(--error)',
        default => 'var(--neutral-500)'
    };
@endphp

<div class="kpi-card" data-kpi="{{ $kpi }}" @if($state === 'loading') data-loading="true" @endif @if($tooltip) title="{{ $tooltip }}" @endif @if($state === 'loading') aria-live="polite" aria-label="Loading {{ $title }}" @endif>
    <div class="kpi-header">
        <h3 class="kpi-title">
            @if($state === 'loading')
                <div class="skeleton skeleton-title"></div>
            @else
                {{ $title }}
            @endif
        </h3>
        @if($trend && $state !== 'loading')
            <div class="kpi-trend">
                <span class="trend-indicator" style="color: {{ $trendColor }};">
                    @if($trendDirection === 'up') ↑ @elseif($trendDirection === 'down') ↓ @else → @endif
                </span>
                <span class="trend-value" style="color: {{ $trendColor }};">
                    @if($trendDirection === 'up') + @elseif($trendDirection === 'down') - @endif{{ abs($trendValue) }}%
                </span>
            </div>
        @endif
    </div>

    <div class="kpi-value">
        @if($state === 'loading')
            <div class="skeleton skeleton-value"></div>
        @elseif($state === 'empty')
            <span class="text-muted">--</span>
        @else
            {{ $value }}
        @endif
    </div>

    <div class="kpi-subtitle">
        @if($state === 'loading')
            <div class="skeleton skeleton-subtitle"></div>
        @elseif($subtitle)
            {{ $subtitle }}
        @endif
    </div>

    @if($drilldownRoute && $state !== 'loading')
        <a href="{{ $drilldownRoute }}"
           class="kpi-drilldown btn btn-sm btn-outline-primary"
           aria-label="{{ $drilldownLabel }}"
           data-drilldown="{{ json_encode([
               'url' => $drilldownRoute,
               'title' => $title,
               'kpi' => $kpi,
               'filters' => request()->all()
           ]) }}"
           data-breadcrumb-update="true">
            {{ $drilldownLabel }}
        </a>
    @endif
</div>

<style>
.kpi-card {
    background: var(--neutral-50);
    border: 1px solid var(--neutral-200);
    border-radius: var(--border-radius-base);
    padding: var(--spacing-lg);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-500);
}

.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-md);
}

.kpi-title {
    font-size: var(--font-size-h3);
    font-weight: 600;
    color: var(--neutral-900);
    margin: 0;
    line-height: 1.2;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    font-size: var(--font-size-caption);
    font-weight: 600;
}

.trend-indicator {
    font-size: var(--font-size-body-small);
}

.trend-value {
    font-size: var(--font-size-caption);
}

.kpi-value {
    font-size: var(--font-size-display);
    font-weight: 700;
    color: var(--neutral-900);
    margin-bottom: var(--spacing-sm);
    line-height: 1;
}

.kpi-subtitle {
    font-size: var(--font-size-body-small);
    color: var(--neutral-500);
    margin-bottom: var(--spacing-md);
    line-height: 1.3;
}

.kpi-drilldown {
    align-self: flex-start;
    font-size: var(--font-size-caption);
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-small);
    transition: all 0.2s ease;
}

.kpi-drilldown:hover {
    transform: scale(var(--state-active-scale));
}

.kpi-drilldown:focus {
    outline: var(--focus-ring);
}

/* Progressive enhancement animations */
.kpi-card[data-loading="true"] .kpi-title,
.kpi-card[data-loading="true"] .kpi-value,
.kpi-card[data-loading="true"] .kpi-subtitle {
    animation: fade-in-up 0.5s ease-out forwards;
}

.kpi-card[data-loading="true"] .kpi-title {
    animation-delay: 0.1s;
}

.kpi-card[data-loading="true"] .kpi-value {
    animation-delay: 0.2s;
}

.kpi-card[data-loading="true"] .kpi-subtitle {
    animation-delay: 0.3s;
}

.kpi-card[data-loading="true"] .kpi-drilldown {
    animation: fade-in-up 0.5s ease-out 0.4s forwards;
    opacity: 0;
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Skeleton loading styles */
.skeleton {
    background: linear-gradient(90deg, var(--neutral-200) 25%, var(--neutral-100) 50%, var(--neutral-200) 75%);
    background-size: 200% 100%;
    animation: skeleton-loading 1.5s infinite;
    border-radius: var(--border-radius-small);
}

.skeleton-title {
    height: 16px;
    width: 80%;
    margin-bottom: var(--spacing-xs);
}

.skeleton-value {
    height: 40px;
    width: 60%;
    margin-bottom: var(--spacing-xs);
}

.skeleton-subtitle {
    height: 14px;
    width: 70%;
}

/* Staggered skeleton animation */
.kpi-card[aria-live] .skeleton-title {
    animation-delay: 0s;
}

.kpi-card[aria-live] .skeleton-value {
    animation-delay: 0.1s;
}

.kpi-card[aria-live] .skeleton-subtitle {
    animation-delay: 0.2s;
}

@keyframes skeleton-loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Responsive design */
@media (max-width: 768px) {
    .kpi-card {
        padding: var(--spacing-md);
        min-height: 120px;
    }

    .kpi-title {
        font-size: var(--font-size-body-large);
    }

    .kpi-value {
        font-size: var(--font-size-h1);
    }

    .kpi-subtitle {
        font-size: var(--font-size-caption);
    }

    .kpi-drilldown {
        font-size: var(--font-size-caption);
        padding: var(--spacing-xs) var(--spacing-sm);
    }
}

/* Accessibility */
.kpi-card:focus-within {
    outline: var(--focus-ring);
}

.kpi-drilldown {
    position: relative;
}

.kpi-drilldown::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border-radius: var(--border-radius-small);
    background: transparent;
    transition: background 0.2s ease;
}

.kpi-drilldown:focus::before {
    background: var(--primary-500);
    opacity: 0.1;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .kpi-card {
        border: 2px solid var(--neutral-900);
    }

    .kpi-title {
        color: var(--neutral-900);
    }

    .kpi-value {
        color: var(--neutral-900);
    }

    .kpi-subtitle {
        color: var(--neutral-500);
    }
}
</style>
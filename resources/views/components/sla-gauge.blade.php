@props([
    'percentage' => 0,
    'onTime' => 0,
    'total' => 0,
    'change' => 0,
    'size' => 'medium' // small, medium, large
])

@php
    // Determine status based on percentage
    if ($percentage >= 95) {
        $status = 'success';
        $statusColor = '#10b981'; // green-500
        $statusText = 'Excellent';
    } elseif ($percentage >= 90) {
        $status = 'warning';
        $statusColor = '#f59e0b'; // amber-500
        $statusText = 'Good';
    } else {
        $status = 'error';
        $statusColor = '#ef4444'; // red-500
        $statusText = 'Needs Attention';
    }

    // Size configurations
    $sizes = [
        'small' => ['width' => 80, 'stroke' => 6, 'fontSize' => 14],
        'medium' => ['width' => 120, 'stroke' => 8, 'fontSize' => 18],
        'large' => ['width' => 160, 'stroke' => 10, 'fontSize' => 24]
    ];

    $config = $sizes[$size] ?? $sizes['medium'];
    $radius = ($config['width'] - $config['stroke']) / 2;
    $circumference = 2 * pi() * $radius;
    $strokeDasharray = $circumference;
    $strokeDashoffset = $circumference - ($percentage / 100) * $circumference;
@endphp

<div class="sla-gauge sla-gauge--{{ $size }} sla-gauge--{{ $status }}"
     role="img"
     aria-label="SLA Status: {{ $percentage }}% ({{ $onTime }} of {{ $total }} deliveries on time)">
    <svg width="{{ $config['width'] }}" height="{{ $config['width'] }}" viewBox="0 0 {{ $config['width'] }} {{ $config['width'] }}">
        <!-- Background circle -->
        <circle
            cx="{{ $config['width'] / 2 }}"
            cy="{{ $config['width'] / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="currentColor"
            stroke-width="{{ $config['stroke'] }}"
            class="sla-gauge__background"
        />

        <!-- Progress circle -->
        <circle
            cx="{{ $config['width'] / 2 }}"
            cy="{{ $config['width'] / 2 }}"
            r="{{ $radius }}"
            fill="none"
            stroke="{{ $statusColor }}"
            stroke-width="{{ $config['stroke'] }}"
            stroke-linecap="round"
            stroke-dasharray="{{ $strokeDasharray }}"
            stroke-dashoffset="{{ $strokeDashoffset }}"
            class="sla-gauge__progress"
            transform="rotate(-90 {{ $config['width'] / 2 }} {{ $config['width'] / 2 }})"
        />

        <!-- Center text -->
        <text
            x="{{ $config['width'] / 2 }}"
            y="{{ $config['width'] / 2 - 5 }}"
            text-anchor="middle"
            class="sla-gauge__percentage"
            font-size="{{ $config['fontSize'] }}"
            font-weight="700"
            fill="currentColor"
        >
            {{ number_format($percentage, 1) }}%
        </text>

        <text
            x="{{ $config['width'] / 2 }}"
            y="{{ $config['width'] / 2 + 12 }}"
            text-anchor="middle"
            class="sla-gauge__label"
            font-size="{{ $config['fontSize'] * 0.4 }}"
            fill="currentColor"
        >
            SLA
        </text>
    </svg>

    <!-- Status indicator -->
    <div class="sla-gauge__status">
        <div class="sla-gauge__status-dot" style="background-color: {{ $statusColor }}"></div>
        <span class="sla-gauge__status-text">{{ $statusText }}</span>
    </div>

    <!-- Change indicator -->
    @if($change !== 0)
        <div class="sla-gauge__change sla-gauge__change--{{ $change > 0 ? 'positive' : 'negative' }}">
            <i class="fas fa-arrow-{{ $change > 0 ? 'up' : 'down' }}"></i>
            {{ abs($change) }}%
        </div>
    @endif
</div>

<style>
.sla-gauge {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
}

.sla-gauge__background {
    color: #e5e7eb; /* gray-200 */
}

.sla-gauge__progress {
    transition: stroke-dashoffset 1s ease-in-out;
}

.sla-gauge__percentage {
    font-family: inherit;
}

.sla-gauge__label {
    font-family: inherit;
    opacity: 0.7;
}

.sla-gauge__status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.sla-gauge__status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.sla-gauge__status-text {
    color: var(--color-gray-700);
}

.sla-gauge__change {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.sla-gauge__change--positive {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
}

.sla-gauge__change--negative {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
}

/* Size variants */
.sla-gauge--small {
    /* Default small size */
}

.sla-gauge--medium {
    /* Default medium size */
}

.sla-gauge--large {
    /* Default large size */
}

/* Status variants */
.sla-gauge--success .sla-gauge__percentage {
    color: #065f46;
}

.sla-gauge--warning .sla-gauge__percentage {
    color: #92400e;
}

.sla-gauge--error .sla-gauge__percentage {
    color: #991b1b;
}

/* Dark theme support */
@media (prefers-color-scheme: dark) {
    .sla-gauge__background {
        color: rgba(148, 163, 184, 0.2);
    }

    .sla-gauge__percentage,
    .sla-gauge__label {
        fill: var(--color-dark-text);
    }

    .sla-gauge__status-text {
        color: var(--color-dark-text);
    }
}

/* Animation for progress */
@keyframes gauge-fill {
    from {
        stroke-dashoffset: var(--circumference);
    }
    to {
        stroke-dashoffset: var(--stroke-dashoffset);
    }
}

.sla-gauge__progress {
    animation: gauge-fill 1.5s ease-out;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sla-gauge--large {
        --gauge-size: 120px;
    }

    .sla-gauge--medium {
        --gauge-size: 100px;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .sla-gauge__progress {
        animation: none;
    }
}
</style>
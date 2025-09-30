@props([
    'data' => null,
    'loading' => true
])

@php
    if ($loading || !$data) {
        $state = 'loading';
        $percentage = 0;
        $onTime = 0;
        $total = 0;
        $change = 0;
    } else {
        $percentage = $data['percentage'] ?? 0;
        $onTime = $data['on_time'] ?? 0;
        $total = $data['total'] ?? 0;
        $change = $data['change_7d'] ?? 0;

        // Determine state based on percentage
        if ($percentage >= 95) {
            $state = 'success';
        } elseif ($percentage >= 90) {
            $state = 'warning';
        } else {
            $state = 'error';
        }
    }

    $title = "Today's SLA Status";
    $subtitle = $loading ? '' : "{$onTime} of {$total} deliveries on time";
    $href = $loading ? null : route('parcel.filter', ['parcel_date' => today()->format('Y-m-d')]);
@endphp

<div class="sla-status-widget">
    <x-kpi-card
        :title="$title"
        :value="$loading ? null : number_format($percentage, 1) . '%'"
        :subtitle="$subtitle"
        icon="fas fa-tachometer-alt"
        :state="$state"
        :href="$href"
        tooltip="Click to view today's deliveries"
        :loading="$loading"
    >
        <x-slot name="customContent">
            @if(!$loading && $data)
                <div class="sla-gauge-container">
                    <x-sla-gauge
                        :percentage="$percentage"
                        :onTime="$onTime"
                        :total="$total"
                        :change="$change"
                        size="medium"
                    />
                </div>
            @endif
        </x-slot>
    </x-kpi-card>
</div>

<style>
.sla-status-widget {
    height: 100%;
}

.sla-gauge-container {
    margin-top: 1rem;
    display: flex;
    justify-content: center;
}

/* Override KPI card for SLA widget */
.sla-status-widget .kpi-card {
    min-height: 200px;
}

.sla-status-widget .kpi-card__content {
    position: relative;
}

.sla-status-widget .kpi-card__value {
    margin-bottom: 0.5rem;
}

/* Custom content slot positioning */
.sla-status-widget .kpi-card__content::after {
    content: '';
    display: block;
    height: 120px; /* Space for gauge */
}

/* Dark theme adjustments */
@media (prefers-color-scheme: dark) {
    .sla-status-widget .kpi-card {
        background: var(--color-dark-surface);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .sla-status-widget .kpi-card {
        min-height: 180px;
    }

    .sla-gauge-container {
        margin-top: 0.75rem;
    }

    .sla-status-widget .kpi-card__content::after {
        height: 100px;
    }
}
</style>
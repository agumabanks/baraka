<?php

namespace App\Jobs;

use App\Enums\ApprovalStatus;
use App\Enums\ParcelStatus;
use App\Events\KpiUpdated;
use App\Events\QueueItemAdded;
use App\Events\QueueItemCompleted;
use App\Models\Backend\Parcel;
use App\Models\Backend\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessDashboardCalculations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $merchantId;
    protected $calculationType;

    /**
     * Create a new job instance.
     */
    public function __construct($merchantId = null, $calculationType = 'all')
    {
        $this->merchantId = $merchantId;
        $this->calculationType = $calculationType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $calculations = [];

            if ($this->calculationType === 'all' || $this->calculationType === 'kpis') {
                $calculations['kpis'] = $this->calculateKpis();
            }

            if ($this->calculationType === 'all' || $this->calculationType === 'queue') {
                $calculations['queue'] = $this->calculateQueueStats();
            }

            if ($this->calculationType === 'all' || $this->calculationType === 'charts') {
                $calculations['charts'] = $this->calculateChartData();
            }

            // Cache the results
            $cacheKey = $this->merchantId ? "dashboard_calculations_merchant_{$this->merchantId}" : 'dashboard_calculations_global';
            Cache::put($cacheKey, $calculations, now()->addMinutes(30));

            // Broadcast updates if real-time is enabled
            $this->broadcastUpdates($calculations);

        } catch (\Exception $e) {
            \Log::error('Dashboard calculation job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate KPI metrics
     */
    private function calculateKpis()
    {
        $query = Parcel::query();

        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        $kpis = [
            'total_parcels' => (clone $query)->count(),
            'total_delivered' => (clone $query)->where('status', ParcelStatus::DELIVERED)->count(),
            'total_pending' => (clone $query)->where('status', ParcelStatus::PENDING)->count(),
            'total_returns' => (clone $query)->where('status', ParcelStatus::RETURN_RECEIVED_BY_MERCHANT)->count(),
            'total_revenue' => (clone $query)->whereIn('status', [ParcelStatus::DELIVERED, ParcelStatus::PARTIAL_DELIVERED])->sum('cash_collection'),
        ];

        if ($this->merchantId) {
            $kpis['total_balance'] = Payment::where('merchant_id', $this->merchantId)
                ->where('status', ApprovalStatus::PENDING)
                ->sum('amount');
        }

        return $kpis;
    }

    /**
     * Calculate queue statistics
     */
    private function calculateQueueStats()
    {
        $query = Parcel::query();

        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        return [
            'pending_pickup' => (clone $query)->where('status', ParcelStatus::PENDING)->count(),
            'in_transit' => (clone $query)->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count(),
            'out_for_delivery' => (clone $query)->where('status', ParcelStatus::DELIVERY_MAN_ASSIGN)->count(),
            'delivered_today' => (clone $query)->where('status', ParcelStatus::DELIVERED)
                ->whereDate('updated_at', today())->count(),
        ];
    }

    /**
     * Calculate chart data
     */
    private function calculateChartData()
    {
        $query = Parcel::query();

        if ($this->merchantId) {
            $query->where('merchant_id', $this->merchantId);
        }

        // Last 7 days data
        $dates = [];
        $totals = [];
        $delivered = [];
        $pending = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;
            $totals[] = (clone $query)->whereDate('created_at', $date)->count();
            $delivered[] = (clone $query)->where('status', ParcelStatus::DELIVERED)
                ->whereDate('updated_at', $date)->count();
            $pending[] = (clone $query)->where('status', ParcelStatus::PENDING)
                ->whereDate('created_at', $date)->count();
        }

        return [
            'dates' => $dates,
            'totals' => $totals,
            'delivered' => $delivered,
            'pending' => $pending
        ];
    }

    /**
     * Broadcast real-time updates
     */
    private function broadcastUpdates($calculations)
    {
        // Broadcast KPI updates
        if (isset($calculations['kpis'])) {
            foreach ($calculations['kpis'] as $kpiId => $value) {
                broadcast(new KpiUpdated($kpiId, $value, $this->calculateTrend($kpiId), $this->merchantId));
            }
        }

        // Broadcast queue updates (simplified - in real implementation would track actual changes)
        if (isset($calculations['queue'])) {
            // This would be triggered by actual queue changes in the application
            // For now, just cache the data for polling
        }
    }

    /**
     * Calculate trend for KPI (simplified implementation)
     */
    private function calculateTrend($kpiId)
    {
        // In a real implementation, this would compare current vs previous period
        // For now, return a mock trend
        return [
            'value' => rand(-5, 15),
            'direction' => rand(0, 1) ? 'up' : 'down'
        ];
    }

    /**
     * Get the queue name for the job
     */
    public function queue()
    {
        return 'dashboard-calculations';
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags()
    {
        return [
            'dashboard',
            'calculations',
            $this->merchantId ? "merchant:{$this->merchantId}" : 'global'
        ];
    }
}
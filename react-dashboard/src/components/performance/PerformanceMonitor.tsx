/**
 * Performance Monitor Component
 * Real-time application performance monitoring with DHL-grade metrics
 */

import React, { useEffect, useState, useMemo } from 'react';
import Card from '../ui/Card';
import Button from '../ui/Button';
import { Activity, Zap, Clock, Database, Wifi, Cpu } from 'lucide-react';

interface PerformanceMetrics {
  renderTime: number;
  memoryUsage: number;
  networkRequests: number;
  cacheHitRate: number;
  apiResponseTime: number;
  bundleSize: number;
  lighthouseScore: number;
  coreWebVitals: {
    lcp: number;
    fid: number;
    cls: number;
  };
}

interface PerformanceMonitorProps {
  enabled?: boolean;
  showDetails?: boolean;
  onPerformanceUpdate?: (metrics: PerformanceMetrics) => void;
}

const PerformanceMonitor: React.FC<PerformanceMonitorProps> = ({
  enabled = true,
  showDetails = false,
  onPerformanceUpdate,
}) => {
  const [metrics, setMetrics] = useState<PerformanceMetrics | null>(null);
  const [isVisible, setIsVisible] = useState(false);
  const [refreshInterval, setRefreshInterval] = useState(5000);

  // Measure Core Web Vitals
  const measureCoreWebVitals = () => {
    if ('PerformanceObserver' in window) {
      try {
        // Largest Contentful Paint
        new PerformanceObserver((list) => {
          const entries = list.getEntries();
          const lastEntry = entries[entries.length - 1];
          setMetrics(prev => prev ? {
            ...prev,
            coreWebVitals: {
              ...prev.coreWebVitals,
              lcp: lastEntry.startTime,
            }
          } : null);
        }).observe({ entryTypes: ['largest-contentful-paint'] });

        // First Input Delay
        new PerformanceObserver((list) => {
          const entries = list.getEntries() as PerformanceEventTiming[];
          entries.forEach((entry) => {
            const interactionDelay = typeof entry.processingStart === 'number'
              ? entry.processingStart - entry.startTime
              : entry.duration;

            setMetrics(prev => prev ? {
              ...prev,
              coreWebVitals: {
                ...prev.coreWebVitals,
                fid: interactionDelay,
              }
            } : null);
          });
        }).observe({ entryTypes: ['first-input'] });

        // Cumulative Layout Shift
        let clsValue = 0;
        new PerformanceObserver((list) => {
          const entries = list.getEntries();
          entries.forEach((entry: any) => {
            if (!entry.hadRecentInput) {
              clsValue += entry.value;
            }
          });
          setMetrics(prev => prev ? {
            ...prev,
            coreWebVitals: {
              ...prev.coreWebVitals,
              cls: clsValue,
            }
          } : null);
        }).observe({ entryTypes: ['layout-shift'] });
      } catch (error) {
        console.warn('Performance Observer not supported:', error);
      }
    }
  };

  // Collect performance metrics
  const collectMetrics = useMemo(() => {
    return () => {
      const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
      const paint = performance.getEntriesByType('paint');
      
      const memoryInfo = (performance as any).memory;
      const paintMetrics = paint.reduce((acc, entry) => {
        acc[entry.name] = entry.startTime;
        return acc;
      }, {} as Record<string, number>);

      const newMetrics: PerformanceMetrics = {
        renderTime: Date.now() - (navigation?.startTime || 0),
        memoryUsage: memoryInfo?.usedJSHeapSize || 0,
        networkRequests: performance.getEntriesByType('resource').length,
        cacheHitRate: 0, // Would be calculated from network timing
        apiResponseTime: navigation?.responseEnd - navigation?.responseStart || 0,
        bundleSize: 0, // Would be calculated from bundle analysis
        lighthouseScore: 0, // Would be from Lighthouse API
        coreWebVitals: {
          lcp: paintMetrics['largest-contentful-paint'] || 0,
          fid: 0,
          cls: 0,
        },
      };

      setMetrics(newMetrics);
      onPerformanceUpdate?.(newMetrics);
    };
  }, [onPerformanceUpdate]);

  useEffect(() => {
    if (!enabled) return;

    measureCoreWebVitals();
    collectMetrics();

    const interval = setInterval(collectMetrics, refreshInterval);
    return () => clearInterval(interval);
  }, [enabled, collectMetrics, refreshInterval]);

  const getPerformanceColor = (value: number, thresholds: { good: number; needsImprovement: number }) => {
    if (value <= thresholds.good) return 'text-green-600';
    if (value <= thresholds.needsImprovement) return 'text-yellow-600';
    return 'text-red-600';
  };

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatTime = (time: number) => {
    return time < 1000 ? `${time.toFixed(0)}ms` : `${(time / 1000).toFixed(2)}s`;
  };

  if (!enabled) return null;

  return (
    <>
      {/* Floating Performance Button */}
      <button
        onClick={() => setIsVisible(!isVisible)}
        className="fixed bottom-4 right-4 z-50 bg-mono-black text-mono-white p-3 rounded-full shadow-lg hover:bg-mono-gray-800 transition-colors"
        aria-label="Toggle performance monitor"
      >
        <Activity className="h-5 w-5" />
      </button>

      {/* Performance Dashboard */}
      {isVisible && (
        <Card className="fixed bottom-20 right-4 w-80 bg-white shadow-xl border border-mono-gray-200 z-40">
          <div className="p-4 space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">Performance Monitor</h3>
              <button
                onClick={() => setIsVisible(false)}
                className="text-mono-gray-500 hover:text-mono-black"
              >
                ×
              </button>
            </div>

            {metrics && (
              <div className="space-y-3">
                {/* Core Metrics */}
                <div className="grid grid-cols-2 gap-3">
                  <div className="bg-mono-gray-50 p-3 rounded-lg">
                    <div className="flex items-center gap-2 mb-1">
                      <Clock className="h-4 w-4 text-mono-gray-600" />
                      <span className="text-xs text-mono-gray-600">Render Time</span>
                    </div>
                    <p className="text-lg font-semibold text-mono-black">
                      {formatTime(metrics.renderTime)}
                    </p>
                  </div>

                  <div className="bg-mono-gray-50 p-3 rounded-lg">
                    <div className="flex items-center gap-2 mb-1">
                      <Database className="h-4 w-4 text-mono-gray-600" />
                      <span className="text-xs text-mono-gray-600">Memory</span>
                    </div>
                    <p className="text-lg font-semibold text-mono-black">
                      {formatBytes(metrics.memoryUsage)}
                    </p>
                  </div>

                  <div className="bg-mono-gray-50 p-3 rounded-lg">
                    <div className="flex items-center gap-2 mb-1">
                      <Wifi className="h-4 w-4 text-mono-gray-600" />
                      <span className="text-xs text-mono-gray-600">API Response</span>
                    </div>
                    <p className="text-lg font-semibold text-mono-black">
                      {formatTime(metrics.apiResponseTime)}
                    </p>
                  </div>

                  <div className="bg-mono-gray-50 p-3 rounded-lg">
                    <div className="flex items-center gap-2 mb-1">
                      <Cpu className="h-4 w-4 text-mono-gray-600" />
                      <span className="text-xs text-mono-gray-600">Requests</span>
                    </div>
                    <p className="text-lg font-semibold text-mono-black">
                      {metrics.networkRequests}
                    </p>
                  </div>
                </div>

                {/* Core Web Vitals */}
                {showDetails && (
                  <div className="space-y-2">
                    <h4 className="text-sm font-semibold text-mono-gray-700">Core Web Vitals</h4>
                    <div className="space-y-2">
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-mono-gray-600">LCP</span>
                        <span className={`text-xs font-medium ${getPerformanceColor(metrics.coreWebVitals.lcp, { good: 2500, needsImprovement: 4000 })}`}>
                          {formatTime(metrics.coreWebVitals.lcp)}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-mono-gray-600">FID</span>
                        <span className={`text-xs font-medium ${getPerformanceColor(metrics.coreWebVitals.fid, { good: 100, needsImprovement: 300 })}`}>
                          {formatTime(metrics.coreWebVitals.fid)}
                        </span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-xs text-mono-gray-600">CLS</span>
                        <span className={`text-xs font-medium ${getPerformanceColor(metrics.coreWebVitals.cls, { good: 0.1, needsImprovement: 0.25 })}`}>
                          {metrics.coreWebVitals.cls.toFixed(3)}
                        </span>
                      </div>
                    </div>
                  </div>
                )}

                {/* Controls */}
                <div className="flex items-center justify-between pt-2 border-t border-mono-gray-200">
                  <label className="text-xs text-mono-gray-600">
                    Refresh: {refreshInterval / 1000}s
                  </label>
                  <div className="flex gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setRefreshInterval(1000)}
                    >
                      1s
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setRefreshInterval(5000)}
                    >
                      5s
                    </Button>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setRefreshInterval(30000)}
                    >
                      30s
                    </Button>
                  </div>
                </div>

                {/* Performance Status */}
                <div className="flex items-center gap-2 pt-2">
                  <div className={`w-2 h-2 rounded-full ${
                    metrics.renderTime < 100 ? 'bg-green-500' : 
                    metrics.renderTime < 300 ? 'bg-yellow-500' : 'bg-red-500'
                  }`} />
                  <span className="text-xs text-mono-gray-600">
                    {metrics.renderTime < 100 ? 'Excellent' : 
                     metrics.renderTime < 300 ? 'Good' : 'Needs Attention'}
                  </span>
                </div>
              </div>
            )}
          </div>
        </Card>
      )}
    </>
  );
};

export default PerformanceMonitor;

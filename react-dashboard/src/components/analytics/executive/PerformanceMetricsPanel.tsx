/**
 * Performance Metrics Panel
 * System performance monitoring and optimization recommendations
 */

import React from 'react';
import Card from '../../ui/Card';
import Button from '../../ui/Button';
import { usePerformanceMetrics, usePerformanceRecommendations } from '../../../hooks/useAnalytics';

/**
 * Performance Metrics Panel Component
 * Displays system performance metrics and optimization recommendations
 */
const PerformanceMetricsPanel: React.FC = () => {
  const { data: metricsData, isLoading: metricsLoading } = usePerformanceMetrics();
  const { data: recommendationsData, isLoading: recommendationsLoading } = usePerformanceRecommendations();

  // Format load time
  const formatLoadTime = (time: number) => {
    if (time < 1000) return `${time}ms`;
    return `${(time / 1000).toFixed(2)}s`;
  };

  // Get performance grade
  const getPerformanceGrade = (metrics: { loadTime: number; renderTime: number; cacheHitRate: number; errorRate: number }) => {
    const score = Math.max(
      0,
      Math.min(
        100,
        100 - (metrics.loadTime / 100) - (metrics.errorRate * 10) - ((100 - metrics.cacheHitRate) / 2)
      )
    );

    if (score >= 90) return { grade: 'A', color: 'text-green-600', bg: 'bg-green-50' };
    if (score >= 80) return { grade: 'B', color: 'text-blue-600', bg: 'bg-blue-50' };
    if (score >= 70) return { grade: 'C', color: 'text-yellow-600', bg: 'bg-yellow-50' };
    if (score >= 60) return { grade: 'D', color: 'text-orange-600', bg: 'bg-orange-50' };
    return { grade: 'F', color: 'text-red-600', bg: 'bg-red-50' };
  };

  // Show loading state
  if (metricsLoading && recommendationsLoading) {
    return (
      <Card className="p-6">
        <div className="space-y-4">
          <h3 className="text-lg font-semibold text-mono-black">System Performance</h3>
          <div className="animate-pulse space-y-3">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="h-8 bg-mono-gray-200 rounded" />
            ))}
          </div>
        </div>
      </Card>
    );
  }

  const metrics = metricsData?.data;
  const recommendations = recommendationsData?.data || [];

  if (!metrics) {
    return (
      <Card className="p-6">
        <div className="text-center py-6">
          <i className="fas fa-chart-line text-mono-gray-400 text-3xl mb-3" />
          <p className="text-sm text-mono-gray-500">
            Performance metrics unavailable
          </p>
        </div>
      </Card>
    );
  }

  const performanceGrade = getPerformanceGrade(metrics);

  return (
    <section className="space-y-6">
      <header>
        <h2 className="text-lg font-semibold text-mono-black">
          System Performance
        </h2>
        <p className="text-sm text-mono-gray-600">
          Real-time system metrics and optimization recommendations
        </p>
      </header>

      {/* Performance Overview */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Performance Score */}
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Performance Grade
              </h3>
              <Button
                variant="outline"
                size="sm"
                onClick={() => window.location.reload()}
              >
                <i className="fas fa-sync-alt" aria-hidden="true" />
              </Button>
            </div>
            <div className="text-center">
              <div className={`inline-flex items-center justify-center w-20 h-20 rounded-full text-3xl font-bold ${performanceGrade.color} ${performanceGrade.bg} border-4 border-current`}>
                {performanceGrade.grade}
              </div>
              <p className="text-sm text-mono-gray-600 mt-2">
                Overall System Performance
              </p>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-mono-gray-600">Load Time:</span>
                <span className={`font-medium ${metrics.loadTime < 2000 ? 'text-green-600' : metrics.loadTime < 5000 ? 'text-yellow-600' : 'text-red-600'}`}>
                  {formatLoadTime(metrics.loadTime)}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-mono-gray-600">Cache Hit Rate:</span>
                <span className={`font-medium ${metrics.cacheHitRate > 80 ? 'text-green-600' : metrics.cacheHitRate > 60 ? 'text-yellow-600' : 'text-red-600'}`}>
                  {metrics.cacheHitRate.toFixed(1)}%
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-mono-gray-600">Error Rate:</span>
                <span className={`font-medium ${metrics.errorRate < 1 ? 'text-green-600' : metrics.errorRate < 5 ? 'text-yellow-600' : 'text-red-600'}`}>
                  {metrics.errorRate.toFixed(2)}%
                </span>
              </div>
            </div>
          </div>
        </Card>

        {/* Resource Usage */}
        <Card className="p-6">
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-mono-black">
              Resource Usage
            </h3>
            <div className="space-y-4">
              {/* Data Size */}
              <div>
                <div className="flex justify-between text-sm mb-1">
                  <span className="text-mono-gray-600">Data Transfer</span>
                  <span className="font-medium">
                    {(metrics.dataSize / 1024 / 1024).toFixed(1)} MB
                  </span>
                </div>
                <div className="w-full bg-mono-gray-200 rounded-full h-2">
                  <div 
                    className="bg-blue-600 h-2 rounded-full" 
                    style={{ width: `${Math.min(metrics.dataSize / 1024 / 1024 / 10 * 100, 100)}%` }}
                  />
                </div>
              </div>

              {/* Cache Performance */}
              <div>
                <div className="flex justify-between text-sm mb-1">
                  <span className="text-mono-gray-600">Cache Efficiency</span>
                  <span className="font-medium">
                    {metrics.cacheHitRate.toFixed(0)}%
                  </span>
                </div>
                <div className="w-full bg-mono-gray-200 rounded-full h-2">
                  <div 
                    className="bg-green-600 h-2 rounded-full" 
                    style={{ width: `${metrics.cacheHitRate}%` }}
                  />
                </div>
              </div>

              {/* Render Performance */}
              <div>
                <div className="flex justify-between text-sm mb-1">
                  <span className="text-mono-gray-600">Render Speed</span>
                  <span className="font-medium">
                    {formatLoadTime(metrics.renderTime)}
                  </span>
                </div>
                <div className="w-full bg-mono-gray-200 rounded-full h-2">
                  <div 
                    className={`h-2 rounded-full ${metrics.renderTime < 100 ? 'bg-green-600' : metrics.renderTime < 200 ? 'bg-yellow-600' : 'bg-red-600'}`} 
                    style={{ width: `${Math.max(0, 100 - (metrics.renderTime / 5))}%` }}
                  />
                </div>
              </div>
            </div>
          </div>
        </Card>

        {/* Quick Stats */}
        <Card className="p-6">
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-mono-black">
              Quick Stats
            </h3>
            <div className="space-y-3">
              <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                <div className="flex items-center gap-2">
                  <i className="fas fa-clock text-mono-gray-500" />
                  <span className="text-sm text-mono-gray-600">Avg Load Time</span>
                </div>
                <span className="font-medium text-mono-black">
                  {formatLoadTime(metrics.loadTime)}
                </span>
              </div>
              
              <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                <div className="flex items-center gap-2">
                  <i className="fas fa-database text-mono-gray-500" />
                  <span className="text-sm text-mono-gray-600">Cache Hits</span>
                </div>
                <span className="font-medium text-mono-black">
                  {metrics.cacheHitRate.toFixed(0)}%
                </span>
              </div>
              
              <div className="flex items-center justify-between p-3 bg-mono-gray-50 rounded-lg">
                <div className="flex items-center gap-2">
                  <i className="fas fa-exclamation-triangle text-mono-gray-500" />
                  <span className="text-sm text-mono-gray-600">Error Rate</span>
                </div>
                <span className={`font-medium ${metrics.errorRate < 1 ? 'text-green-600' : 'text-red-600'}`}>
                  {metrics.errorRate.toFixed(2)}%
                </span>
              </div>
            </div>
          </div>
        </Card>
      </div>

      {/* Optimization Recommendations */}
      {recommendations.length > 0 && (
        <Card className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-mono-black">
                Optimization Recommendations
              </h3>
              <Button
                variant="outline"
                size="sm"
                onClick={() => window.location.reload()}
                disabled={recommendationsLoading}
              >
                <i className="fas fa-lightbulb mr-2" aria-hidden="true" />
                Refresh
              </Button>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {recommendations.map((recommendation: any, index: number) => (
                <div
                  key={index}
                  className="p-4 border border-mono-gray-200 rounded-lg hover:border-mono-gray-300 transition-colors"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                          recommendation.priority === 'high' ? 'bg-red-100 text-red-800' :
                          recommendation.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                          'bg-blue-100 text-blue-800'
                        }`}>
                          {recommendation.priority?.toUpperCase()}
                        </span>
                        <span className="text-xs text-mono-gray-500">
                          {recommendation.type}
                        </span>
                      </div>
                      <p className="text-sm font-medium text-mono-black mb-1">
                        {recommendation.message}
                      </p>
                      {recommendation.impact && (
                        <p className="text-xs text-mono-gray-600">
                          Impact: {recommendation.impact}
                        </p>
                      )}
                    </div>
                    <i className="fas fa-lightbulb text-yellow-500 mt-1" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </Card>
      )}

      {/* Performance Status */}
      <Card className="p-6 bg-gradient-to-r from-mono-gray-50 to-mono-gray-100">
        <div className="flex items-center justify-between">
          <div>
            <h3 className="text-lg font-semibold text-mono-black mb-1">
              System Health Status
            </h3>
            <p className="text-sm text-mono-gray-600">
              {metrics.loadTime < 2000 ? 'Excellent performance' : 
               metrics.loadTime < 5000 ? 'Good performance' : 
               'Performance needs optimization'}
            </p>
          </div>
          <div className="text-right">
            <div className="text-2xl font-bold text-mono-black">
              {metrics.loadTime < 2000 ? '🟢' : metrics.loadTime < 5000 ? '🟡' : '🔴'}
            </div>
            <div className="text-xs text-mono-gray-500">
              Last updated: {new Date().toLocaleTimeString()}
            </div>
          </div>
        </div>
      </Card>
    </section>
  );
};

export default PerformanceMetricsPanel;
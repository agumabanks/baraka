import React from 'react';
import { Card } from '../../ui/Card';
import { Badge } from '../../ui/Badge';
import { EDIPerformanceMetrics } from '../../../types/edi';

interface EDIPerformanceMetricsProps {
  data?: EDIPerformanceMetrics;
  loading: boolean;
  detailed?: boolean;
}

export const EDIPerformanceMetricsPanel: React.FC<EDIPerformanceMetricsProps> = ({
  data,
  loading,
  detailed = false,
}) => {
  if (loading) {
    return (
      <Card className="p-6">
        <div className="flex justify-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      </Card>
    );
  }

  if (!data) {
    return (
      <Card className="p-6">
        <p className="text-mono-gray-600 text-center">No performance data available</p>
      </Card>
    );
  }

  const renderChart = (chartData: any[], title: string, valueKey: string, color: string) => {
    if (!chartData || chartData.length === 0) return null;

    const maxValue = Math.max(...chartData.map(d => d[valueKey] || 0));
    
    return (
      <div className="mb-4">
        <h4 className="text-sm font-medium text-mono-gray-700 mb-2">{title}</h4>
        <div className="space-y-2">
          {chartData.slice(-7).map((item, index) => (
            <div key={index} className="flex items-center gap-2">
              <div className="w-16 text-xs text-mono-gray-600">
                {new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
              </div>
              <div className="flex-1 bg-gray-200 rounded-full h-2">
                <div
                  className={`h-2 rounded-full ${color}`}
                  style={{ width: `${maxValue > 0 ? (item[valueKey] / maxValue) * 100 : 0}%` }}
                />
              </div>
              <div className="w-12 text-xs text-mono-gray-900 font-medium text-right">
                {item[valueKey] || 0}
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      {/* Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-blue-600">{data.overview.total_transactions}</p>
            <p className="text-sm text-mono-gray-600">Total Transactions</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">{data.overview.success_rate.toFixed(1)}%</p>
            <p className="text-sm text-mono-gray-600">Success Rate</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-purple-600">{data.overview.average_processing_time.toFixed(0)}ms</p>
            <p className="text-sm text-mono-gray-600">Avg Processing</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-orange-600">{data.overview.acknowledged_rate.toFixed(1)}%</p>
            <p className="text-sm text-mono-gray-600">Acknowledged</p>
          </div>
        </Card>
      </div>

      {detailed && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Transaction Volume Chart */}
          <Card className="p-6">
            <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Transaction Volume</h4>
            {renderChart(data.transaction_volume_chart, 'Daily Transactions', 'count', 'bg-blue-500')}
            <div className="flex justify-between text-xs text-mono-gray-600 mt-2">
              <span>Success: {data.transaction_volume_chart?.reduce((sum, d) => sum + (d.success || 0), 0) || 0}</span>
              <span>Failed: {data.transaction_volume_chart?.reduce((sum, d) => sum + (d.failed || 0), 0) || 0}</span>
            </div>
          </Card>

          {/* Processing Time Chart */}
          <Card className="p-6">
            <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Processing Times</h4>
            {renderChart(data.processing_time_chart, 'Average Processing Time (ms)', 'average_time', 'bg-green-500')}
            <div className="flex justify-between text-xs text-mono-gray-600 mt-2">
              <span>P50: {data.processing_time_chart?.[data.processing_time_chart.length - 1]?.p50 || 0}ms</span>
              <span>P95: {data.processing_time_chart?.[data.processing_time_chart.length - 1]?.p95 || 0}ms</span>
            </div>
          </Card>
        </div>
      )}

      {/* Document Type Performance */}
      {data.document_type_performance && data.document_type_performance.length > 0 && (
        <Card className="p-6">
          <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Document Type Performance</h4>
          <div className="space-y-3">
            {data.document_type_performance.map((docType, index) => (
              <div key={index} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <span className="text-sm font-bold text-blue-600">{docType.document_code}</span>
                  </div>
                  <div>
                    <p className="font-medium text-mono-gray-900">{docType.document_type}</p>
                    <p className="text-sm text-mono-gray-600">{docType.count} transactions</p>
                  </div>
                </div>
                <div className="text-right">
                  <Badge 
                    variant={docType.success_rate > 95 ? 'success' : 
                           docType.success_rate > 80 ? 'warning' : 'destructive'}
                  >
                    {docType.success_rate.toFixed(1)}% success
                  </Badge>
                  <p className="text-xs text-mono-gray-600 mt-1">
                    Avg: {docType.average_processing_time.toFixed(0)}ms
                  </p>
                </div>
              </div>
            ))}
          </div>
        </Card>
      )}

      {/* System Status */}
      <Card className="p-6">
        <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">System Components</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">EDI Engine</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">Document Processing</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">Acknowledgment System</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">File Transfer</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">Validation Engine</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
          <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            <div>
              <p className="font-medium text-mono-gray-900">Database</p>
              <p className="text-sm text-mono-gray-600">Operational</p>
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
};

export default EDIPerformanceMetricsPanel;
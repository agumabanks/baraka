import React from 'react';
import { Card } from '../../ui/Card';
import { Badge } from '../../ui/Badge';
import { WebhookHealthStatus } from '../../../types/webhook';

interface WebhookHealthDashboardProps {
  data?: WebhookHealthStatus;
  loading: boolean;
  detailed?: boolean;
}

export const WebhookHealthDashboard: React.FC<WebhookHealthDashboardProps> = ({
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
        <p className="text-mono-gray-600 text-center">No health data available</p>
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
            <p className="text-2xl font-bold text-blue-600">{data.overview.total_endpoints}</p>
            <p className="text-sm text-mono-gray-600">Total Endpoints</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">{data.overview.active_endpoints}</p>
            <p className="text-sm text-mono-gray-600">Active Endpoints</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-purple-600">{data.overview.success_rate.toFixed(1)}%</p>
            <p className="text-sm text-mono-gray-600">Overall Success Rate</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-orange-600">{data.overview.average_response_time.toFixed(0)}ms</p>
            <p className="text-sm text-mono-gray-600">Avg Response Time</p>
          </div>
        </Card>
      </div>

      {detailed && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Delivery Volume Chart */}
          <Card className="p-6">
            <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Delivery Volume</h4>
            {renderChart(data.delivery_volume_chart, 'Daily Deliveries', 'count', 'bg-blue-500')}
            <div className="flex justify-between text-xs text-mono-gray-600 mt-2">
              <span>Success: {data.delivery_volume_chart?.reduce((sum, d) => sum + (d.success || 0), 0) || 0}</span>
              <span>Failed: {data.delivery_volume_chart?.reduce((sum, d) => sum + (d.failed || 0), 0) || 0}</span>
            </div>
          </Card>

          {/* Response Time Chart */}
          <Card className="p-6">
            <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Response Times</h4>
            {renderChart(data.response_time_chart, 'Average Response Time (ms)', 'average_time', 'bg-green-500')}
            <div className="flex justify-between text-xs text-mono-gray-600 mt-2">
              <span>P50: {data.response_time_chart?.[data.response_time_chart.length - 1]?.p50 || 0}ms</span>
              <span>P95: {data.response_time_chart?.[data.response_time_chart.length - 1]?.p95 || 0}ms</span>
            </div>
          </Card>
        </div>
      )}

      {/* System Components */}
      <Card className="p-6">
        <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">System Components</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {data.components?.map((component, index) => (
            <div key={index} className="flex items-center gap-3 p-3 bg-green-50 rounded">
              <div className={`w-3 h-3 rounded-full ${
                component.status === 'operational' ? 'bg-green-500' : 
                component.status === 'degraded' ? 'bg-yellow-500' : 'bg-red-500'
              }`}></div>
              <div>
                <p className="font-medium text-mono-gray-900">{component.name}</p>
                <p className="text-sm text-mono-gray-600">
                  {component.status === 'operational' ? 'Operational' : 
                   component.status === 'degraded' ? 'Degraded' : 'Offline'}
                </p>
              </div>
            </div>
          )) || (
            <>
              <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p className="font-medium text-mono-gray-900">Webhook Engine</p>
                  <p className="text-sm text-mono-gray-600">Operational</p>
                </div>
              </div>
              <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p className="font-medium text-mono-gray-900">Delivery Queue</p>
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
              <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p className="font-medium text-mono-gray-900">HTTPS Connections</p>
                  <p className="text-sm text-mono-gray-600">Operational</p>
                </div>
              </div>
              <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p className="font-medium text-mono-gray-900">SSL/TLS</p>
                  <p className="text-sm text-mono-gray-600">Operational</p>
                </div>
              </div>
              <div className="flex items-center gap-3 p-3 bg-green-50 rounded">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p className="font-medium text-mono-gray-900">Rate Limiting</p>
                  <p className="text-sm text-mono-gray-600">Operational</p>
                </div>
              </div>
            </>
          )}
        </div>
      </Card>

      {/* Recent Alerts */}
      {data.alerts && data.alerts.length > 0 && (
        <Card className="p-6">
          <h4 className="text-lg font-semibold text-mono-gray-900 mb-4">Recent Alerts</h4>
          <div className="space-y-3">
            {data.alerts.map((alert, index) => (
              <div
                key={index}
                className={`p-3 rounded border ${
                  alert.severity === 'critical' 
                    ? 'bg-red-50 border-red-200' 
                    : alert.severity === 'warning'
                    ? 'bg-yellow-50 border-yellow-200'
                    : 'bg-blue-50 border-blue-200'
                }`}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <Badge 
                      variant={alert.severity === 'critical' ? 'destructive' : 
                             alert.severity === 'warning' ? 'warning' : 'default'}
                      className="text-xs"
                    >
                      {alert.severity.toUpperCase()}
                    </Badge>
                    <span className="text-sm font-medium text-mono-gray-900">{alert.title}</span>
                  </div>
                  <span className="text-xs text-mono-gray-600">
                    {new Date(alert.timestamp ?? alert.created_at).toLocaleDateString()}
                  </span>
                </div>
                <p className="text-sm text-mono-gray-700 mt-1">{alert.message}</p>
              </div>
            ))}
          </div>
        </Card>
      )}
    </div>
  );
};
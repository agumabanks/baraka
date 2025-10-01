import React from 'react';
import Card from '../ui/Card';
import LineChart from './charts/LineChart';
import BarChart from './charts/BarChart';
import AreaChart from './charts/AreaChart';
import PieChart from './charts/PieChart';
import type { ChartConfig } from '../../types/dashboard';

/**
 * Chart Section Component
 * Renders appropriate monochrome chart based on configuration
 */
interface ChartSectionProps {
  /** Chart configuration */
  config: ChartConfig;
  /** Loading state */
  loading?: boolean;
  /** Error state */
  error?: string;
}

const ChartSection: React.FC<ChartSectionProps> = ({
  config,
  loading = false,
  error
}) => {
  const { title, type, data, height = 300 } = config;

  // Chart type icon mapping
  const chartIcons = {
    line: 'fas fa-chart-line',
    bar: 'fas fa-chart-bar',
    area: 'fas fa-chart-area',
    pie: 'fas fa-chart-pie',
    polar: 'fas fa-circle-notch',
  };

  // Render the appropriate chart component
  const renderChart = () => {
    const chartProps = {
      data: data || [],
      height,
      loading,
      error,
      ariaLabel: `${title} ${type} chart`,
    };

    switch (type) {
      case 'line':
        return <LineChart {...chartProps} />;
      case 'bar':
        return <BarChart {...chartProps} />;
      case 'area':
        return <AreaChart {...chartProps} />;
      case 'pie':
        return <PieChart {...chartProps} donut={false} />;
      default:
        // Fallback for unsupported chart types
        return (
          <div
            className="flex flex-col items-center justify-center bg-mono-gray-50 border-2 border-dashed border-mono-gray-300 rounded-lg"
            style={{ height: `${height}px` }}
            role="status"
            aria-label={`Unsupported chart type: ${type}`}
          >
            <i
              className="fas fa-exclamation-triangle text-3xl text-mono-gray-400 mb-4"
              aria-hidden="true"
            />
            <p className="text-mono-gray-500 font-medium mb-2">
              Unsupported Chart Type
            </p>
            <p className="text-sm text-mono-gray-400">
              Chart type "{type}" is not supported
            </p>
          </div>
        );
    }
  };

  return (
    <Card
      header={
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold text-mono-black">
            {title}
          </h3>
          <div className="flex items-center gap-2">
            <i
              className={`${chartIcons[type]} text-mono-gray-400`}
              aria-hidden="true"
            />
            <span className="text-xs text-mono-gray-500 uppercase tracking-wide">
              {type} chart
            </span>
          </div>
        </div>
      }
    >
      {renderChart()}
    </Card>
  );
};

export default ChartSection;
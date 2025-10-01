import React from 'react';
import {
  BarChart as RechartsBarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from 'recharts';
import type { ChartDataPoint } from '../../../types/dashboard';
import {
  MONOCHROME_COLORS,
  CHART_SIZING,
  CHART_ANIMATIONS,
  getCustomTooltipConfig,
  formatChartData,
} from '../../../utils/chartConfig';

/**
 * Monochrome Bar Chart Component
 * Displays comparisons with clean bar visualization
 */
interface BarChartProps {
  /** Chart data points */
  data: ChartDataPoint[];
  /** Chart height in pixels */
  height?: number;
  /** Loading state */
  loading?: boolean;
  /** Error state */
  error?: string;
  /** Accessibility label */
  ariaLabel?: string;
}

const BarChart: React.FC<BarChartProps> = ({
  data,
  height = CHART_SIZING.defaultHeight,
  loading = false,
  error,
  ariaLabel = 'Bar chart showing data comparisons',
}) => {
  // Handle loading state
  if (loading) {
    return (
      <div
        className="animate-pulse bg-mono-gray-50 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="status"
        aria-live="polite"
        aria-label="Loading bar chart"
      >
        <div className="text-mono-gray-400">Loading chart...</div>
      </div>
    );
  }

  // Handle error state
  if (error) {
    return (
      <div
        className="bg-mono-gray-50 border border-mono-gray-300 rounded-lg flex items-center justify-center px-6 text-center"
        style={{ height }}
        role="alert"
        aria-live="assertive"
      >
        <div className="text-sm text-mono-gray-600">
          {error ? `Unable to render chart: ${error}` : 'Unable to render chart'}
        </div>
      </div>
    );
  }

  // Handle empty data
  if (!data || data.length === 0) {
    return (
      <div
        className="bg-mono-gray-50 border border-mono-gray-200 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="status"
      >
        <div className="text-mono-gray-500 text-sm">No data available</div>
      </div>
    );
  }

  // Format data for Recharts
  const formattedData = formatChartData(data);
  const tooltipConfig = getCustomTooltipConfig();

  return (
    <div
      className="w-full"
      role="img"
      aria-label={ariaLabel}
      aria-describedby="bar-chart-description"
    >
      <span id="bar-chart-description" className="sr-only">
        Bar chart displaying {data.length} data points for comparison
      </span>

      <ResponsiveContainer width="100%" height={height}>
        <RechartsBarChart
          data={formattedData}
          margin={CHART_SIZING.margins}
          aria-hidden="true"
        >
          {/* Grid lines */}
          <CartesianGrid
            stroke={MONOCHROME_COLORS.gray[200]}
            strokeDasharray="3 3"
            strokeWidth={1}
          />

          {/* X-axis */}
          <XAxis
            dataKey="label"
            stroke={MONOCHROME_COLORS.gray[400]}
            strokeWidth={1}
            fontSize={12}
            fontFamily="system-ui, -apple-system, sans-serif"
            tick={{ fill: MONOCHROME_COLORS.gray[600] }}
            axisLine={{ stroke: MONOCHROME_COLORS.gray[300] }}
            tickLine={{ stroke: MONOCHROME_COLORS.gray[300] }}
          />

          {/* Y-axis */}
          <YAxis
            stroke={MONOCHROME_COLORS.gray[400]}
            strokeWidth={1}
            fontSize={12}
            fontFamily="system-ui, -apple-system, sans-serif"
            tick={{ fill: MONOCHROME_COLORS.gray[600] }}
            axisLine={{ stroke: MONOCHROME_COLORS.gray[300] }}
            tickLine={{ stroke: MONOCHROME_COLORS.gray[300] }}
          />

          {/* Tooltip */}
          <Tooltip
            contentStyle={tooltipConfig.contentStyle}
            labelStyle={tooltipConfig.labelStyle}
            itemStyle={tooltipConfig.itemStyle}
            cursor={tooltipConfig.cursor}
            aria-live="polite"
          />

          {/* Bars */}
          <Bar
            dataKey="value"
            fill={MONOCHROME_COLORS.gray[400]}
            stroke={MONOCHROME_COLORS.gray[600]}
            strokeWidth={1}
            radius={[2, 2, 0, 0]}
            animationDuration={CHART_ANIMATIONS.duration}
            animationEasing={CHART_ANIMATIONS.easing}
          />
        </RechartsBarChart>
      </ResponsiveContainer>
    </div>
  );
};

export default BarChart;

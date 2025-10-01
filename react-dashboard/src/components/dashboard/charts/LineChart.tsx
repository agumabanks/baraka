import React from 'react';
import {
  LineChart as RechartsLineChart,
  Line,
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
 * Monochrome Line Chart Component
 * Displays trends with smooth line visualization
 */
interface LineChartProps {
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

const LineChart: React.FC<LineChartProps> = ({
  data,
  height = CHART_SIZING.defaultHeight,
  loading = false,
  error,
  ariaLabel = 'Line chart showing data trends',
}) => {
  // Handle loading state
  if (loading) {
    return (
      <div
        className="animate-pulse bg-mono-gray-50 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="status"
        aria-live="polite"
        aria-label="Loading line chart"
      >
        <div className="text-mono-gray-400">Loading chart...</div>
      </div>
    );
  }

  // Handle error state
  if (error) {
    return (
      <div
        className="bg-red-50 border border-red-200 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="alert"
        aria-live="assertive"
      >
        <div className="text-red-600 text-sm">
          Error loading chart: {error}
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
      aria-describedby="line-chart-description"
    >
      <span id="line-chart-description" className="sr-only">
        Line chart displaying {data.length} data points showing trends over time
      </span>

      <ResponsiveContainer width="100%" height={height}>
        <RechartsLineChart
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

          {/* Line */}
          <Line
            type="monotone"
            dataKey="value"
            stroke={MONOCHROME_COLORS.primary}
            strokeWidth={2}
            dot={{
              fill: MONOCHROME_COLORS.primary,
              strokeWidth: 2,
              r: 4,
            }}
            activeDot={{
              fill: MONOCHROME_COLORS.primary,
              stroke: MONOCHROME_COLORS.secondary,
              strokeWidth: 2,
              r: 6,
            }}
            animationDuration={CHART_ANIMATIONS.duration}
            animationEasing={CHART_ANIMATIONS.easing}
          />
        </RechartsLineChart>
      </ResponsiveContainer>
    </div>
  );
};

export default LineChart;
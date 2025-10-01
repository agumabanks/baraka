import React from 'react';
import {
  AreaChart as RechartsAreaChart,
  Area,
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
  getMonochromeGradients,
} from '../../../utils/chartConfig';

/**
 * Monochrome Area Chart Component
 * Displays volumes with filled area visualization
 */
interface AreaChartProps {
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

const AreaChart: React.FC<AreaChartProps> = ({
  data,
  height = CHART_SIZING.defaultHeight,
  loading = false,
  error,
  ariaLabel = 'Area chart showing data volumes',
}) => {
  // Handle loading state
  if (loading) {
    return (
      <div
        className="animate-pulse bg-mono-gray-50 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="status"
        aria-live="polite"
        aria-label="Loading area chart"
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
  const gradients = getMonochromeGradients();

  return (
    <div
      className="w-full"
      role="img"
      aria-label={ariaLabel}
      aria-describedby="area-chart-description"
    >
      <span id="area-chart-description" className="sr-only">
        Area chart displaying {data.length} data points showing volume trends
      </span>

      <ResponsiveContainer width="100%" height={height}>
        <RechartsAreaChart
          data={formattedData}
          margin={CHART_SIZING.margins}
          aria-hidden="true"
        >
          {/* Gradient definitions */}
          <defs>
            <linearGradient id="monochromeGradient" x1="0" y1="0" x2="0" y2="1">
              <stop
                offset="0%"
                stopColor={gradients.monochromeGradient.stops[0].stopColor}
                stopOpacity={gradients.monochromeGradient.stops[0].stopOpacity}
              />
              <stop
                offset="100%"
                stopColor={gradients.monochromeGradient.stops[1].stopColor}
                stopOpacity={gradients.monochromeGradient.stops[1].stopOpacity}
              />
            </linearGradient>
          </defs>

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

          {/* Area */}
          <Area
            type="monotone"
            dataKey="value"
            stroke={MONOCHROME_COLORS.primary}
            strokeWidth={1}
            fill="url(#monochromeGradient)"
            animationDuration={CHART_ANIMATIONS.duration}
            animationEasing={CHART_ANIMATIONS.easing}
          />
        </RechartsAreaChart>
      </ResponsiveContainer>
    </div>
  );
};

export default AreaChart;
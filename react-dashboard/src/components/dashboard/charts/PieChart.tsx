import React from 'react';
import {
  PieChart as RechartsPieChart,
  Pie,
  Cell,
  Tooltip,
  ResponsiveContainer,
  Legend,
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
 * Monochrome Pie Chart Component
 * Displays proportions with donut/pie visualization
 */
interface PieChartProps {
  /** Chart data points */
  data: ChartDataPoint[];
  /** Chart height in pixels */
  height?: number;
  /** Loading state */
  loading?: boolean;
  /** Error state */
  error?: string;
  /** Whether to show as donut chart */
  donut?: boolean;
  /** Inner radius for donut chart */
  innerRadius?: number;
  /** Accessibility label */
  ariaLabel?: string;
}

/**
 * Generate monochrome color palette for pie segments
 * Uses different shades of gray for visual distinction
 */
const getPieColors = (dataLength: number): string[] => {
  const baseColors = [
    MONOCHROME_COLORS.gray[400], // Medium gray
    MONOCHROME_COLORS.gray[500], // Medium dark gray
    MONOCHROME_COLORS.gray[600], // Dark gray
    MONOCHROME_COLORS.gray[300], // Light medium gray
    MONOCHROME_COLORS.gray[700], // Darker gray
    MONOCHROME_COLORS.gray[200], // Light gray
    MONOCHROME_COLORS.gray[800], // Very dark gray
    MONOCHROME_COLORS.gray[100], // Very light gray
  ];

  // Repeat colors if we have more data points than colors
  const colors: string[] = [];
  for (let i = 0; i < dataLength; i++) {
    colors.push(baseColors[i % baseColors.length]);
  }

  return colors;
};

const PieChart: React.FC<PieChartProps> = ({
  data,
  height = CHART_SIZING.defaultHeight,
  loading = false,
  error,
  donut = false,
  innerRadius = 60,
  ariaLabel = 'Pie chart showing data proportions',
}) => {
  // Handle loading state
  if (loading) {
    return (
      <div
        className="animate-pulse bg-mono-gray-50 rounded-lg flex items-center justify-center"
        style={{ height }}
        role="status"
        aria-live="polite"
        aria-label="Loading pie chart"
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
  const pieColors = getPieColors(data.length);

  // Calculate responsive inner radius
  const responsiveInnerRadius = donut ? Math.min(innerRadius, height * 0.2) : 0;

  return (
    <div
      className="w-full"
      role="img"
      aria-label={ariaLabel}
      aria-describedby="pie-chart-description"
    >
      <span id="pie-chart-description" className="sr-only">
        {donut ? 'Donut' : 'Pie'} chart displaying {data.length} data segments showing proportions
      </span>

      <ResponsiveContainer width="100%" height={height}>
        <RechartsPieChart aria-hidden="true">
          {/* Pie/Donut */}
          <Pie
            data={formattedData}
            cx="50%"
            cy="50%"
            innerRadius={responsiveInnerRadius}
            outerRadius={Math.min(height * 0.35, 120)}
            paddingAngle={2}
            dataKey="value"
            animationDuration={CHART_ANIMATIONS.duration}
            animationEasing={CHART_ANIMATIONS.easing}
          >
            {formattedData.map((_, index) => (
              <Cell
                key={`cell-${index}`}
                fill={pieColors[index]}
                stroke={MONOCHROME_COLORS.secondary}
                strokeWidth={1}
              />
            ))}
          </Pie>

          {/* Tooltip */}
          <Tooltip
            contentStyle={tooltipConfig.contentStyle}
            itemStyle={tooltipConfig.itemStyle}
            aria-live="polite"
          />

          {/* Legend */}
          <Legend
            verticalAlign="bottom"
            height={36}
            iconType="circle"
            wrapperStyle={{
              fontSize: '12px',
              fontFamily: 'system-ui, -apple-system, sans-serif',
              color: MONOCHROME_COLORS.gray[600],
            }}
          />
        </RechartsPieChart>
      </ResponsiveContainer>
    </div>
  );
};

export default PieChart;
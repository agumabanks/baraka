/**
 * Monochrome Chart Configuration
 * Steve Jobs minimalist aesthetic - grayscale only
 */

import type { ChartDataPoint } from '../types/dashboard';

/**
 * Monochrome color palette for charts
 * Only black, white, and shades of gray
 */
export const MONOCHROME_COLORS = {
  // Primary colors
  primary: '#000000', // Pure black
  secondary: '#FFFFFF', // Pure white

  // Gray scale palette
  gray: {
    50: '#FAFAFA',   // Very light gray
    100: '#F5F5F5',  // Light gray
    200: '#E5E5E5',  // Light medium gray
    300: '#D4D4D4',  // Medium light gray
    400: '#A3A3A3',  // Medium gray
    500: '#737373',  // Medium dark gray
    600: '#525252',  // Dark gray
    700: '#404040',  // Darker gray
    800: '#262626',  // Very dark gray
    900: '#171717',  // Almost black
  },

  // Semantic colors (grayscale only)
  success: '#525252', // Medium dark gray for success
  warning: '#737373', // Medium gray for warning
  error: '#404040',   // Dark gray for error
  neutral: '#A3A3A3', // Medium gray for neutral
} as const;

/**
 * Chart gradient definitions for area charts
 */
export const MONOCHROME_GRADIENTS = {
  primary: {
    start: MONOCHROME_COLORS.gray[100],
    end: MONOCHROME_COLORS.gray[300],
  },
  secondary: {
    start: MONOCHROME_COLORS.gray[200],
    end: MONOCHROME_COLORS.gray[400],
  },
  accent: {
    start: MONOCHROME_COLORS.gray[300],
    end: MONOCHROME_COLORS.gray[500],
  },
} as const;

/**
 * Recharts theme configuration
 * Monochrome styling for all chart elements
 */
export const MONOCHROME_CHART_THEME = {
  // Grid lines
  grid: {
    stroke: MONOCHROME_COLORS.gray[200],
    strokeDasharray: '3 3',
    strokeWidth: 1,
  },

  // Axes
  axis: {
    stroke: MONOCHROME_COLORS.gray[400],
    strokeWidth: 1,
    fontSize: 12,
    fontFamily: 'system-ui, -apple-system, sans-serif',
    fill: MONOCHROME_COLORS.gray[600],
  },

  // Tick marks
  tick: {
    fill: MONOCHROME_COLORS.gray[500],
    fontSize: 11,
  },

  // Chart elements
  line: {
    stroke: MONOCHROME_COLORS.primary,
    strokeWidth: 2,
    dot: {
      fill: MONOCHROME_COLORS.primary,
      strokeWidth: 2,
      r: 4,
    },
    activeDot: {
      fill: MONOCHROME_COLORS.primary,
      stroke: MONOCHROME_COLORS.secondary,
      strokeWidth: 2,
      r: 6,
    },
  },

  bar: {
    fill: MONOCHROME_COLORS.gray[400],
    stroke: MONOCHROME_COLORS.gray[600],
    strokeWidth: 1,
  },

  area: {
    fill: `url(#monochromeGradient)`,
    stroke: MONOCHROME_COLORS.primary,
    strokeWidth: 1,
  },

  pie: {
    fill: MONOCHROME_COLORS.gray[400],
    stroke: MONOCHROME_COLORS.secondary,
    strokeWidth: 1,
  },

  // Tooltip
  tooltip: {
    backgroundColor: MONOCHROME_COLORS.secondary,
    border: `1px solid ${MONOCHROME_COLORS.gray[300]}`,
    borderRadius: '6px',
    boxShadow: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`,
    fontSize: 12,
    fontFamily: 'system-ui, -apple-system, sans-serif',
    color: MONOCHROME_COLORS.gray[800],
  },

  // Legend
  legend: {
    fontSize: 12,
    fontFamily: 'system-ui, -apple-system, sans-serif',
    color: MONOCHROME_COLORS.gray[600],
  },

  // Responsive breakpoints
  responsive: {
    mobile: {
      fontSize: 10,
      tickFontSize: 9,
    },
    tablet: {
      fontSize: 11,
      tickFontSize: 10,
    },
    desktop: {
      fontSize: 12,
      tickFontSize: 11,
    },
  },
} as const;

/**
 * Animation configuration
 * Smooth, subtle animations
 */
export const CHART_ANIMATIONS = {
  duration: 1000,
  easing: 'ease-in-out',
  delay: 100,
} as const;

/**
 * Accessibility configuration
 * Screen reader support and keyboard navigation
 */
export const CHART_ACCESSIBILITY = {
  ariaLabel: 'Data visualization chart',
  role: 'img',
  tabIndex: 0,
  keyboardNavigation: true,
  screenReaderOnly: {
    announceData: true,
    describeTrends: true,
  },
} as const;

/**
 * Chart sizing configuration
 * Responsive dimensions
 */
export const CHART_SIZING = {
  defaultHeight: 300,
  minHeight: 200,
  maxHeight: 600,
  aspectRatio: 16 / 9,
  margins: {
    top: 20,
    right: 30,
    bottom: 60,
    left: 60,
  },
  responsive: {
    mobile: {
      height: 250,
      margins: { top: 15, right: 20, bottom: 40, left: 40 },
    },
    tablet: {
      height: 300,
      margins: { top: 20, right: 25, bottom: 50, left: 50 },
    },
    desktop: {
      height: 350,
      margins: { top: 20, right: 30, bottom: 60, left: 60 },
    },
  },
} as const;

/**
 * Format chart data for Recharts
 * Ensures consistent data structure
 */
export const formatChartData = (data: ChartDataPoint[]) => {
  return data.map((point, index) => ({
    ...point,
    // Ensure numeric values
    value: typeof point.value === 'number' ? point.value : parseFloat(point.value) || 0,
    // Add index for accessibility
    index,
  }));
};

/**
 * Generate monochrome gradient definitions for SVG
 * Returns gradient configuration object for Recharts
 */
export const getMonochromeGradients = () => ({
  monochromeGradient: {
    id: 'monochromeGradient',
    type: 'linear',
    x1: 0,
    y1: 0,
    x2: 0,
    y2: 1,
    stops: [
      {
        offset: '0%',
        stopColor: MONOCHROME_GRADIENTS.primary.start,
        stopOpacity: 0.8,
      },
      {
        offset: '100%',
        stopColor: MONOCHROME_GRADIENTS.primary.end,
        stopOpacity: 0.2,
      },
    ],
  },
  monochromeGradientSecondary: {
    id: 'monochromeGradientSecondary',
    type: 'linear',
    x1: 0,
    y1: 0,
    x2: 0,
    y2: 1,
    stops: [
      {
        offset: '0%',
        stopColor: MONOCHROME_GRADIENTS.secondary.start,
        stopOpacity: 0.8,
      },
      {
        offset: '100%',
        stopColor: MONOCHROME_GRADIENTS.secondary.end,
        stopOpacity: 0.2,
      },
    ],
  },
});

/**
 * Custom tooltip configuration for monochrome theme
 * Returns tooltip styling and content configuration
 */
export const getCustomTooltipConfig = () => ({
  contentStyle: {
    backgroundColor: MONOCHROME_COLORS.secondary,
    border: `1px solid ${MONOCHROME_COLORS.gray[300]}`,
    borderRadius: '6px',
    boxShadow: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`,
    fontSize: '12px',
    fontFamily: 'system-ui, -apple-system, sans-serif',
    color: MONOCHROME_COLORS.gray[800],
  },
  labelStyle: {
    color: MONOCHROME_COLORS.gray[800],
    fontWeight: 500,
    marginBottom: '8px',
  },
  itemStyle: {
    color: MONOCHROME_COLORS.gray[600],
  },
  cursor: {
    stroke: MONOCHROME_COLORS.gray[400],
    strokeWidth: 1,
    strokeDasharray: '3 3',
  },
});

/**
 * Responsive container configuration
 */
export const getResponsiveContainerProps = (height?: number) => ({
  width: '100%',
  height: height || CHART_SIZING.defaultHeight,
  style: {
    fontFamily: 'system-ui, -apple-system, sans-serif',
  },
});
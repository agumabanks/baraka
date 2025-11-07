/**
 * Analytics Platform TypeScript Type Definitions
 * Comprehensive real-time analytics for multi-tier reporting platform
 */

import type { KPICard, ChartDataPoint } from './dashboard';

export type { ChartDataPoint };

/**
 * Executive Dashboard Types
 */
export interface ExecutiveKPIs {
  revenue: KPICard;
  totalShipments: KPICard;
  activeCustomers: KPICard;
  operationalEfficiency: KPICard;
  profitability: KPICard;
  customerSatisfaction: KPICard;
}

export interface RealTimeMetrics {
  timestamp: string;
  activeOperations: number;
  pendingShipments: number;
  completedDeliveries: number;
  averageDeliveryTime: number;
  customerSatisfactionScore: number;
  systemHealth: 'healthy' | 'warning' | 'critical';
}

/**
 * Operational Reporting Types
 */
export interface OperationalMetrics {
  originDestinationAnalytics: {
    totalVolume: number;
    growthRate: number;
    topRoutes: RouteAnalytics[];
    geographicDistribution: GeographicData[];
  };
  routeEfficiency: {
    overallScore: number;
    routePerformance: RoutePerformance[];
    optimizationOpportunities: OptimizationOpportunity[];
  };
  onTimeDelivery: {
    rate: number;
    variance: number;
    trends: ChartDataPoint[];
    performanceByRegion: RegionPerformance[];
  };
  exceptionAnalysis: {
    totalExceptions: number;
    exceptionTypes: ExceptionType[];
    rootCauses: RootCauseAnalysis[];
  };
  driverPerformance: {
    rankings: DriverRanking[];
    utilization: ChartDataPoint[];
    performanceMetrics: DriverMetrics[];
  };
  containerUtilization: {
    utilizationRate: number;
    efficiencyScore: number;
    optimization: ContainerOptimization[];
  };
  transitTimeAnalysis: {
    averageTime: number;
    bottleneckAnalysis: BottleneckAnalysis[];
    improvementOpportunities: ImprovementOpportunity[];
  };
}

export interface RouteAnalytics {
  route: string;
  origin: string;
  destination: string;
  volume: number;
  revenue: number;
  efficiency: number;
  coordinates: [number, number][];
}

export interface GeographicData {
  region: string;
  coordinates: [number, number];
  value: number;
  shipmentCount: number;
  revenue: number;
}

export interface RoutePerformance {
  routeId: string;
  routeName: string;
  efficiency: number;
  onTimeRate: number;
  cost: number;
  utilization: number;
}

export interface OptimizationOpportunity {
  type: 'cost' | 'time' | 'efficiency';
  description: string;
  potentialSavings: number;
  implementation: string[];
}

export interface RegionPerformance {
  region: string;
  onTimeRate: number;
  deliveryCount: number;
  averageTime: number;
  performance: 'excellent' | 'good' | 'fair' | 'poor';
}

export interface ExceptionType {
  type: string;
  count: number;
  percentage: number;
  impact: 'low' | 'medium' | 'high' | 'critical';
  trend: 'up' | 'down' | 'stable';
}

export interface RootCauseAnalysis {
  cause: string;
  frequency: number;
  affectedShipments: number;
  resolutionTime: number;
  prevention: string[];
}

export interface DriverRanking {
  driverId: string;
  driverName: string;
  performance: number;
  deliveries: number;
  rating: number;
  efficiency: number;
}

export interface DriverMetrics {
  driverId: string;
  onTimeRate: number;
  customerRating: number;
  fuelEfficiency: number;
  safetyScore: number;
}

export interface ContainerOptimization {
  containerId: string;
  currentUtilization: number;
  optimizedUtilization: number;
  potentialSavings: number;
}

export interface BottleneckAnalysis {
  location: string;
  type: 'processing' | 'transport' | 'customs' | 'delivery';
  averageDelay: number;
  impactScore: number;
}

export interface ImprovementOpportunity {
  area: string;
  potentialTimeSaving: number;
  costImpact: number;
  priority: 'high' | 'medium' | 'low';
}

/**
 * Financial Reporting Types
 */
export interface FinancialMetrics {
  revenueRecognition: {
    totalRevenue: number;
    recognized: number;
    pending: number;
    breakdown: RevenueBreakdown[];
  };
  cogsAnalysis: {
    totalCOGS: number;
    categories: COGSCategory[];
    trends: ChartDataPoint[];
  };
  grossMarginAnalysis: {
    margin: number;
    marginByProduct: ProductMargin[];
    forecasting: MarginForecast[];
  };
  codCollection: {
    totalCOD: number;
    collected: number;
    pending: number;
    aging: CODAging[];
  };
  paymentProcessing: {
    processed: number;
    pending: number;
    failed: number;
    volumes: PaymentVolume[];
  };
  profitabilityAnalysis: {
    netProfit: number;
    profitMargins: ProfitMargin[];
    byRegion: RegionProfitability[];
    byCustomer: CustomerProfitability[];
  };
}

export interface RevenueBreakdown {
  category: string;
  amount: number;
  percentage: number;
  trend: ChartDataPoint[];
}

export interface COGSCategory {
  category: string;
  amount: number;
  percentage: number;
  supplierCosts: SupplierCost[];
}

export interface SupplierCost {
  supplier: string;
  cost: number;
  contracts: ContractInfo[];
}

export interface ContractInfo {
  contractId: string;
  value: number;
  terms: string;
  renewalDate: string;
}

export interface ProductMargin {
  productId: string;
  productName: string;
  margin: number;
  marginPercentage: number;
  volume: number;
}

export interface MarginForecast {
  period: string;
  predictedMargin: number;
  confidence: number;
  factors: string[];
}

export interface CODAging {
  period: string;
  amount: number;
  count: number;
  risk: 'low' | 'medium' | 'high';
}

export interface PaymentVolume {
  method: string;
  volume: number;
  amount: number;
  fees: number;
}

export interface ProfitMargin {
  dimension: string;
  margin: number;
  trend: ChartDataPoint[];
}

export interface RegionProfitability {
  region: string;
  profit: number;
  margin: number;
  growth: number;
}

export interface CustomerProfitability {
  customerId: string;
  customerName: string;
  profit: number;
  margin: number;
  lifetimeValue: number;
}

/**
 * Customer Intelligence Types
 */
export interface CustomerIntelligence {
  activityMonitoring: {
    activeCustomers: number;
    activityTrends: ChartDataPoint[];
    engagement: EngagementMetrics[];
  };
  dormantAccountDetection: {
    dormantCount: number;
    riskCategories: RiskCategory[];
    reactivation: ReactivationStrategy[];
  };
  valueAnalysis: {
    averageShipmentValue: number;
    valueTrends: ChartDataPoint[];
    highValueCustomers: HighValueCustomer[];
  };
  sentimentAnalysis: {
    overallSentiment: number;
    sentimentTrends: ChartDataPoint[];
    feedbackCategories: FeedbackCategory[];
  };
  satisfactionMetrics: {
    npsScore: number;
    csatScore: number;
    satisfactionBy: SatisfactionBreakdown[];
  };
  clvCalculations: {
    totalCLV: number;
    clvBySegment: SegmentCLV[];
    clvTrends: ChartDataPoint[];
  };
  churnPrediction: {
    riskScore: number;
    riskFactors: ChurnRiskFactor[];
    predictions: ChurnPrediction[];
  };
  customerSegmentation: {
    segments: CustomerSegment[];
    segmentAnalysis: SegmentAnalysis[];
  };
  automatedAlerts: {
    activeAlerts: Alert[];
    alertTypes: AlertType[];
    escalationRules: EscalationRule[];
  };
}

export interface EngagementMetrics {
  customerId: string;
  engagementScore: number;
  activityLevel: 'high' | 'medium' | 'low';
  lastActivity: string;
  preferredChannels: string[];
}

export interface RiskCategory {
  category: string;
  count: number;
  risk: 'low' | 'medium' | 'high';
  recommendedAction: string;
}

export interface ReactivationStrategy {
  strategy: string;
  successRate: number;
  implementation: string[];
  cost: number;
}

export interface HighValueCustomer {
  customerId: string;
  customerName: string;
  totalValue: number;
  averageValue: number;
  frequency: number;
}

export interface FeedbackCategory {
  category: string;
  positive: number;
  negative: number;
  neutral: number;
  sentiment: number;
}

export interface SatisfactionBreakdown {
  dimension: string;
  score: number;
  change: number;
  benchmark: number;
}

export interface SegmentCLV {
  segment: string;
  clv: number;
  customerCount: number;
  averageCLV: number;
}

export interface ChurnRiskFactor {
  factor: string;
  impact: number;
  weight: number;
  actionable: boolean;
}

export interface ChurnPrediction {
  customerId: string;
  customerName: string;
  riskScore: number;
  riskLevel: 'low' | 'medium' | 'high' | 'critical';
  lastActivity: string;
  recommendedAction: string;
}

export interface CustomerSegment {
  segmentId: string;
  name: string;
  description: string;
  size: number;
  characteristics: string[];
}

export interface SegmentAnalysis {
  segment: string;
  revenue: number;
  satisfaction: number;
  retention: number;
  profitability: number;
}

export interface Alert {
  id: string;
  type: string;
  priority: 'low' | 'medium' | 'high' | 'critical';
  message: string;
  timestamp: string;
  status: 'active' | 'resolved' | 'acknowledged';
}

export interface AlertType {
  type: string;
  count: number;
  escalationTime: number;
  autoResolve: boolean;
}

export interface EscalationRule {
  condition: string;
  action: string;
  timeout: number;
  recipients: string[];
}

/**
 * Real-time Streaming Types
 */
export interface StreamingMetrics {
  connections: number;
  updatesPerSecond: number;
  dataPoints: DataPointStream[];
}

export interface DataPointStream {
  metric: string;
  value: number;
  timestamp: string;
  metadata?: Record<string, unknown>;
}

export interface WebSocketMessage {
  type: 'metric' | 'alert' | 'update' | 'notification';
  payload: unknown;
  timestamp: string;
  source: string;
}

/**
 * Advanced Analytics Types
 */
export interface DrillDownContext {
  parentId: string;
  level: number;
  filters: Record<string, unknown>;
  path: string[];
}

export interface FilterConfig {
  id: string;
  label: string;
  type: 'date' | 'select' | 'range' | 'text' | 'multiselect';
  options?: Array<{ value: string; label: string }>;
  value: unknown;
  operator?: 'eq' | 'ne' | 'gt' | 'lt' | 'in' | 'like';
}

export interface ExportConfig {
  format: 'csv' | 'excel' | 'pdf' | 'json';
  data: unknown;
  filename: string;
  filters?: FilterConfig[];
  columns?: string[];
}

export interface DashboardLayout {
  id: string;
  name: string;
  widgets: WidgetConfig[];
  isDefault: boolean;
  permissions: string[];
}

export interface WidgetConfig {
  id: string;
  type: 'kpi' | 'chart' | 'map' | 'table' | 'list';
  title: string;
  position: { x: number; y: number; w: number; h: number };
  config: Record<string, unknown>;
  dataSource: string;
  refreshInterval?: number;
  filters?: FilterConfig[];
}

/**
 * Geographic Visualization Types
 */
export interface MapConfig {
  center: [number, number];
  zoom: number;
  bounds?: [[number, number], [number, number]];
  style: 'roadmap' | 'satellite' | 'hybrid' | 'terrain';
}

export interface HeatMapData {
  coordinates: [number, number];
  intensity: number;
  color: string;
  metadata?: Record<string, unknown>;
}

export interface RouteVisualization {
  routeId: string;
  coordinates: [number, number][];
  style: {
    color: string;
    weight: number;
    opacity: number;
  };
  markers: MapMarker[];
}

export interface MapMarker {
  coordinates: [number, number];
  type: 'origin' | 'destination' | 'hub' | 'depot' | 'customer';
  title: string;
  info: string;
  icon?: string;
}

/**
 * Performance and Optimization Types
 */
export interface PerformanceMetrics {
  loadTime: number;
  renderTime: number;
  dataSize: number;
  cacheHitRate: number;
  errorRate: number;
}

export interface CacheConfig {
  ttl: number;
  key: string;
  strategy: 'memory' | 'localStorage' | 'indexedDB';
  invalidateOn: string[];
}

/**
 * Analytics Dashboard State
 */
export interface AnalyticsState {
  currentDashboard: 'executive' | 'operational' | 'financial' | 'customer';
  selectedFilters: FilterConfig[];
  timeRange: { start: string; end: string; preset: string };
  isRealTimeEnabled: boolean;
  autoRefresh: boolean;
  refreshInterval: number;
  selectedWidgets: string[];
  layout: DashboardLayout;
}

/**
 * API Response Types for Analytics
 */
export interface AnalyticsApiResponse<T> {
  success: boolean;
  data: T;
  metadata: {
    timestamp: string;
    source: string;
    version: string;
    totalRecords: number;
  };
  performance: {
    loadTime: number;
    cacheHit: boolean;
  };
}

/**
 * Error and Loading States
 */
export interface AnalyticsError {
  code: string;
  message: string;
  details?: Record<string, unknown>;
  timestamp: string;
  recoverable: boolean;
}

export interface AnalyticsLoading {
  dashboard: boolean;
  data: boolean;
  filters: boolean;
  export: boolean;
}

/**
 * Theme and Accessibility Types
 */
export interface AnalyticsTheme {
  primary: string;
  secondary: string;
  accent: string;
  background: string;
  text: string;
  border: string;
  success: string;
  warning: string;
  error: string;
}

export interface AccessibilityConfig {
  enableScreenReader: boolean;
  highContrast: boolean;
  largeText: boolean;
  reducedMotion: boolean;
  keyboardNavigation: boolean;
}

/**
 * Permission and Security Types
 */
export interface AnalyticsPermission {
  module: 'executive' | 'operational' | 'financial' | 'customer' | 'system';
  actions: ('read' | 'write' | 'export' | 'admin')[];
  restrictions?: Record<string, unknown>;
}

export interface SecurityConfig {
  encryption: boolean;
  auditLog: boolean;
  dataMasking: boolean;
  accessControl: 'rbac' | 'abac' | 'custom';
}
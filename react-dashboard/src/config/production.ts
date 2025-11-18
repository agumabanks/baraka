/**
 * Production Deployment Configuration
 * DHL-grade production-ready settings
 */

export const productionConfig = {
  // Performance Optimization
  performance: {
    enableServiceWorker: true,
    enablePWA: true,
    enableLazyLoading: true,
    enableBundleSplitting: true,
    cacheTimeout: 5 * 60 * 1000, // 5 minutes
    staleWhileRevalidate: true,
  },

  // Security Settings
  security: {
    enableCSP: true,
    enableHTTPS: true,
    enableSecureHeaders: true,
    enableXSSProtection: true,
    enableContentTypeOptions: true,
  },

  // Real-time Features
  realTime: {
    enableWebSocket: true,
    enableSSE: true,
    heartbeatInterval: 30000, // 30 seconds
    reconnectionDelay: 1000,
    maxReconnectionAttempts: 5,
  },

  // Monitoring & Analytics
  monitoring: {
    enablePerformanceMonitoring: true,
    enableErrorTracking: true,
    enableUserAnalytics: true,
    enableCustomEvents: true,
    enableCoreWebVitals: true,
  },

  // Accessibility
  accessibility: {
    enableScreenReaderSupport: true,
    enableKeyboardNavigation: true,
    enableHighContrast: true,
    enableReducedMotion: true,
    enableFocusManagement: true,
  },

  // Internationalization
  i18n: {
    defaultLanguage: 'en',
    supportedLanguages: ['en', 'fr', 'sw'],
    enableRTL: false,
    fallbackLanguage: 'en',
  },

  // Theme & UI
  ui: {
    enableDarkMode: true,
    enableHighContrast: true,
    enableCustomThemes: true,
    enableResponsiveDesign: true,
    enableMobileOptimizations: true,
  },
} as const;

// Environment-specific configurations
export const environments = {
  development: {
    ...productionConfig,
    monitoring: {
      ...productionConfig.monitoring,
      enableDebugMode: true,
      enableVerboseLogging: true,
    },
  },
  
  staging: {
    ...productionConfig,
    performance: {
      ...productionConfig.performance,
      cacheTimeout: 2 * 60 * 1000, // 2 minutes in staging
    },
  },
  
  production: {
    ...productionConfig,
    monitoring: {
      ...productionConfig.monitoring,
      enableDebugMode: false,
      enableVerboseLogging: false,
    },
  },
} as const;

export type ProductionConfig = typeof productionConfig;
export type Environment = keyof typeof environments;
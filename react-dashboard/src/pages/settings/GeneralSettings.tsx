import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import { generalSettingsApi } from '../../services/api';

type SettingsPayload = Record<string, any>;
type CurrenciesPayload = SettingsPayload & {
  currencies?: Array<Record<string, any>> | string[];
};

type SettingEntry = {
  key: string;
  label: string;
  value: unknown;
};

type SettingsSection = {
  id: string;
  label: string;
  entries: SettingEntry[];
};

const CATEGORY_DEFINITIONS: Array<{ id: string; label: string; keywords: string[] }> = [
  { id: 'general', label: 'General', keywords: ['app', 'brand', 'company', 'site', 'support', 'timezone', 'locale', 'logo', 'address'] },
  { id: 'operations', label: 'Operations', keywords: ['workflow', 'parcel', 'shipment', 'pickup', 'delivery', 'booking', 'hub', 'route'] },
  { id: 'finance', label: 'Finance', keywords: ['cod', 'charge', 'settlement', 'invoice', 'payment', 'tax', 'currency', 'finance'] },
  { id: 'notifications', label: 'Notifications', keywords: ['notification', 'email', 'sms', 'alert', 'webhook'] },
  { id: 'integrations', label: 'Integrations', keywords: ['api', 'integration', 'token', 'key', 'client', 'webhook'] },
];

const FALLBACK_CATEGORY = CATEGORY_DEFINITIONS[0]!.id;

type RoundingMode = 'none' | 'nearest' | 'up' | 'down';

type Preferences = {
  general: {
    tagline: string;
    support_email: string;
    timezone: string;
    country: string;
  };
  branding: {
    theme: 'light' | 'dark' | 'system';
    sidebar_density: 'compact' | 'comfortable' | 'spacious';
    enable_animations: boolean;
  };
  operations: {
    auto_assign_drivers: boolean;
    enable_capacity_management: boolean;
    require_dispatch_approval: boolean;
    auto_generate_tracking_ids: boolean;
    enforce_pod_otp: boolean;
    allow_public_tracking: boolean;
  };
  finance: {
    auto_reconcile: boolean;
    enforce_cod_settlement_workflow: boolean;
    enable_invoice_emails: boolean;
    default_tax_rate: number;
    rounding_mode: RoundingMode;
  };
  notifications: {
    email: boolean;
    sms: boolean;
    push: boolean;
    daily_digest: boolean;
    escalate_incidents: boolean;
  };
  integrations: {
    webhooks_enabled: boolean;
    webhooks_url: string;
    slack_enabled: boolean;
    slack_channel: string;
    power_bi_enabled: boolean;
    zapier_enabled: boolean;
    analytics_tracking_id: string;
  };
  system: {
    maintenance_mode: boolean;
    two_factor_required: boolean;
    allow_self_service: boolean;
    auto_logout_minutes: number;
    data_retention_days: number;
  };
  website: {
    hero_title: string;
    hero_subtitle: string;
    hero_cta_label: string;
    footer_note: string;
  };
};

type FormState = {
  general: {
    companyName: string;
    companyEmail: string;
    supportEmail: string;
    phone: string;
    address: string;
    country: string;
    timezone: string;
    currency: string;
    copyright: string;
  };
  branding: {
    tagline: string;
    theme: Preferences['branding']['theme'];
    sidebarDensity: Preferences['branding']['sidebar_density'];
    enableAnimations: boolean;
    primaryColor: string;
    textColor: string;
    logoUrl: string | null;
    lightLogoUrl: string | null;
    faviconUrl: string | null;
    logoFile: File | null;
    lightLogoFile: File | null;
    faviconFile: File | null;
  };
  operations: {
    autoAssignDrivers: boolean;
    enableCapacityManagement: boolean;
    requireDispatchApproval: boolean;
    autoGenerateTrackingIds: boolean;
    enforcePodOtp: boolean;
    allowPublicTracking: boolean;
  };
  finance: {
    autoReconcile: boolean;
    enforceCodWorkflow: boolean;
    enableInvoiceEmails: boolean;
    defaultTaxRate: number;
    roundingMode: RoundingMode;
    invoicePrefix: string;
    trackingPrefix: string;
  };
  notifications: {
    email: boolean;
    sms: boolean;
    push: boolean;
    dailyDigest: boolean;
    escalateIncidents: boolean;
  };
  integrations: {
    webhooksEnabled: boolean;
    webhooksUrl: string;
    slackEnabled: boolean;
    slackChannel: string;
    powerBiEnabled: boolean;
    zapierEnabled: boolean;
    analyticsTrackingId: string;
  };
  system: {
    maintenanceMode: boolean;
    twoFactorRequired: boolean;
    allowSelfService: boolean;
    autoLogoutMinutes: number;
    dataRetentionDays: number;
  };
  website: {
    heroTitle: string;
    heroSubtitle: string;
    ctaLabel: string;
    footerNote: string;
  };
};

const DRAFT_STORAGE_KEY = 'baraka.settings.general.draft';

type DraftSnapshot = {
  state: FormState;
  updatedAt: string;
};

type SpotlightMatch = {
  id: string;
  label: string;
  sectionId: string;
  sectionLabel: string;
  preview: string;
};

type ModuleTile = {
  id: string;
  label: string;
  description: string;
  icon: string;
  sectionId: string;
  accent: string;
  status: string;
  hint: string;
};

const toSerializableFormState = (state: FormState): FormState => ({
  ...state,
  branding: {
    ...state.branding,
    logoFile: null,
    lightLogoFile: null,
    faviconFile: null,
  },
});

const fingerprintFormState = (state: FormState): string => {
  try {
    return JSON.stringify(toSerializableFormState(state));
  } catch {
    return '';
  }
};

const deepMergeFormStates = (base: FormState, overrides: FormState): FormState => ({
  ...base,
  general: { ...base.general, ...overrides.general },
  branding: {
    ...base.branding,
    ...overrides.branding,
    logoFile: null,
    lightLogoFile: null,
    faviconFile: null,
  },
  operations: { ...base.operations, ...overrides.operations },
  finance: { ...base.finance, ...overrides.finance },
  notifications: { ...base.notifications, ...overrides.notifications },
  integrations: { ...base.integrations, ...overrides.integrations },
  system: { ...base.system, ...overrides.system },
  website: { ...base.website, ...overrides.website },
});

const formatRelativeTime = (input: string | number | Date): string => {
  const date = input instanceof Date ? input : new Date(input);
  const timestamp = date.getTime();
  if (Number.isNaN(timestamp)) {
    return 'moments ago';
  }

  const diff = Date.now() - timestamp;
  if (diff < 5_000) {
    return 'just now';
  }
  if (diff < 60_000) {
    return `${Math.round(diff / 1_000)}s ago`;
  }
  if (diff < 3_600_000) {
    return `${Math.round(diff / 60_000)}m ago`;
  }
  if (diff < 86_400_000) {
    return `${Math.round(diff / 3_600_000)}h ago`;
  }
  return `${Math.round(diff / 86_400_000)}d ago`;
};

const formatExactTimestamp = (input: string | number | Date): string => {
  const date = input instanceof Date ? input : new Date(input);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }
  return new Intl.DateTimeFormat('en-GB', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date);
};

const getStorage = (): Storage | null => {
  if (typeof window === 'undefined') {
    return null;
  }
  return window.localStorage;
};

const persistDraftSnapshot = (state: FormState): DraftSnapshot | null => {
  const storage = getStorage();
  if (!storage) {
    return null;
  }

  const snapshot: DraftSnapshot = {
    state: toSerializableFormState(state),
    updatedAt: new Date().toISOString(),
  };

  try {
    storage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(snapshot));
  } catch (error) {
    console.warn('Unable to cache general settings draft', error);
    return null;
  }

  return snapshot;
};

const readDraftSnapshot = (): DraftSnapshot | null => {
  const storage = getStorage();
  if (!storage) {
    return null;
  }

  try {
    const raw = storage.getItem(DRAFT_STORAGE_KEY);
    if (!raw) {
      return null;
    }
    const parsed = JSON.parse(raw) as DraftSnapshot;
    if (!parsed?.state) {
      return null;
    }
    return parsed;
  } catch (error) {
    console.warn('Unable to parse general settings draft', error);
    return null;
  }
};

const clearDraftSnapshot = () => {
  const storage = getStorage();
  if (!storage) {
    return;
  }
  try {
    storage.removeItem(DRAFT_STORAGE_KEY);
  } catch (error) {
    console.warn('Unable to clear general settings draft', error);
  }
};

const toPreviewString = (value: unknown): string => {
  if (typeof value === 'string') {
    return value;
  }
  if (typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }
  if (Array.isArray(value)) {
    return value
      .slice(0, 3)
      .map((item) => {
        if (typeof item === 'string') {
          return item;
        }
        if (typeof item === 'number' || typeof item === 'boolean') {
          return String(item);
        }
        return '…';
      })
      .join(', ');
  }
  if (value && typeof value === 'object') {
    try {
      return JSON.stringify(value);
    } catch {
      return '[object]';
    }
  }
  return '';
};


type StatusMessage = {
  type: 'success' | 'error';
  message: string;
};

type DataError = {
  id: string;
  title: string;
  message: string;
  retry: () => void;
};

const CONTROL_SECTIONS: Array<{ id: string; label: string; description: string }> = [
  { id: 'overview', label: 'Overview', description: 'Snapshot of key preferences, uptime, and branding.' },
  { id: 'brand', label: 'Brand Identity', description: 'Logos, colors, and customer-facing details.' },
  { id: 'website', label: 'Website', description: 'Landing content, hero copy, and marketing details.' },
  { id: 'operations', label: 'Operations', description: 'Logistics automation, tracking, and execution policies.' },
  { id: 'finance', label: 'Finance & Billing', description: 'Settlement workflows and charge calculations.' },
  { id: 'notifications', label: 'Notifications', description: 'Email, SMS, push, and escalation rules.' },
  { id: 'integrations', label: 'Integrations', description: 'Webhook, Slack, analytics, and external tooling.' },
  { id: 'system', label: 'System Controls', description: 'Platform hardening, retention, and security.' },
  { id: 'raw', label: 'Data Explorer', description: 'Low-level configuration direct from Laravel.' },
];

const THEME_OPTIONS: Array<{ value: Preferences['branding']['theme']; label: string }> = [
  { value: 'light', label: 'Light' },
  { value: 'dark', label: 'Dark' },
  { value: 'system', label: 'System' },
];

const DENSITY_OPTIONS: Array<{ value: Preferences['branding']['sidebar_density']; label: string }> = [
  { value: 'compact', label: 'Compact' },
  { value: 'comfortable', label: 'Comfortable' },
  { value: 'spacious', label: 'Spacious' },
];

const ROUNDING_OPTIONS: Array<{ value: RoundingMode; label: string }> = [
  { value: 'nearest', label: 'Nearest' },
  { value: 'none', label: 'None' },
  { value: 'up', label: 'Always Up' },
  { value: 'down', label: 'Always Down' },
];

const TIMEZONE_OPTIONS: string[] = (() => {
  try {
    const supported = (Intl as any)?.supportedValuesOf?.('timeZone');
    if (Array.isArray(supported) && supported.length > 0) {
      return supported;
    }
  } catch (error) {
    console.debug('Timezone detection fallback', error);
  }

  return ['Africa/Kampala', 'Africa/Nairobi', 'Africa/Dar_es_Salaam', 'UTC'];
})();

const getDefaultPreferences = (): Preferences => ({
  general: {
    tagline: '',
    support_email: '',
    timezone: 'Africa/Kampala',
    country: 'Uganda',
  },
  branding: {
    theme: 'light',
    sidebar_density: 'comfortable',
    enable_animations: true,
  },
  operations: {
    auto_assign_drivers: false,
    enable_capacity_management: false,
    require_dispatch_approval: true,
    auto_generate_tracking_ids: true,
    enforce_pod_otp: true,
    allow_public_tracking: true,
  },
  finance: {
    auto_reconcile: false,
    enforce_cod_settlement_workflow: true,
    enable_invoice_emails: true,
    default_tax_rate: 0,
    rounding_mode: 'nearest',
  },
  notifications: {
    email: true,
    sms: false,
    push: true,
    daily_digest: true,
    escalate_incidents: false,
  },
  integrations: {
    webhooks_enabled: true,
    webhooks_url: '',
    slack_enabled: false,
    slack_channel: '',
    power_bi_enabled: false,
    zapier_enabled: false,
    analytics_tracking_id: '',
  },
  system: {
    maintenance_mode: false,
    two_factor_required: false,
    allow_self_service: true,
    auto_logout_minutes: 60,
    data_retention_days: 365,
  },
  website: {
    hero_title: 'Deliver with confidence',
    hero_subtitle: 'Baraka routes, tracks, and reconciles every parcel in real time.',
    hero_cta_label: 'Book a pickup',
    footer_note: 'Baraka ERP v1.0 • Crafted in Kampala',
  },
});

const deepClone = <T,>(value: T): T => {
  const structuredCloneFn = (globalThis as any).structuredClone;
  if (typeof structuredCloneFn === 'function') {
    return structuredCloneFn(value);
  }

  return JSON.parse(JSON.stringify(value)) as T;
};

const mergePreferenceSets = (defaults: Preferences, existing?: unknown): Preferences => {
  if (!existing || typeof existing !== 'object') {
    return deepClone(defaults);
  }

  const target = deepClone(defaults);

  const merge = (current: Record<string, unknown>, overrides: Record<string, unknown>) => {
    Object.entries(overrides).forEach(([key, overrideValue]) => {
      if (overrideValue === undefined || overrideValue === null) {
        return;
      }

      if (typeof overrideValue === 'object' && !Array.isArray(overrideValue)) {
        const base = typeof current[key] === 'object' && !Array.isArray(current[key]) ? (current[key] as Record<string, unknown>) : {};
        current[key] = merge(base, overrideValue as Record<string, unknown>);
        return;
      }

      current[key] = overrideValue;
    });

    return current;
  };

  return merge(target, existing as Record<string, unknown>) as Preferences;
};

const buildFormState = (settings: SettingsPayload, preferences: Preferences): FormState => {
  return {
    general: {
      companyName: String(settings.name ?? ''),
      companyEmail: String(settings.email ?? ''),
      supportEmail: String(preferences.general.support_email ?? settings.email ?? ''),
      phone: String(settings.phone ?? ''),
      address: String(settings.address ?? ''),
      country: String(preferences.general.country ?? ''),
      timezone: String(preferences.general.timezone ?? 'Africa/Kampala'),
      currency: String(settings.currency ?? ''),
      copyright: String(settings.copyright ?? ''),
    },
    branding: {
      tagline: String(preferences.general.tagline ?? ''),
      theme: preferences.branding.theme ?? 'light',
      sidebarDensity: preferences.branding.sidebar_density ?? 'comfortable',
      enableAnimations: Boolean(preferences.branding.enable_animations),
      primaryColor: String(settings.primary_color ?? '#111827'),
      textColor: String(settings.text_color ?? '#ffffff'),
      logoUrl: typeof settings.logo_image === 'string' ? settings.logo_image : null,
      lightLogoUrl: typeof settings.light_logo_image === 'string' ? settings.light_logo_image : null,
      faviconUrl: typeof settings.favicon_image === 'string' ? settings.favicon_image : null,
      logoFile: null,
      lightLogoFile: null,
      faviconFile: null,
    },
    operations: {
      autoAssignDrivers: Boolean(preferences.operations.auto_assign_drivers),
      enableCapacityManagement: Boolean(preferences.operations.enable_capacity_management),
      requireDispatchApproval: Boolean(preferences.operations.require_dispatch_approval),
      autoGenerateTrackingIds: Boolean(preferences.operations.auto_generate_tracking_ids),
      enforcePodOtp: Boolean(preferences.operations.enforce_pod_otp),
      allowPublicTracking: Boolean(preferences.operations.allow_public_tracking),
    },
    finance: {
      autoReconcile: Boolean(preferences.finance.auto_reconcile),
      enforceCodWorkflow: Boolean(preferences.finance.enforce_cod_settlement_workflow),
      enableInvoiceEmails: Boolean(preferences.finance.enable_invoice_emails),
      defaultTaxRate: Number(preferences.finance.default_tax_rate ?? 0),
      roundingMode: preferences.finance.rounding_mode ?? 'nearest',
      invoicePrefix: String(settings.invoice_prefix ?? ''),
      trackingPrefix: String(settings.par_track_prefix ?? ''),
    },
    notifications: {
      email: Boolean(preferences.notifications.email),
      sms: Boolean(preferences.notifications.sms),
      push: Boolean(preferences.notifications.push),
      dailyDigest: Boolean(preferences.notifications.daily_digest),
      escalateIncidents: Boolean(preferences.notifications.escalate_incidents),
    },
    integrations: {
      webhooksEnabled: Boolean(preferences.integrations.webhooks_enabled),
      webhooksUrl: String(preferences.integrations.webhooks_url ?? ''),
      slackEnabled: Boolean(preferences.integrations.slack_enabled),
      slackChannel: String(preferences.integrations.slack_channel ?? ''),
      powerBiEnabled: Boolean(preferences.integrations.power_bi_enabled),
      zapierEnabled: Boolean(preferences.integrations.zapier_enabled),
      analyticsTrackingId: String(preferences.integrations.analytics_tracking_id ?? ''),
    },
    system: {
      maintenanceMode: Boolean(preferences.system.maintenance_mode),
      twoFactorRequired: Boolean(preferences.system.two_factor_required),
      allowSelfService: Boolean(preferences.system.allow_self_service),
      autoLogoutMinutes: Number(preferences.system.auto_logout_minutes ?? 60),
      dataRetentionDays: Number(preferences.system.data_retention_days ?? 365),
    },
    website: {
      heroTitle: String(preferences.website?.hero_title ?? preferences.general.tagline ?? 'Deliver with confidence'),
      heroSubtitle: String(
        preferences.website?.hero_subtitle ?? 'Baraka routes, tracks, and reconciles every parcel in real time.',
      ),
      ctaLabel: String(preferences.website?.hero_cta_label ?? 'Book a pickup'),
      footerNote: String(preferences.website?.footer_note ?? settings.copyright ?? 'Baraka ERP v1.0 • Crafted in Kampala'),
    },
  };
};

const extractPreferencesFromState = (state: FormState): Preferences => ({
  general: {
    tagline: state.branding.tagline,
    support_email: state.general.supportEmail,
    timezone: state.general.timezone,
    country: state.general.country,
  },
  branding: {
    theme: state.branding.theme,
    sidebar_density: state.branding.sidebarDensity,
    enable_animations: state.branding.enableAnimations,
  },
  operations: {
    auto_assign_drivers: state.operations.autoAssignDrivers,
    enable_capacity_management: state.operations.enableCapacityManagement,
    require_dispatch_approval: state.operations.requireDispatchApproval,
    auto_generate_tracking_ids: state.operations.autoGenerateTrackingIds,
    enforce_pod_otp: state.operations.enforcePodOtp,
    allow_public_tracking: state.operations.allowPublicTracking,
  },
  finance: {
    auto_reconcile: state.finance.autoReconcile,
    enforce_cod_settlement_workflow: state.finance.enforceCodWorkflow,
    enable_invoice_emails: state.finance.enableInvoiceEmails,
    default_tax_rate: state.finance.defaultTaxRate,
    rounding_mode: state.finance.roundingMode,
  },
  notifications: {
    email: state.notifications.email,
    sms: state.notifications.sms,
    push: state.notifications.push,
    daily_digest: state.notifications.dailyDigest,
    escalate_incidents: state.notifications.escalateIncidents,
  },
  integrations: {
    webhooks_enabled: state.integrations.webhooksEnabled,
    webhooks_url: state.integrations.webhooksUrl,
    slack_enabled: state.integrations.slackEnabled,
    slack_channel: state.integrations.slackChannel,
    power_bi_enabled: state.integrations.powerBiEnabled,
    zapier_enabled: state.integrations.zapierEnabled,
    analytics_tracking_id: state.integrations.analyticsTrackingId,
  },
  system: {
    maintenance_mode: state.system.maintenanceMode,
    two_factor_required: state.system.twoFactorRequired,
    allow_self_service: state.system.allowSelfService,
    auto_logout_minutes: state.system.autoLogoutMinutes,
    data_retention_days: state.system.dataRetentionDays,
  },
  website: {
    hero_title: state.website.heroTitle,
    hero_subtitle: state.website.heroSubtitle,
    hero_cta_label: state.website.ctaLabel,
    footer_note: state.website.footerNote,
  },
});

const buildFormDataFromState = (state: FormState): FormData => {
  const formData = new FormData();
  formData.set('name', state.general.companyName);
  formData.set('email', state.general.companyEmail);
  formData.set('phone', state.general.phone);
  formData.set('address', state.general.address);
  formData.set('currency', state.general.currency);
  formData.set('copyright', state.general.copyright);
  formData.set('par_track_prefix', state.finance.trackingPrefix);
  formData.set('invoice_prefix', state.finance.invoicePrefix);
  formData.set('primary_color', state.branding.primaryColor);
  formData.set('text_color', state.branding.textColor);

  const preferencesPayload = extractPreferencesFromState(state);
  formData.set('preferences', JSON.stringify(preferencesPayload));

  if (state.branding.logoFile) {
    formData.set('logo', state.branding.logoFile);
  }
  if (state.branding.lightLogoFile) {
    formData.set('light_logo', state.branding.lightLogoFile);
  }
  if (state.branding.faviconFile) {
    formData.set('favicon', state.branding.faviconFile);
  }

  return formData;
};

const CONTROL_QUERY_OPTIONS = {
  staleTime: 120_000,
  gcTime: 600_000,
  refetchOnWindowFocus: false,
  refetchOnReconnect: true,
  retry: 2,
  networkMode: 'offlineFirst' as const,
};

const resolveErrorMessage = (error: unknown, fallback: string): string => {
  if (error instanceof Error) {
    return error.message;
  }

  if (typeof error === 'string' && error.trim().length > 0) {
    return error;
  }

  return fallback;
};

const useOfflineStatus = (): boolean => {
  const [offline, setOffline] = useState<boolean>(() => {
    if (typeof navigator === 'undefined') {
      return false;
    }
    return !navigator.onLine;
  });

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const handleOnline = () => setOffline(false);
    const handleOffline = () => setOffline(true);

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, []);

  return offline;
};

const SkeletonLine: React.FC<{ className?: string }> = ({ className = '' }) => (
  <div className={`h-3 rounded-full bg-mono-gray-200/80 ${className}`} />
);

const SkeletonBlock: React.FC<{ className?: string }> = ({ className = '' }) => (
  <div className={`rounded-2xl bg-mono-gray-200/60 ${className}`} />
);

const ControlCenterSkeleton: React.FC = () => (
  <div className="grid gap-8 lg:grid-cols-[280px,1fr]" aria-hidden="true">
    <div className="space-y-6">
      <div className="animate-pulse space-y-4 rounded-2xl border border-mono-gray-200 p-6">
        <SkeletonLine className="w-28" />
        <SkeletonLine className="h-5 w-48" />
        <div className="space-y-3 pt-2">
          <SkeletonBlock className="h-12 w-full" />
          <SkeletonBlock className="h-12 w-full" />
          <SkeletonBlock className="h-12 w-full" />
        </div>
      </div>
      <div className="animate-pulse space-y-4 rounded-2xl border border-mono-gray-200 p-6">
        <SkeletonLine className="w-32" />
        <SkeletonLine className="h-5 w-36" />
        <div className="space-y-2 pt-3">
          <SkeletonBlock className="h-10 w-full" />
          <SkeletonBlock className="h-10 w-full" />
          <SkeletonBlock className="h-10 w-full" />
        </div>
      </div>
    </div>
    <div className="space-y-6">
      <div className="animate-pulse space-y-4 rounded-2xl border border-mono-gray-200 p-6">
        <SkeletonLine className="w-24" />
        <SkeletonLine className="h-7 w-64" />
        <SkeletonLine className="w-80" />
      </div>
      <div className="animate-pulse space-y-4 rounded-2xl border border-mono-gray-200 p-6">
        <SkeletonLine className="h-6 w-48" />
        <div className="grid gap-3 md:grid-cols-2">
          <SkeletonBlock className="h-32 w-full" />
          <SkeletonBlock className="h-32 w-full" />
        </div>
      </div>
    </div>
  </div>
);

const PreferenceToggle: React.FC<{
  label: string;
  description?: string;
  value: boolean;
  onChange: (next: boolean) => void;
  disabled?: boolean;
}> = ({ label, description, value, onChange, disabled = false }) => {
  const handleToggle = () => {
    if (disabled) {
      return;
    }
    onChange(!value);
  };

  return (
    <button
      type="button"
      role="switch"
      aria-checked={value}
      aria-label={label}
      aria-disabled={disabled}
      onClick={handleToggle}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          handleToggle();
        }
      }}
      className={`flex w-full items-start justify-between rounded-2xl border px-4 py-3 text-left transition-all focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 ${
        value
          ? 'border-mono-black bg-mono-black text-mono-white shadow-lg'
          : 'border-mono-gray-300 bg-mono-gray-50 text-mono-gray-800 hover:border-mono-gray-500'
      } ${disabled ? 'cursor-not-allowed opacity-60 hover:border-mono-gray-300' : ''}`}
    >
      <span>
        <span className="block text-sm font-semibold">{label}</span>
        {description && (
          <span className={`mt-1 block text-xs ${value ? 'text-mono-gray-200' : 'text-mono-gray-600'}`}>
            {description}
          </span>
        )}
      </span>
      <span
        className={`relative mt-1 inline-flex h-6 w-11 items-center rounded-full border transition-colors ${
          value ? 'border-transparent bg-mono-white' : 'border-mono-gray-400 bg-mono-gray-200'
        } ${disabled ? 'opacity-60' : ''}`}
      >
        <span
          className={`absolute left-1 inline-block h-4 w-4 rounded-full bg-mono-black transition-transform ${
            value ? 'translate-x-5' : 'translate-x-0'
          }`}
        />
      </span>
    </button>
  );
};

const normaliseLabel = (input: string): string => {
  return input
    .split(/[_\-.]/)
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
};

const isPlainObject = (value: unknown): value is Record<string, unknown> => {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
};

const renderValue = (value: unknown, depth = 0): ReactNode => {
  if (value === null || value === undefined || value === '') {
    return <span className="text-sm text-mono-gray-500">Not configured</span>;
  }

  if (typeof value === 'boolean') {
    return (
      <span
        className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] ${
          value ? 'bg-mono-black text-mono-white' : 'bg-mono-gray-200 text-mono-gray-600'
        }`}
      >
        {value ? 'Enabled' : 'Disabled'}
      </span>
    );
  }

  if (typeof value === 'number') {
    return <span className="text-sm font-semibold text-mono-black">{Number(value).toLocaleString()}</span>;
  }

  if (typeof value === 'string') {
    return <span className="text-sm text-mono-gray-700">{value}</span>;
  }

  if (Array.isArray(value)) {
    if (value.length === 0) {
      return <span className="text-sm text-mono-gray-500">Empty list</span>;
    }

    const primitiveItems = value.every((item) => ['string', 'number'].includes(typeof item));

    if (primitiveItems) {
      return (
        <div className="flex flex-wrap gap-2">
          {value.slice(0, 12).map((item, index) => (
            <span key={`${item}-${index}`} className="rounded-full border border-mono-gray-200 px-3 py-1 text-xs text-mono-gray-700">
              {String(item)}
            </span>
          ))}
          {value.length > 12 && (
            <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-400">+{value.length - 12} more</span>
          )}
        </div>
      );
    }

    return (
      <div className="space-y-2">
        {value.slice(0, 5).map((item, index) => (
          <div key={index} className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3 text-sm text-mono-gray-700">
            {renderValue(item, depth + 1)}
          </div>
        ))}
        {value.length > 5 && (
          <details className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3 text-xs text-mono-gray-600">
            <summary className="cursor-pointer text-mono-gray-500">View full list</summary>
            <pre className="mt-2 whitespace-pre-wrap">{JSON.stringify(value, null, 2)}</pre>
          </details>
        )}
      </div>
    );
  }

  if (isPlainObject(value)) {
    const entries = Object.entries(value);

    if (entries.length === 0) {
      return <span className="text-sm text-mono-gray-500">No values</span>;
    }

    const limit = depth === 0 ? 8 : 6;

    return (
      <div className="space-y-3">
        {entries.slice(0, limit).map(([childKey, childValue]) => (
          <div key={childKey} className="flex items-start justify-between gap-4">
            <div className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{normaliseLabel(childKey)}</div>
            <div className="max-w-xl text-sm text-mono-gray-700">{renderValue(childValue, depth + 1)}</div>
          </div>
        ))}
        {entries.length > limit && (
          <details className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3 text-xs text-mono-gray-600">
            <summary className="cursor-pointer text-mono-gray-500">View full JSON</summary>
            <pre className="mt-2 whitespace-pre-wrap">{JSON.stringify(value, null, 2)}</pre>
          </details>
        )}
      </div>
    );
  }

  return <pre className="whitespace-pre-wrap text-xs text-mono-gray-700">{JSON.stringify(value, null, 2)}</pre>;
};

const GeneralSettingsPage: React.FC = () => {
  const isOffline = useOfflineStatus();
  const generalQuery = useQuery<SettingsPayload>({
    queryKey: ['settings', 'general'],
    queryFn: async () => {
      const response = await generalSettingsApi.getGeneralSettings();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load general settings');
      }
      return response.data as SettingsPayload;
    },
    ...CONTROL_QUERY_OPTIONS,
  });

  const codQuery = useQuery<SettingsPayload>({
    queryKey: ['settings', 'cod-charges'],
    queryFn: async () => {
      const response = await generalSettingsApi.getCodCharges();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load COD charges');
      }
      return response.data as SettingsPayload;
    },
    ...CONTROL_QUERY_OPTIONS,
  });

  const deliveryQuery = useQuery<SettingsPayload>({
    queryKey: ['settings', 'delivery-charges'],
    queryFn: async () => {
      const response = await generalSettingsApi.getDeliveryCharges();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load delivery charges');
      }
      return response.data as SettingsPayload;
    },
    ...CONTROL_QUERY_OPTIONS,
  });

  const currenciesQuery = useQuery<CurrenciesPayload>({
    queryKey: ['settings', 'currencies'],
    queryFn: async () => {
      const response = await generalSettingsApi.getCurrencies();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load currencies');
      }
      return response.data as CurrenciesPayload;
    },
    ...CONTROL_QUERY_OPTIONS,
  });
  const { refetch: refetchGeneralSettings } = generalQuery;
  const { refetch: refetchCodCharges } = codQuery;
  const { refetch: refetchDeliveryCharges } = deliveryQuery;
  const { refetch: refetchCurrencies } = currenciesQuery;

  const generalData = generalQuery.data ?? {};

  const currencyOptions = useMemo(() => {
    const payload = currenciesQuery.data;
    if (!payload) {
      return [] as Array<{ code: string; name: string }>;
    }

    const raw = Array.isArray(payload)
      ? payload
      : Array.isArray(payload.currencies)
        ? payload.currencies
        : [];

    return raw
      .map((item) => {
        if (typeof item === 'string') {
          return { code: item, name: item };
        }

        if (item && typeof item === 'object') {
          const record = item as Record<string, unknown>;
          const code = String(record.code ?? record.currency ?? record.id ?? '');
          const name = String(record.name ?? record.title ?? record.label ?? code);
          return { code, name };
        }

        return null;
      })
      .filter((entry): entry is { code: string; name: string } => Boolean(entry));
  }, [currenciesQuery.data]);

  const rawSections: SettingsSection[] = useMemo(() => {
    const buckets = new Map<string, SettingEntry[]>(
      CATEGORY_DEFINITIONS.map((definition) => [definition.id, []])
    );

    Object.entries(generalData).forEach(([key, value]) => {
      const lowerKey = key.toLowerCase();
      const match = CATEGORY_DEFINITIONS.find((definition) =>
        definition.keywords.some((keyword) => lowerKey.includes(keyword))
      );
      const bucketId = match?.id ?? FALLBACK_CATEGORY;
      const bucket = buckets.get(bucketId);
      if (bucket) {
        bucket.push({ key, label: normaliseLabel(key), value });
      }
    });

    if (codQuery.isSuccess && codQuery.data) {
      buckets.get('finance')?.push({
        key: 'cod_charges',
        label: 'COD Charges',
        value: codQuery.data,
      });
    }

    if (deliveryQuery.isSuccess && deliveryQuery.data) {
      buckets.get('finance')?.push({
        key: 'delivery_charges',
        label: 'Delivery Charges',
        value: deliveryQuery.data,
      });
    }

    if (currencyOptions.length > 0) {
      buckets.get('finance')?.push({
        key: 'supported_currencies',
        label: 'Supported Currencies',
        value: currencyOptions,
      });
    } else if (currenciesQuery.isSuccess && currenciesQuery.data) {
      buckets.get('finance')?.push({
        key: 'supported_currencies',
        label: 'Supported Currencies',
        value: currenciesQuery.data,
      });
    }

    return CATEGORY_DEFINITIONS
      .map((definition) => {
        const entries = buckets.get(definition.id) ?? [];
        const sortedEntries = entries.sort((a, b) => a.label.localeCompare(b.label));
        return {
          id: definition.id,
          label: definition.label,
          entries: sortedEntries,
        };
      })
      .filter((section) => section.entries.length > 0);
  }, [generalData, codQuery.isSuccess, codQuery.data, deliveryQuery.isSuccess, deliveryQuery.data, currencyOptions, currenciesQuery.isSuccess, currenciesQuery.data]);

  const defaultPreferences = useMemo(() => getDefaultPreferences(), []);
  const mergedPreferences = useMemo(() => mergePreferenceSets(defaultPreferences, generalData?.preferences), [defaultPreferences, generalData]);
  const dataErrors = useMemo<DataError[]>(() => {
    const items: DataError[] = [];

    if (codQuery.isError) {
      items.push({
        id: 'cod-charges',
        title: 'COD charges',
        message: resolveErrorMessage(codQuery.error, 'COD charges failed to load.'),
        retry: () => {
          void codQuery.refetch();
        },
      });
    }

    if (deliveryQuery.isError) {
      items.push({
        id: 'delivery-charges',
        title: 'Delivery charges',
        message: resolveErrorMessage(deliveryQuery.error, 'Delivery charges failed to load.'),
        retry: () => {
          void deliveryQuery.refetch();
        },
      });
    }

    if (currenciesQuery.isError) {
      items.push({
        id: 'currencies',
        title: 'Currency catalogue',
        message: resolveErrorMessage(currenciesQuery.error, 'Currencies failed to load.'),
        retry: () => {
          void currenciesQuery.refetch();
        },
      });
    }

    return items;
  }, [
    codQuery.error,
    codQuery.isError,
    codQuery.refetch,
    deliveryQuery.error,
    deliveryQuery.isError,
    deliveryQuery.refetch,
    currenciesQuery.error,
    currenciesQuery.isError,
    currenciesQuery.refetch,
  ]);

  const isRefreshing = generalQuery.isFetching || codQuery.isFetching || deliveryQuery.isFetching || currenciesQuery.isFetching;

  const [activeSection, setActiveSection] = useState<string>('overview');
  const [statusMessage, setStatusMessage] = useState<StatusMessage | null>(null);
  const [formState, setFormState] = useState<FormState>(() => buildFormState({}, defaultPreferences));
  const [hasHydratedForm, setHasHydratedForm] = useState(false);
  const wasOfflineRef = useRef(isOffline);
  const [savedFingerprint, setSavedFingerprint] = useState<string>('');
  const [spotlightTerm, setSpotlightTerm] = useState('');
  const [rawSearchTerm, setRawSearchTerm] = useState('');
  const [draftMetadata, setDraftMetadata] = useState<DraftSnapshot | null>(null);
  const spotlightInputRef = useRef<HTMLInputElement | null>(null);

  const updateMutation = useMutation({
    mutationFn: async (payload: FormData) => {
      const response = await generalSettingsApi.updateGeneralSettings(payload);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to update general settings');
      }

      return response.data as SettingsPayload;
    },
    networkMode: 'offlineFirst',
    onSuccess: (data) => {
      const refreshedPreferences = mergePreferenceSets(defaultPreferences, data?.preferences);
      const nextState = buildFormState(data ?? {}, refreshedPreferences);
      setFormState(nextState);
      setSavedFingerprint(fingerprintFormState(nextState));
      clearDraftSnapshot();
      setDraftMetadata(null);
      setStatusMessage({ type: 'success', message: 'System preferences updated successfully.' });
      setHasHydratedForm(true);
      void generalQuery.refetch();
      void codQuery.refetch();
      void deliveryQuery.refetch();
      void currenciesQuery.refetch();
    },
    onError: (error: unknown) => {
      setStatusMessage({
        type: 'error',
        message: error instanceof Error ? error.message : 'Unable to update settings',
      });
    },
  });

  const isSaving = updateMutation.isPending;

  const updateGeneralField = <K extends keyof FormState['general']>(key: K, value: FormState['general'][K]) => {
    setFormState((prev) => ({
      ...prev,
      general: {
        ...prev.general,
        [key]: value,
      },
    }));
  };

  const updateBrandingField = <K extends keyof FormState['branding']>(key: K, value: FormState['branding'][K]) => {
    setFormState((prev) => ({
      ...prev,
      branding: {
        ...prev.branding,
        [key]: value,
      },
    }));
  };

  const updateOperationsToggle = (key: keyof FormState['operations'], value: boolean) => {
    setFormState((prev) => ({
      ...prev,
      operations: {
        ...prev.operations,
        [key]: value,
      },
    }));
  };

  const updateFinanceField = <K extends keyof FormState['finance']>(key: K, value: FormState['finance'][K]) => {
    setFormState((prev) => ({
      ...prev,
      finance: {
        ...prev.finance,
        [key]: value,
      },
    }));
  };

  const updateNotificationsToggle = (key: keyof FormState['notifications'], value: boolean) => {
    setFormState((prev) => ({
      ...prev,
      notifications: {
        ...prev.notifications,
        [key]: value,
      },
    }));
  };

  const updateIntegrationField = <K extends keyof FormState['integrations']>(key: K, value: FormState['integrations'][K]) => {
    setFormState((prev) => ({
      ...prev,
      integrations: {
        ...prev.integrations,
        [key]: value,
      },
    }));
  };

  const updateSystemField = <K extends keyof FormState['system']>(key: K, value: FormState['system'][K]) => {
    setFormState((prev) => ({
      ...prev,
      system: {
        ...prev.system,
        [key]: value,
      },
    }));
  };

  const updateWebsiteField = <K extends keyof FormState['website']>(key: K, value: FormState['website'][K]) => {
    setFormState((prev) => ({
      ...prev,
      website: {
        ...prev.website,
        [key]: value,
      },
    }));
  };

  const handleReset = () => {
    setFormState(baselineState);
    setHasHydratedForm(true);
    setStatusMessage({ type: 'success', message: 'Reverted to the last saved preferences.' });
    clearDraftSnapshot();
    setDraftMetadata(null);
  };

  const handleSave = () => {
    if (isOffline) {
      setStatusMessage({
        type: 'error',
        message: 'You are offline. Reconnect to save your changes.',
      });
      return;
    }

    const payload = buildFormDataFromState(formState);
    setHasHydratedForm(false);
    updateMutation.mutate(payload);
  };

  const handleSpotlightNavigate = (sectionId: string) => {
    setActiveSection(sectionId);
    setSpotlightTerm('');
    if (typeof window !== 'undefined') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const handleDiscardDraft = () => {
    clearDraftSnapshot();
    setDraftMetadata(null);
    if (hasChanges) {
      setFormState(baselineState);
    }
    setStatusMessage({
      type: 'success',
      message: 'Local draft cleared.',
    });
  };

  const [logoPreview, setLogoPreview] = useState<string | null>(null);
  const [lightLogoPreview, setLightLogoPreview] = useState<string | null>(null);
  const [faviconPreview, setFaviconPreview] = useState<string | null>(null);

  useEffect(() => {
    if (formState.branding.logoFile) {
      const objectUrl = URL.createObjectURL(formState.branding.logoFile);
      setLogoPreview(objectUrl);
      return () => URL.revokeObjectURL(objectUrl);
    }
    setLogoPreview(null);
    return undefined;
  }, [formState.branding.logoFile]);

  useEffect(() => {
    if (formState.branding.lightLogoFile) {
      const objectUrl = URL.createObjectURL(formState.branding.lightLogoFile);
      setLightLogoPreview(objectUrl);
      return () => URL.revokeObjectURL(objectUrl);
    }
    setLightLogoPreview(null);
    return undefined;
  }, [formState.branding.lightLogoFile]);

  useEffect(() => {
    if (formState.branding.faviconFile) {
      const objectUrl = URL.createObjectURL(formState.branding.faviconFile);
      setFaviconPreview(objectUrl);
      return () => URL.revokeObjectURL(objectUrl);
    }
    setFaviconPreview(null);
    return undefined;
  }, [formState.branding.faviconFile]);

  const summaryItems = useMemo(() => {
    const primaryCurrency = formState.general.currency || currencyOptions[0]?.code || '—';
    const brandName = formState.general.companyName || generalData.app_name || '—';
    const timezone = formState.general.timezone || mergedPreferences.general.timezone || '—';
    const supportEmail = formState.general.supportEmail || formState.general.companyEmail || '—';

    return [
      { label: 'Brand', value: brandName },
      { label: 'Timezone', value: timezone },
      { label: 'Primary Currency', value: primaryCurrency },
      { label: 'Support Email', value: supportEmail },
    ];
  }, [formState, currencyOptions, generalData.app_name, mergedPreferences.general.timezone]);

  const rawEntriesCount = useMemo(() => {
    return rawSections.reduce((total, section) => total + section.entries.length, 0);
  }, [rawSections]);

  const baselineState = useMemo(() => buildFormState(generalData, mergedPreferences), [generalData, mergedPreferences]);
  const baselineFingerprint = useMemo(() => fingerprintFormState(baselineState), [baselineState]);
  const currentFingerprint = useMemo(() => fingerprintFormState(formState), [formState]);
  const hasChanges = currentFingerprint !== savedFingerprint;

  const telemetryBadges = useMemo(
    () => [
      {
        label: 'Sync status',
        value: isRefreshing ? 'Syncing' : 'In sync',
        meta: generalQuery.dataUpdatedAt ? `Updated ${formatRelativeTime(generalQuery.dataUpdatedAt)}` : 'Awaiting first sync',
      },
      {
        label: 'Connectivity',
        value: isOffline ? 'Offline' : 'Online',
        meta: isOffline ? 'Changes stored locally' : 'Live link to Laravel',
      },
      {
        label: 'Draft state',
        value: hasChanges ? 'Draft in progress' : 'Published',
        meta: draftMetadata?.updatedAt ? `Local draft ${formatRelativeTime(draftMetadata.updatedAt)}` : 'No offline draft',
      },
    ],
    [generalQuery.dataUpdatedAt, isRefreshing, isOffline, hasChanges, draftMetadata],
  );

  const lastSyncedExact = useMemo(
    () => (generalQuery.dataUpdatedAt ? formatExactTimestamp(generalQuery.dataUpdatedAt) : 'Awaiting sync'),
    [generalQuery.dataUpdatedAt],
  );

  const moduleTiles = useMemo<ModuleTile[]>(() => {
    const notificationsEnabled = [
      formState.notifications.email,
      formState.notifications.sms,
      formState.notifications.push,
      formState.notifications.dailyDigest,
      formState.notifications.escalateIncidents,
    ].filter(Boolean).length;

    const integrationsEnabled = [
      formState.integrations.webhooksEnabled,
      formState.integrations.slackEnabled,
      formState.integrations.powerBiEnabled,
      formState.integrations.zapierEnabled,
    ].filter(Boolean).length;

    const websiteDomain = String(
      generalData?.site_url ?? generalData?.website ?? generalData?.domain ?? generalData?.app_url ?? '',
    );

    const accent = formState.branding.primaryColor || '#111827';

    return [
      {
        id: 'overview',
        label: 'Control Center',
        description: 'Snapshot & uptime',
        icon: 'fas fa-compass',
        sectionId: 'overview',
        accent,
        status: formState.general.companyName || 'Brand TBD',
        hint: summaryItems[2]?.value ? `Currency ${summaryItems[2]?.value}` : 'Currency unset',
      },
      {
        id: 'website',
        label: 'Website',
        description: 'Landing & marketing',
        icon: 'fas fa-globe',
        sectionId: 'website',
        accent,
        status: websiteDomain || 'Domain not wired',
        hint: formState.website.heroTitle || 'Add hero headline',
      },
      {
        id: 'brand',
        label: 'Brand',
        description: 'Logos & theming',
        icon: 'fas fa-swatchbook',
        sectionId: 'brand',
        accent,
        status: formState.branding.theme === 'system' ? 'Auto' : formState.branding.theme,
        hint: formState.branding.tagline ? 'Tagline ready' : 'Add tagline',
      },
      {
        id: 'operations',
        label: 'Operations',
        description: 'Automation rules',
        icon: 'fas fa-route',
        sectionId: 'operations',
        accent,
        status: formState.operations.autoAssignDrivers && formState.operations.autoGenerateTrackingIds ? 'Autopilot' : 'Hybrid',
        hint: formState.operations.enforcePodOtp ? 'OTP proof' : 'Signature only',
      },
      {
        id: 'finance',
        label: 'Finance',
        description: 'Billing & COD',
        icon: 'fas fa-file-invoice-dollar',
        sectionId: 'finance',
        accent,
        status: `${formState.finance.defaultTaxRate}% tax`,
        hint: `Rounding ${formState.finance.roundingMode}`,
      },
      {
        id: 'notifications',
        label: 'Notifications',
        description: 'Email/SMS/push',
        icon: 'fas fa-bell',
        sectionId: 'notifications',
        accent,
        status: `${notificationsEnabled}/5 channels`,
        hint: formState.notifications.escalateIncidents ? 'Escalations live' : 'Escalations off',
      },
      {
        id: 'integrations',
        label: 'Integrations',
        description: 'Webhooks & apps',
        icon: 'fas fa-plug',
        sectionId: 'integrations',
        accent,
        status: `${integrationsEnabled} connected`,
        hint: formState.integrations.webhooksUrl ? 'Webhook URL set' : 'Webhook URL pending',
      },
      {
        id: 'system',
        label: 'System',
        description: 'Security & retention',
        icon: 'fas fa-shield-halved',
        sectionId: 'system',
        accent,
        status: formState.system.twoFactorRequired ? '2FA required' : '2FA optional',
        hint: `${formState.system.dataRetentionDays} day retention`,
      },
    ];
  }, [formState, generalData, summaryItems]);

  useEffect(() => {
    if (!generalQuery.isSuccess || hasHydratedForm) {
      return;
    }

    let nextState = baselineState;
    const draft = readDraftSnapshot();
    if (draft?.state) {
      nextState = deepMergeFormStates(baselineState, draft.state);
      setDraftMetadata(draft);
      setStatusMessage({
        type: 'success',
        message: 'Restored your last offline draft.',
      });
    }

    setFormState(nextState);
    setHasHydratedForm(true);
    setSavedFingerprint(baselineFingerprint);
  }, [generalQuery.isSuccess, hasHydratedForm, baselineState, baselineFingerprint]);

  useEffect(() => {
    if (!statusMessage || typeof window === 'undefined') {
      return;
    }

    const timeout = window.setTimeout(() => setStatusMessage(null), 5000);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [statusMessage]);

  useEffect(() => {
    if (!hasHydratedForm) {
      return;
    }

    if (typeof window === 'undefined') {
      return;
    }

    if (!hasChanges) {
      clearDraftSnapshot();
      setDraftMetadata(null);
      return;
    }

    const timeout = window.setTimeout(() => {
      const snapshot = persistDraftSnapshot(formState);
      if (snapshot) {
        setDraftMetadata(snapshot);
      }
    }, 400);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [formState, hasChanges, hasHydratedForm]);

  useEffect(() => {
    if (typeof window === 'undefined') {
      return;
    }

    const handler = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        spotlightInputRef.current?.focus();
      }
    };

    window.addEventListener('keydown', handler);

    return () => {
      window.removeEventListener('keydown', handler);
    };
  }, []);

  const spotlightMatches = useMemo<SpotlightMatch[]>(() => {
    const term = spotlightTerm.trim().toLowerCase();
    if (term.length < 2) {
      return [];
    }

    const matches: SpotlightMatch[] = [];
    rawSections.forEach((section) => {
      section.entries.forEach((entry) => {
        const preview = toPreviewString(entry.value) || '—';
        const haystack = `${entry.key} ${entry.label} ${preview}`.toLowerCase();
        if (haystack.includes(term)) {
          matches.push({
            id: entry.key,
            label: entry.label,
            sectionId: section.id,
            sectionLabel: section.label,
            preview,
          });
        }
      });
    });

    return matches.slice(0, 5);
  }, [spotlightTerm, rawSections]);

  const filteredRawSections = useMemo(() => {
    const term = rawSearchTerm.trim().toLowerCase();
    if (!term) {
      return rawSections;
    }

    return rawSections
      .map((section) => ({
        ...section,
        entries: section.entries.filter((entry) => {
          const preview = toPreviewString(entry.value) || '';
          const haystack = `${entry.key} ${entry.label} ${preview}`.toLowerCase();
          return haystack.includes(term);
        }),
      }))
      .filter((section) => section.entries.length > 0);
  }, [rawSections, rawSearchTerm]);

  const renderActiveSection = (): ReactNode => {
    switch (activeSection) {
      case 'overview': {
        const websiteDomain = String(
          generalData?.site_url ?? generalData?.website ?? generalData?.domain ?? generalData?.app_url ?? 'Not linked',
        );
        return (
          <div className="space-y-6">
            <div className="grid gap-6 xl:grid-cols-2">
              <Card className="border border-mono-gray-200 p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Brand identity</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Customer-facing essentials</h3>
                <dl className="mt-4 space-y-3 text-sm text-mono-gray-600">
                  <div className="flex items-center justify-between">
                    <dt>Primary brand</dt>
                    <dd className="text-mono-black">{formState.general.companyName || 'Not set'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Tagline</dt>
                    <dd className="text-mono-black">{formState.branding.tagline || 'Not set'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Primary colour</dt>
                    <dd className="inline-flex items-center gap-2 font-medium text-mono-black">
                      <span className="h-4 w-4 rounded-full border border-mono-gray-200" style={{ backgroundColor: formState.branding.primaryColor }} />
                      {formState.branding.primaryColor.toUpperCase()}
                    </dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Theme</dt>
                    <dd className="text-mono-black">{formState.branding.theme.toUpperCase()}</dd>
                  </div>
                </dl>
              </Card>

              <Card className="border border-mono-gray-200 p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operational health</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Automation & tracking</h3>
                <div className="mt-4 grid gap-4 md:grid-cols-2">
                  {[
                    { label: 'Auto assign', value: formState.operations.autoAssignDrivers ? 'Enabled' : 'Manual', hint: 'Driver routing' },
                    { label: 'Capacity', value: formState.operations.enableCapacityManagement ? 'Managed' : 'Manual', hint: 'Hub throughput' },
                    { label: 'Tracking IDs', value: formState.operations.autoGenerateTrackingIds ? 'Automated' : 'Manual', hint: 'BR codes' },
                    { label: 'Proof of delivery', value: formState.operations.enforcePodOtp ? 'OTP required' : 'Signature only', hint: 'Field sign-off' },
                  ].map((tile) => (
                    <div key={tile.label} className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                      <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{tile.label}</p>
                      <p className="mt-2 text-sm font-semibold text-mono-black">{tile.value}</p>
                      <p className="text-xs text-mono-gray-500">{tile.hint}</p>
                    </div>
                  ))}
                </div>
              </Card>

              <Card className="border border-mono-gray-200 p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Finance snapshot</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Billing guardrails</h3>
                <dl className="mt-4 space-y-3 text-sm text-mono-gray-600">
                  <div className="flex items-center justify-between">
                    <dt>COD workflow</dt>
                    <dd className="text-mono-black">{formState.finance.enforceCodWorkflow ? 'Automated' : 'Manual'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Default tax rate</dt>
                    <dd className="text-mono-black">{formState.finance.defaultTaxRate}%</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Invoice prefix</dt>
                    <dd className="text-mono-black">{formState.finance.invoicePrefix || 'INV'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Rounding</dt>
                    <dd className="text-mono-black">{formState.finance.roundingMode.toUpperCase()}</dd>
                  </div>
                </dl>
              </Card>

              <Card className="border border-mono-gray-200 p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">System guardrails</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Security & retention</h3>
                <dl className="mt-4 space-y-3 text-sm text-mono-gray-600">
                  <div className="flex items-center justify-between">
                    <dt>Maintenance</dt>
                    <dd className="text-mono-black">{formState.system.maintenanceMode ? 'Enabled' : 'Disabled'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Two factor</dt>
                    <dd className="text-mono-black">{formState.system.twoFactorRequired ? 'Mandatory' : 'Optional'}</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Auto logout</dt>
                    <dd className="text-mono-black">{formState.system.autoLogoutMinutes} min</dd>
                  </div>
                  <div className="flex items-center justify-between">
                    <dt>Data retention</dt>
                    <dd className="text-mono-black">{formState.system.dataRetentionDays} days</dd>
                  </div>
                </dl>
              </Card>
            </div>

            <Card className="border border-mono-gray-200 p-6">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Website landing</p>
                  <h3 className="text-xl font-semibold text-mono-black">Public touchpoints</h3>
                </div>
                <Button variant="ghost" size="sm" onClick={() => handleSpotlightNavigate('website')}>
                  Edit landing page
                </Button>
              </div>
              <div className="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                  <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Domain</p>
                  <p className="mt-1 text-sm font-semibold text-mono-black">{websiteDomain}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Hero CTA</p>
                  <p className="mt-1 text-sm font-semibold text-mono-black">{formState.website.ctaLabel || 'Book a pickup'}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Support email</p>
                  <p className="mt-1 text-sm font-semibold text-mono-black">{formState.general.supportEmail || 'Not set'}</p>
                </div>
                <div>
                  <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">Tagline</p>
                  <p className="mt-1 text-sm font-semibold text-mono-black">{formState.branding.tagline || 'Add tagline'}</p>
                </div>
              </div>
              <div className="mt-6 rounded-3xl border border-mono-gray-100 bg-mono-gray-50 p-6">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Preview</p>
                <h4 className="mt-3 text-2xl font-semibold text-mono-black">
                  {formState.website.heroTitle || 'Deliver with confidence'}
                </h4>
                <p className="mt-2 text-sm text-mono-gray-600">
                  {formState.website.heroSubtitle || 'Baraka routes, tracks, and reconciles every parcel in real time.'}
                </p>
              </div>
            </Card>
          </div>
        );
      }
      case 'brand': {
        return (
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Brand Identity</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Customer-facing configuration</h3>
              </div>
            </div>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
              <Input
                label="Company name"
                value={formState.general.companyName}
                onChange={(event) => updateGeneralField('companyName', event.target.value)}
              />
              <Input
                label="Company email"
                type="email"
                value={formState.general.companyEmail}
                onChange={(event) => updateGeneralField('companyEmail', event.target.value)}
              />
              <Input
                label="Support email"
                type="email"
                value={formState.general.supportEmail}
                onChange={(event) => updateGeneralField('supportEmail', event.target.value)}
              />
              <Input
                label="Customer hotline"
                value={formState.general.phone}
                onChange={(event) => updateGeneralField('phone', event.target.value)}
              />
              <div className="lg:col-span-2">
                <label className="block text-sm font-medium text-mono-gray-900">Office address</label>
                <textarea
                  className="mt-1 w-full rounded-md border border-mono-gray-300 px-3 py-2 text-sm text-mono-gray-900 focus:outline-none focus:ring-2 focus:ring-mono-black"
                  rows={3}
                  value={formState.general.address}
                  onChange={(event) => updateGeneralField('address', event.target.value)}
                />
              </div>
              <Select
                label="Primary currency"
                value={formState.general.currency}
                onChange={(event) => updateGeneralField('currency', event.target.value)}
                options={[{ value: '', label: 'Select currency' }, ...currencyOptions.map((option) => ({ value: option.code, label: `${option.code} — ${option.name}` }))]}
              />
              <Select
                label="Timezone"
                value={formState.general.timezone}
                onChange={(event) => updateGeneralField('timezone', event.target.value)}
                options={TIMEZONE_OPTIONS.map((option) => ({ value: option, label: option }))}
              />
              <Input
                label="Country"
                value={formState.general.country}
                onChange={(event) => updateGeneralField('country', event.target.value)}
              />
              <Input
                label="Copyright notice"
                value={formState.general.copyright}
                onChange={(event) => updateGeneralField('copyright', event.target.value)}
              />
              <Input
                label="Brand tagline"
                value={formState.branding.tagline}
                onChange={(event) => updateBrandingField('tagline', event.target.value)}
              />
              <Select
                label="Theme"
                value={formState.branding.theme}
                onChange={(event) => updateBrandingField('theme', event.target.value as FormState['branding']['theme'])}
                options={THEME_OPTIONS.map((option) => ({ value: option.value, label: option.label }))}
              />
              <Select
                label="Sidebar density"
                value={formState.branding.sidebarDensity}
                onChange={(event) => updateBrandingField('sidebarDensity', event.target.value as FormState['branding']['sidebarDensity'])}
                options={DENSITY_OPTIONS.map((option) => ({ value: option.value, label: option.label }))}
              />
              <PreferenceToggle
                label="Micro-animations"
                description="Keep the dashboard delightful with subtle transitions."
                value={formState.branding.enableAnimations}
                onChange={(value) => updateBrandingField('enableAnimations', value)}
              />
              <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Primary Colour</p>
                <div className="mt-3 flex items-center gap-3">
                  <input
                    type="color"
                    value={formState.branding.primaryColor}
                    onChange={(event) => updateBrandingField('primaryColor', event.target.value)}
                    className="h-10 w-16 cursor-pointer rounded-md border border-mono-gray-300 bg-mono-white"
                    aria-label="Primary brand colour"
                  />
                  <input
                    type="text"
                    value={formState.branding.primaryColor}
                    onChange={(event) => updateBrandingField('primaryColor', event.target.value)}
                    className="flex-1 rounded-md border border-mono-gray-300 px-3 py-2 text-sm text-mono-gray-900 focus:outline-none focus:ring-2 focus:ring-mono-black"
                  />
                </div>
              </div>
              <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Text Colour</p>
                <div className="mt-3 flex items-center gap-3">
                  <input
                    type="color"
                    value={formState.branding.textColor}
                    onChange={(event) => updateBrandingField('textColor', event.target.value)}
                    className="h-10 w-16 cursor-pointer rounded-md border border-mono-gray-300 bg-mono-white"
                    aria-label="Text colour"
                  />
                  <input
                    type="text"
                    value={formState.branding.textColor}
                    onChange={(event) => updateBrandingField('textColor', event.target.value)}
                    className="flex-1 rounded-md border border-mono-gray-300 px-3 py-2 text-sm text-mono-gray-900 focus:outline-none focus:ring-2 focus:ring-mono-black"
                  />
                </div>
              </div>
            </div>

            <div className="mt-8 grid gap-6 md:grid-cols-3">
              <div className="space-y-3">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Dark Logo</p>
                <div className="rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50 p-4 text-center">
                  <div className="flex justify-center">
                    {logoPreview || formState.branding.logoUrl ? (
                      <img
                        src={logoPreview ?? formState.branding.logoUrl ?? ''}
                        alt="Primary logo preview"
                        className="h-16 object-contain"
                      />
                    ) : (
                      <span className="text-xs text-mono-gray-500">Upload .png or .svg</span>
                    )}
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    className="mt-3 w-full text-sm"
                    onChange={(event) => updateBrandingField('logoFile', event.target.files?.[0] ?? null)}
                  />
                </div>
              </div>
              <div className="space-y-3">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Light Logo</p>
                <div className="rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50 p-4 text-center">
                  <div className="flex justify-center">
                    {lightLogoPreview || formState.branding.lightLogoUrl ? (
                      <img
                        src={lightLogoPreview ?? formState.branding.lightLogoUrl ?? ''}
                        alt="Light logo preview"
                        className="h-16 object-contain"
                      />
                    ) : (
                      <span className="text-xs text-mono-gray-500">Upload transparent logo</span>
                    )}
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    className="mt-3 w-full text-sm"
                    onChange={(event) => updateBrandingField('lightLogoFile', event.target.files?.[0] ?? null)}
                  />
                </div>
              </div>
              <div className="space-y-3">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Favicon</p>
                <div className="rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50 p-4 text-center">
                  <div className="flex justify-center">
                    {faviconPreview || formState.branding.faviconUrl ? (
                      <img
                        src={faviconPreview ?? formState.branding.faviconUrl ?? ''}
                        alt="Favicon preview"
                        className="h-12 w-12 rounded-lg object-contain"
                      />
                    ) : (
                      <span className="text-xs text-mono-gray-500">Upload square icon</span>
                    )}
                  </div>
                  <input
                    type="file"
                    accept="image/*"
                    className="mt-3 w-full text-sm"
                    onChange={(event) => updateBrandingField('faviconFile', event.target.files?.[0] ?? null)}
                  />
                </div>
              </div>
            </div>
          </Card>
        );
      }
      case 'website': {
        return (
          <div className="grid gap-6 lg:grid-cols-[1.5fr,1fr]">
            <Card className="border border-mono-gray-200 p-6">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Website landing</p>
                  <h3 className="mt-1 text-xl font-semibold text-mono-black">Hero & messaging</h3>
                </div>
                <Button variant="secondary" size="sm" onClick={() => handleSpotlightNavigate('brand')}>
                  Manage assets
                </Button>
              </div>
              <div className="mt-6 grid gap-4">
                <Input
                  label="Hero title"
                  value={formState.website.heroTitle}
                  onChange={(event) => updateWebsiteField('heroTitle', event.target.value)}
                />
                <Input
                  label="Hero subtitle"
                  value={formState.website.heroSubtitle}
                  onChange={(event) => updateWebsiteField('heroSubtitle', event.target.value)}
                />
                <Input
                  label="Primary call-to-action label"
                  value={formState.website.ctaLabel}
                  onChange={(event) => updateWebsiteField('ctaLabel', event.target.value)}
                />
                <Input
                  label="Footer note"
                  value={formState.website.footerNote}
                  onChange={(event) => updateWebsiteField('footerNote', event.target.value)}
                />
              </div>
            </Card>
            <Card className="border border-mono-gray-200 p-0">
              <div
                className="rounded-3xl p-6 text-mono-white"
                style={{
                  background:
                    formState.branding.theme === 'dark'
                      ? 'linear-gradient(135deg, #0f172a, #1e293b)'
                      : `linear-gradient(135deg, ${formState.branding.primaryColor}, #111827)`,
                }}
              >
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-300">Live preview</p>
                <h3 className="mt-4 text-2xl font-semibold">{formState.website.heroTitle || 'Deliver with confidence'}</h3>
                <p className="mt-3 text-sm text-mono-gray-100">
                  {formState.website.heroSubtitle || 'Baraka routes, tracks, and reconciles every parcel in real time.'}
                </p>
                <Button
                  variant="primary"
                  size="sm"
                  className="mt-6 bg-mono-white text-mono-black hover:bg-mono-gray-100"
                  onClick={() => handleSpotlightNavigate('brand')}
                >
                  {formState.website.ctaLabel || 'Book a pickup'}
                </Button>
              </div>
              <div className="border-t border-mono-gray-100 p-4">
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Footer</p>
                <p className="mt-2 text-sm text-mono-gray-700">{formState.website.footerNote}</p>
              </div>
            </Card>
          </div>
        );
      }
      case 'operations': {
        return (
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Operations</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Automation & workflow controls</h3>
              </div>
            </div>
            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <PreferenceToggle
                label="Auto-assign drivers"
                description="Automatically distribute new parcels across the best available riders."
                value={formState.operations.autoAssignDrivers}
                onChange={(value) => updateOperationsToggle('autoAssignDrivers', value)}
              />
              <PreferenceToggle
                label="Capacity management"
                description="Use hub throughput and load forecasting to prevent over-booking."
                value={formState.operations.enableCapacityManagement}
                onChange={(value) => updateOperationsToggle('enableCapacityManagement', value)}
              />
              <PreferenceToggle
                label="Dispatch approvals"
                description="Require supervisor sign-off before parcels are released to the field."
                value={formState.operations.requireDispatchApproval}
                onChange={(value) => updateOperationsToggle('requireDispatchApproval', value)}
              />
              <PreferenceToggle
                label="Auto tracking IDs"
                description="Generate BR-prefixed tracking IDs instantly when bookings are created."
                value={formState.operations.autoGenerateTrackingIds}
                onChange={(value) => updateOperationsToggle('autoGenerateTrackingIds', value)}
              />
              <PreferenceToggle
                label="Proof-of-delivery OTP"
                description="Require a 4-digit confirmation code before completing deliveries."
                value={formState.operations.enforcePodOtp}
                onChange={(value) => updateOperationsToggle('enforcePodOtp', value)}
              />
              <PreferenceToggle
                label="Public parcel tracking"
                description="Allow customers to track shipments from the public tracking portal."
                value={formState.operations.allowPublicTracking}
                onChange={(value) => updateOperationsToggle('allowPublicTracking', value)}
              />
            </div>
          </Card>
        );
      }
      case 'finance': {
        return (
          <div className="space-y-6">
            <Card className="border border-mono-gray-200 p-6">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Finance & Billing</p>
                  <h3 className="mt-1 text-xl font-semibold text-mono-black">Settlement & billing automation</h3>
                </div>
              </div>
              <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <PreferenceToggle
                  label="COD settlement workflow"
                  description="Auto-stage merchant COD payouts as soon as parcels are delivered."
                  value={formState.finance.enforceCodWorkflow}
                  onChange={(value) => updateFinanceField('enforceCodWorkflow', value)}
                />
                <PreferenceToggle
                  label="Invoice emails"
                  description="Send automatic invoice copies to finance stakeholders."
                  value={formState.finance.enableInvoiceEmails}
                  onChange={(value) => updateFinanceField('enableInvoiceEmails', value)}
                />
                <PreferenceToggle
                  label="Auto-reconciliation"
                  description="Match incoming payments against open settlements automatically."
                  value={formState.finance.autoReconcile}
                  onChange={(value) => updateFinanceField('autoReconcile', value)}
                />
              </div>

              <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <Input
                  label="Invoice prefix"
                  value={formState.finance.invoicePrefix}
                  onChange={(event) => updateFinanceField('invoicePrefix', event.target.value.toUpperCase())}
                />
                <Input
                  label="Tracking prefix"
                  value={formState.finance.trackingPrefix}
                  onChange={(event) => updateFinanceField('trackingPrefix', event.target.value.toUpperCase())}
                />
                <Input
                  label="Default tax rate (%)"
                  type="number"
                  min={0}
                  max={100}
                  step={0.5}
                  value={formState.finance.defaultTaxRate}
                  onChange={(event) => {
                    const value = Number(event.target.value);
                    updateFinanceField('defaultTaxRate', Number.isFinite(value) ? value : 0);
                  }}
                />
                <Select
                  label="Rounding mode"
                  value={formState.finance.roundingMode}
                  onChange={(event) => updateFinanceField('roundingMode', event.target.value as RoundingMode)}
                  options={ROUNDING_OPTIONS.map((option) => ({ value: option.value, label: option.label }))}
                />
              </div>
            </Card>

            <Card className="border border-mono-gray-200 p-6">
              <h3 className="text-lg font-semibold text-mono-black">Currency Catalogue</h3>
              <div className="mt-4">
                {currenciesQuery.isLoading && (
                  <div className="space-y-2" aria-hidden="true">
                    <SkeletonLine className="h-4 w-36" />
                    <SkeletonLine className="h-4 w-48" />
                    <SkeletonLine className="h-4 w-40" />
                    <SkeletonLine className="h-4 w-44" />
                  </div>
                )}
                {!currenciesQuery.isLoading && currenciesQuery.isError && (
                  <p className="text-sm text-amber-700">
                    {resolveErrorMessage(currenciesQuery.error, 'Unable to load currencies right now.')}
                  </p>
                )}
                {!currenciesQuery.isLoading && !currenciesQuery.isError && (
                  <div className="flex flex-wrap gap-2">
                    {currencyOptions.length > 0 ? (
                      currencyOptions.map((currency) => (
                        <span key={currency.code} className="rounded-full border border-mono-gray-200 bg-mono-gray-50 px-3 py-1 text-xs font-medium text-mono-gray-700">
                          {currency.code} • {currency.name}
                        </span>
                      ))
                    ) : currenciesQuery.data ? (
                      <pre className="max-h-60 w-full overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
                        {JSON.stringify(currenciesQuery.data, null, 2)}
                      </pre>
                    ) : (
                      <p className="text-sm text-mono-gray-600">No currencies available.</p>
                    )}
                  </div>
                )}
              </div>
            </Card>
          </div>
        );
      }
      case 'notifications': {
        return (
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Notifications</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Customer & internal messaging</h3>
              </div>
            </div>
            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <PreferenceToggle
                label="Email updates"
                description="Send branded email notifications for parcel movements."
                value={formState.notifications.email}
                onChange={(value) => updateNotificationsToggle('email', value)}
              />
              <PreferenceToggle
                label="SMS alerts"
                description="Deliver SMS alerts for critical parcel statuses."
                value={formState.notifications.sms}
                onChange={(value) => updateNotificationsToggle('sms', value)}
              />
              <PreferenceToggle
                label="Push notifications"
                description="Use web push for real-time dashboard alerts."
                value={formState.notifications.push}
                onChange={(value) => updateNotificationsToggle('push', value)}
              />
              <PreferenceToggle
                label="Daily digest"
                description="Send each stakeholder a morning performance recap."
                value={formState.notifications.dailyDigest}
                onChange={(value) => updateNotificationsToggle('dailyDigest', value)}
              />
              <PreferenceToggle
                label="Escalate incidents"
                description="Escalate unresolved incidents to operations leadership."
                value={formState.notifications.escalateIncidents}
                onChange={(value) => updateNotificationsToggle('escalateIncidents', value)}
              />
            </div>
          </Card>
        );
      }
      case 'integrations': {
        return (
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Integrations</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Connect Baraka to your stack</h3>
              </div>
            </div>
            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <PreferenceToggle
                label="Webhook delivery"
                description="Push lifecycle events to your webhook endpoint."
                value={formState.integrations.webhooksEnabled}
                onChange={(value) => updateIntegrationField('webhooksEnabled', value)}
              />
              <PreferenceToggle
                label="Slack alerts"
                description="Pipe escalations directly into a shared Slack channel."
                value={formState.integrations.slackEnabled}
                onChange={(value) => updateIntegrationField('slackEnabled', value)}
              />
              <PreferenceToggle
                label="Power BI sync"
                description="Mirror shipment metrics into Power BI dashboards."
                value={formState.integrations.powerBiEnabled}
                onChange={(value) => updateIntegrationField('powerBiEnabled', value)}
              />
              <PreferenceToggle
                label="Zapier automation"
                description="Trigger Zapier workflows when parcels change status."
                value={formState.integrations.zapierEnabled}
                onChange={(value) => updateIntegrationField('zapierEnabled', value)}
              />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <Input
                label="Webhook URL"
                type="url"
                value={formState.integrations.webhooksUrl}
                onChange={(event) => updateIntegrationField('webhooksUrl', event.target.value)}
              />
              <Input
                label="Slack channel"
                value={formState.integrations.slackChannel}
                onChange={(event) => updateIntegrationField('slackChannel', event.target.value)}
              />
              <Input
                label="Analytics tracking ID"
                value={formState.integrations.analyticsTrackingId}
                onChange={(event) => updateIntegrationField('analyticsTrackingId', event.target.value)}
              />
            </div>
          </Card>
        );
      }
      case 'system': {
        return (
          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-start justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">System Controls</p>
                <h3 className="mt-1 text-xl font-semibold text-mono-black">Hardening & retention</h3>
              </div>
            </div>
            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <PreferenceToggle
                label="Maintenance mode"
                description="Serve the maintenance banner and pause new bookings."
                value={formState.system.maintenanceMode}
                onChange={(value) => updateSystemField('maintenanceMode', value)}
              />
              <PreferenceToggle
                label="Enforce two-factor auth"
                description="Require all staff to enrol in two-factor authentication."
                value={formState.system.twoFactorRequired}
                onChange={(value) => updateSystemField('twoFactorRequired', value)}
              />
              <PreferenceToggle
                label="Self-service portals"
                description="Allow merchants to manage bookings and labels via the portal."
                value={formState.system.allowSelfService}
                onChange={(value) => updateSystemField('allowSelfService', value)}
              />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
              <Input
                label="Auto logout (minutes)"
                type="number"
                min={5}
                max={1440}
                value={formState.system.autoLogoutMinutes}
                onChange={(event) => {
                  const value = Number(event.target.value);
                  updateSystemField('autoLogoutMinutes', Number.isFinite(value) ? value : 5);
                }}
              />
              <Input
                label="Data retention (days)"
                type="number"
                min={30}
                max={1825}
                value={formState.system.dataRetentionDays}
                onChange={(event) => {
                  const value = Number(event.target.value);
                  updateSystemField('dataRetentionDays', Number.isFinite(value) ? value : 30);
                }}
              />
            </div>
          </Card>
        );
      }
      case 'raw': {
        const searchActive = rawSearchTerm.trim().length > 0;
        const sectionsToRender = filteredRawSections;
        const visibleEntries = sectionsToRender.reduce((total, section) => total + section.entries.length, 0);
        return (
          <div className="space-y-6">
            <Card className="border border-mono-gray-200 p-6">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                  <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">Data Explorer</p>
                  <h3 className="mt-1 text-xl font-semibold text-mono-black">Laravel source of truth</h3>
                  <p className="mt-2 text-sm text-mono-gray-600">
                    Inspect every key stored within the backend without leaving this surface. Perfect for validation and audits.
                  </p>
                </div>
                <span className="rounded-full border border-mono-gray-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                  {rawEntriesCount} keys
                </span>
              </div>
              <div className="mt-6 space-y-2">
                <Input
                  label="Search configuration"
                  placeholder="Type to filter (e.g. currency, webhook, color)"
                  value={rawSearchTerm}
                  onChange={(event) => setRawSearchTerm(event.target.value)}
                />
                <p className="text-xs text-mono-gray-500">
                  {searchActive
                    ? visibleEntries === 0
                      ? 'No matches. Try a different keyword.'
                      : `Showing ${visibleEntries} entr${visibleEntries === 1 ? 'y' : 'ies'} matching “${rawSearchTerm.trim()}”.`
                    : 'Pro tip: use Cmd/Ctrl + F for browser search or Cmd/Ctrl + K to jump via Spotlight.'}
                </p>
              </div>
            </Card>

            {sectionsToRender.length > 0 ? (
              sectionsToRender.map((section) => (
                <Card key={section.id} className="border border-mono-gray-200 p-6">
                  <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                      <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{section.id}</p>
                      <h3 className="mt-1 text-xl font-semibold text-mono-black">{section.label}</h3>
                    </div>
                  </div>
                  <div className="mt-4 space-y-4">
                    {section.entries.map((entry) => (
                      <div key={entry.key} className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                          <span className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{entry.key}</span>
                          <span className="text-sm font-semibold text-mono-black">{entry.label}</span>
                        </div>
                        <div className="mt-3 text-sm text-mono-gray-700">
                          {renderValue(entry.value)}
                        </div>
                      </div>
                    ))}
                  </div>
                </Card>
              ))
            ) : (
              <Card className="border border-mono-gray-200 p-8 text-center text-sm text-mono-gray-600">
                {searchActive ? 'No configuration matches this query.' : 'No raw settings available.'}
              </Card>
            )}
          </div>
        );
      }
      default:
        return null;
    }
  };

  const handleRefreshAll = useCallback(
    async ({ silent = false }: { silent?: boolean } = {}) => {
      if (isOffline) {
        if (!silent) {
          setStatusMessage({
            type: 'error',
            message: 'You are offline. Reconnect to refresh settings.',
          });
        }
        return;
      }

      setHasHydratedForm(false);
      await Promise.all([
        refetchGeneralSettings(),
        refetchCodCharges(),
        refetchDeliveryCharges(),
        refetchCurrencies(),
      ]);

      if (!silent) {
        setStatusMessage({
          type: 'success',
          message: 'Latest preferences synced from the backend.',
        });
      }
    },
    [
      isOffline,
      refetchCodCharges,
      refetchCurrencies,
      refetchDeliveryCharges,
      refetchGeneralSettings,
      setHasHydratedForm,
      setStatusMessage,
    ],
  );

  useEffect(() => {
    if (isOffline) {
      wasOfflineRef.current = true;
      return;
    }

    if (wasOfflineRef.current) {
      wasOfflineRef.current = false;
      void handleRefreshAll({ silent: true });
    }
  }, [handleRefreshAll, isOffline]);

  const isInitialLoading = generalQuery.isLoading && !generalQuery.data;

  if (isInitialLoading) {
    if (isOffline) {
      return (
        <Card className="p-8 text-center">
          <h2 className="text-2xl font-semibold text-mono-black">You are offline</h2>
          <p className="mt-2 text-sm text-mono-gray-600">
            Connect to the network to load System Preferences &amp; Governance.
          </p>
          <Button className="mt-6" variant="primary" size="sm" disabled>
            Waiting for connection
          </Button>
        </Card>
      );
    }
    return <ControlCenterSkeleton />;
  }

  if (generalQuery.isError) {
    const message = generalQuery.error instanceof Error ? generalQuery.error.message : 'Unable to load settings';
    return (
      <Card className="p-8 text-center">
        <h2 className="text-2xl font-semibold text-mono-black">Settings unavailable</h2>
        <p className="mt-2 text-sm text-mono-gray-600">{message}</p>
        {isOffline && (
          <p className="mt-3 text-xs text-mono-gray-500">Reconnect to retry the fetch.</p>
        )}
        <Button
          className="mt-6"
          variant="primary"
          onClick={() => generalQuery.refetch()}
          disabled={isOffline}
        >
          {isOffline ? 'Offline' : 'Retry'}
        </Button>
      </Card>
    );
  }

  return (
    <>
      <div className="grid gap-8 lg:grid-cols-[320px,1fr] xl:gap-12">
        <aside className="space-y-6 lg:sticky lg:top-20 lg:self-start">
          <Card className="border border-mono-gray-200 p-6 shadow-sm lg:max-h-[calc(100vh-140px)] lg:overflow-hidden">
            <div className="flex items-center justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Preferences</p>
                <h2 className="text-lg font-semibold text-mono-black">System Sections</h2>
                <p className="text-xs text-mono-gray-500">Anchored navigation inspired by the provided spec.</p>
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  void handleRefreshAll();
                }}
                disabled={isRefreshing || isOffline}
                className="shrink-0"
              >
                <i className="fas fa-sync mr-2" aria-hidden="true" />
                Refresh
              </Button>
            </div>
            <div className="mt-6 pr-2 lg:max-h-[calc(100vh-230px)] lg:overflow-y-auto">
              <nav className="space-y-3">
                {CONTROL_SECTIONS.map((section) => {
                  const isActive = section.id === activeSection;
                  const meta = section.id === 'raw' ? `${rawEntriesCount} keys` : section.description;
                  return (
                    <button
                      key={section.id}
                      type="button"
                      onClick={() => setActiveSection(section.id)}
                      className={`w-full rounded-3xl px-5 py-4 text-left transition-all duration-200 ${
                        isActive
                          ? 'bg-mono-black text-mono-white shadow-xl shadow-mono-black/25'
                          : 'bg-mono-gray-100 text-mono-gray-700 hover:bg-mono-gray-200'
                      }`}
                    >
                      <span className="block text-sm font-semibold">{section.label}</span>
                      <span className={`text-[11px] uppercase tracking-[0.35em] ${isActive ? 'text-mono-gray-300' : 'text-mono-gray-500'}`}>
                        {meta}
                      </span>
                    </button>
                  );
                })}
              </nav>
            </div>
          </Card>

          <Card className="border border-mono-gray-200 p-6 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Snapshot</p>
            <h3 className="mt-2 text-lg font-semibold text-mono-black">Environment Overview</h3>
            <div className="mt-4 space-y-3">
              {summaryItems.map((item) => (
                <div key={item.label} className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3">
                  <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{item.label}</p>
                  <p className="mt-1 text-sm font-medium text-mono-black">{item.value ?? '—'}</p>
                </div>
              ))}
            </div>
          </Card>
        </aside>

      <section className="space-y-6">
        <header className="space-y-3">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Control Center</p>
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">System Preferences & Governance</h1>
              <p className="mt-2 max-w-2xl text-sm text-mono-gray-600">
                Refine Baraka’s experience with a concise, macOS-inspired surface. Everything you flip here writes straight back to Laravel.
              </p>
            </div>
            <div className="rounded-full border border-mono-gray-200 px-4 py-2 text-right">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                {isOffline ? 'Offline' : 'Online'}
              </p>
              <p className="text-[11px] text-mono-gray-600">{lastSyncedExact}</p>
            </div>
          </div>
        </header>

        <div className="grid gap-4 xl:grid-cols-[1.6fr,1fr]">
          <Card className="border border-mono-gray-200 p-6 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-4">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">System Manager</p>
                <h2 className="text-2xl font-semibold text-mono-black">360° module board</h2>
                <p className="mt-1 text-sm text-mono-gray-600">Pick a module to jump straight into edits.</p>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                <Button variant="primary" size="sm" onClick={handleSave} disabled={isSaving || isOffline || !hasChanges} loading={isSaving}>
                  Save changes
                </Button>
                <Button variant="secondary" size="sm" onClick={handleReset} disabled={isSaving || !hasChanges}>
                  Reset
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    void handleRefreshAll();
                  }}
                  disabled={isRefreshing || isOffline}
                >
                  {isRefreshing ? 'Syncing…' : 'Sync'}
                </Button>
              </div>
            </div>
            <div className="mt-6 grid gap-3 sm:grid-cols-3">
              {telemetryBadges.map((badge) => (
                <div key={badge.label} className="rounded-2xl border border-mono-gray-100 bg-mono-gray-50 p-4">
                  <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{badge.label}</p>
                  <p className="mt-1 text-lg font-semibold text-mono-black">{badge.value}</p>
                  <p className="text-xs text-mono-gray-500">{badge.meta}</p>
                </div>
              ))}
            </div>
            <div className="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              {moduleTiles.map((tile) => {
                const isActive = tile.sectionId === activeSection;
                return (
                  <button
                    key={tile.id}
                    type="button"
                    onClick={() => handleSpotlightNavigate(tile.sectionId)}
                    className={`flex h-full flex-col justify-between rounded-3xl border px-4 py-4 text-left transition ${
                      isActive ? 'border-mono-black shadow-lg' : 'border-mono-gray-200 hover:border-mono-black/60'
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <span className="rounded-2xl p-2 text-mono-white" style={{ backgroundColor: tile.accent }}>
                        <i className={`${tile.icon}`} aria-hidden="true" />
                      </span>
                      <div>
                        <p className="text-sm font-semibold text-mono-black">{tile.label}</p>
                        <p className="text-xs uppercase tracking-[0.3em] text-mono-gray-500">{tile.description}</p>
                      </div>
                    </div>
                    <div className="mt-4 text-right">
                      <p className="text-sm font-semibold text-mono-black">{tile.status}</p>
                      <p className="text-xs text-mono-gray-500">{tile.hint}</p>
                    </div>
                  </button>
                );
              })}
            </div>
          </Card>

          <Card className="border border-mono-gray-200 p-6">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Spotlight</p>
                <h3 className="text-lg font-semibold text-mono-black">Command search</h3>
                <p className="mt-1 text-sm text-mono-gray-600">Jump to any preference, instantly.</p>
              </div>
              <span className="rounded-full border border-mono-gray-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                ⌘K
              </span>
            </div>
            <div className="mt-4 space-y-4">
              <Input
                ref={spotlightInputRef}
                placeholder="Search settings, e.g. currency or webhook"
                value={spotlightTerm}
                onChange={(event) => setSpotlightTerm(event.target.value)}
              />
              <div className="space-y-2">
                {spotlightTerm.trim().length < 2 && (
                  <p className="text-xs text-mono-gray-500">Type at least two characters to surface controls.</p>
                )}
                {spotlightTerm.trim().length >= 2 && spotlightMatches.length === 0 && (
                  <p className="text-xs text-mono-gray-500">No matching controls yet.</p>
                )}
                {spotlightMatches.map((match) => (
                  <button
                    key={match.id}
                    type="button"
                    onClick={() => handleSpotlightNavigate(match.sectionId)}
                    className="flex w-full items-center justify-between rounded-2xl border border-mono-gray-200 px-4 py-3 text-left transition hover:border-mono-black hover:bg-mono-gray-50"
                  >
                    <div className="min-w-0">
                      <p className="text-sm font-semibold text-mono-black">{match.label}</p>
                      <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{match.sectionLabel}</p>
                      <p className="text-xs text-mono-gray-600 truncate">{match.preview}</p>
                    </div>
                    <i className="fas fa-arrow-up-right text-mono-gray-400" aria-hidden="true" />
                  </button>
                ))}
              </div>
              {draftMetadata && (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                  <div>
                    <p className="font-semibold uppercase tracking-[0.3em] text-amber-600">Offline draft</p>
                    <p className="mt-1 text-sm text-amber-800">Saved {formatRelativeTime(draftMetadata.updatedAt)}</p>
                    <p className="text-[11px] text-amber-700">{formatExactTimestamp(draftMetadata.updatedAt)}</p>
                  </div>
                  <Button variant="ghost" size="xs" onClick={handleDiscardDraft}>
                    Clear draft
                  </Button>
                </div>
              )}
            </div>
          </Card>
        </div>

        {isOffline && (
          <div
            className="rounded-2xl border border-amber-400 bg-amber-50 p-4 text-sm text-amber-800"
            role="status"
            aria-live="polite"
          >
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">Offline mode</p>
            <p className="mt-2 text-sm">
              Review and stage updates freely; saving and syncing will resume once you reconnect.
            </p>
          </div>
        )}

        {statusMessage && (
          <div
            className={`rounded-2xl border p-4 text-sm font-medium ${
              statusMessage.type === 'success'
                ? 'border-green-500 bg-green-50 text-green-700'
                : 'border-red-500 bg-red-50 text-red-700'
            }`}
            role="status"
            aria-live="polite"
          >
            {statusMessage.message}
          </div>
        )}

        {dataErrors.map((error) => (
          <div
            key={error.id}
            className="rounded-2xl border border-amber-400 bg-amber-50 p-4 text-sm text-amber-800"
            role="alert"
          >
            <div className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">{error.title}</p>
                <p className="mt-2 text-sm">{error.message}</p>
              </div>
              <Button
                variant="secondary"
                size="sm"
                onClick={() => error.retry()}
                disabled={isOffline}
              >
                {isOffline ? 'Retry when online' : 'Retry'}
              </Button>
            </div>
            {isOffline && (
              <p className="mt-2 text-xs text-amber-700">Reconnect to trigger this request.</p>
            )}
          </div>
        ))}

        {renderActiveSection()}
      </section>
      </div>
      {hasChanges && (
        <div className="fixed bottom-6 right-6 z-30 flex flex-wrap items-center gap-4 rounded-2xl border border-mono-gray-200 bg-white/95 p-4 shadow-2xl backdrop-blur">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Unsaved draft</p>
            <p className="text-sm text-mono-gray-700">You have staged changes that are not yet live.</p>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="primary" size="sm" onClick={handleSave} disabled={isSaving || isOffline} loading={isSaving}>
              <i className="fas fa-save mr-2" aria-hidden="true" />
              Save now
            </Button>
            <Button variant="secondary" size="sm" onClick={handleReset} disabled={isSaving}>
              Discard
            </Button>
          </div>
        </div>
      )}
    </>
  );
};

export default GeneralSettingsPage;

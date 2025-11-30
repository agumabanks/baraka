const DEVICE_UUID_STORAGE_KEY = 'dashboard_device_uuid';
const PUSH_TOKEN_STORAGE_KEY = 'dashboard_push_token';

const fallbackUuid = (): string => {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    try {
      return crypto.randomUUID();
    } catch (error) {
      console.warn('crypto.randomUUID failed, falling back to manual generator', error);
    }
  }

  const randomSegment = () => Math.random().toString(36).slice(2, 10);
  return `web-${Date.now().toString(36)}-${randomSegment()}-${randomSegment()}`;
};

export const getDeviceUuid = (): string => {
  if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
    return fallbackUuid();
  }

  try {
    const cached = window.localStorage.getItem(DEVICE_UUID_STORAGE_KEY);
    if (cached && cached.length > 0) {
      return cached;
    }

    const generated = fallbackUuid();
    window.localStorage.setItem(DEVICE_UUID_STORAGE_KEY, generated);
    return generated;
  } catch (error) {
    console.warn('Unable to access localStorage for device UUID', error);
    return fallbackUuid();
  }
};

export const getPlatformLabel = (): string => {
  if (typeof navigator === 'undefined') {
    return 'web';
  }

  const userAgentData = (navigator as typeof navigator & { userAgentData?: { platform?: string } }).userAgentData;
  if (userAgentData?.platform) {
    return userAgentData.platform.toLowerCase();
  }

  if (navigator.platform) {
    return navigator.platform.toLowerCase();
  }

  return 'web';
};

export const getStoredPushToken = (): string | undefined => {
  if (typeof window === 'undefined' || typeof window.localStorage === 'undefined') {
    return undefined;
  }

  try {
    const token = window.localStorage.getItem(PUSH_TOKEN_STORAGE_KEY);
    return token && token.length > 0 ? token : undefined;
  } catch (error) {
    console.warn('Unable to read push token from storage', error);
    return undefined;
  }
};

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import api from '../services/api';

export interface SystemCurrency {
  id: number;
  name: string;
  symbol: string;
  code: string;
  exchange_rate: number | null;
}

export interface SystemSettings {
  id: number;
  name: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  currency: string;
  currency_id: number | null;
  currency_symbol: string;
  currency_code: string;
  currency_name: string;
  system_currency: SystemCurrency | null;
  copyright: string | null;
  logo_image: string;
  light_logo_image: string;
  favicon_image: string;
  par_track_prefix: string | null;
  invoice_prefix: string | null;
  primary_color: string | null;
  text_color: string | null;
  preferences: Record<string, unknown>;
}

interface SettingsContextType {
  settings: SystemSettings | null;
  loading: boolean;
  error: string | null;
  refetch: () => Promise<void>;
  formatCurrency: (value: number | null | undefined, options?: Intl.NumberFormatOptions) => string;
  getCurrencySymbol: () => string;
  getCurrencyCode: () => string;
  getCurrencyName: () => string;
}

const SettingsContext = createContext<SettingsContextType | undefined>(undefined);

interface SettingsProviderProps {
  children: ReactNode;
}

export const SettingsProvider: React.FC<SettingsProviderProps> = ({ children }) => {
  const [settings, setSettings] = useState<SystemSettings | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  const fetchSettings = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await api.get('/v10/general-settings');
      if (response.data?.data) {
        setSettings(response.data.data);
      }
    } catch (err) {
      console.error('Failed to fetch system settings:', err);
      setError('Failed to load system settings');
      // Set default values as fallback
      setSettings({
        id: 1,
        name: 'Baraka',
        phone: null,
        email: null,
        address: null,
        currency: '$',
        currency_id: 2,
        currency_symbol: '$',
        currency_code: 'USD',
        currency_name: 'Dollars',
        system_currency: {
          id: 2,
          name: 'Dollars',
          symbol: '$',
          code: 'USD',
          exchange_rate: null,
        },
        copyright: null,
        logo_image: '',
        light_logo_image: '',
        favicon_image: '',
        par_track_prefix: 'BRK',
        invoice_prefix: 'INV',
        primary_color: '#1F2937',
        text_color: '#FFFFFF',
        preferences: {},
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  const formatCurrency = (
    value: number | null | undefined,
    options?: Intl.NumberFormatOptions
  ): string => {
    if (value === null || value === undefined) {
      return `${settings?.currency_symbol || '$'}0.00`;
    }

    const currencyCode = settings?.currency_code || 'USD';
    
    try {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        ...options,
      }).format(value);
    } catch (err) {
      // Fallback if currency code is not supported by Intl.NumberFormat
      return `${settings?.currency_symbol || '$'}${value.toFixed(2)}`;
    }
  };

  const getCurrencySymbol = (): string => {
    return settings?.currency_symbol || settings?.currency || '$';
  };

  const getCurrencyCode = (): string => {
    return settings?.currency_code || 'USD';
  };

  const getCurrencyName = (): string => {
    return settings?.currency_name || 'Dollars';
  };

  return (
    <SettingsContext.Provider
      value={{
        settings,
        loading,
        error,
        refetch: fetchSettings,
        formatCurrency,
        getCurrencySymbol,
        getCurrencyCode,
        getCurrencyName,
      }}
    >
      {children}
    </SettingsContext.Provider>
  );
};

export const useSettings = (): SettingsContextType => {
  const context = useContext(SettingsContext);
  if (context === undefined) {
    throw new Error('useSettings must be used within a SettingsProvider');
  }
  return context;
};

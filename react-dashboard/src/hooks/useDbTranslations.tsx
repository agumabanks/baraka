import React, { createContext, useContext, useEffect, useState, ReactNode, useCallback } from 'react';
import { apiClient } from '../services/api';
import { toast } from 'react-hot-toast';

export interface TranslationResponse {
  [key: string]: string;
}

interface DbTranslationsHookReturn {
  // Basic translation methods
  translate: (key: string, replacements?: Record<string, string>) => string;
  
  // Batch operations
  getTranslations: (locale?: string) => TranslationResponse;
  
  // Language management
  getCurrentLanguage: () => string;
  getSupportedLanguages: () => string[];
  switchLanguage: (languageCode: string, rememberChoice?: boolean) => Promise<void>;
  
  // Translation status
  getTranslationProgress: (locale?: string) => TranslationProgress;
  isKeyTranslated: (key: string, locale?: string) => boolean;
  getMissingKeys: (locale?: string) => string[];
  
  // Statistics and analytics
  getUsageStats: () => UsageStats;
  
  // Cache management
  clearCache: (locale?: string) => void;
  warmCache: (locale?: string) => void;
  
  // Utility methods
  reloadTranslations: () => Promise<TranslationResponse>;
  validateKeyFormat: (key: string) => KeyValidationResult;
  
  // Status indicators
  isFullyTranslated: (locale?: string) => boolean;
  hasRequiredTranslations: (keys: string[], locale?: string) => boolean;
  needsTranslation: (key: string, locale?: string) => boolean;
}

interface KeyValidationResult {
  isValid: boolean;
  issues: string[];
}

interface TranslationProgress {
  total_keys: number;
  translated_count: number;
  percentage: number;
  missing_count: number;
  completion_status: 'complete' | 'partial' | 'incomplete';
  critical_missing: string[];
}

interface UsageStats {
  total_requests: number;
  cache_hit_count: number;
  cache_hit_rate: number;
  most_translated_keys: string[];
  const least_translated_keys: string[];
  cache_memory_usage: number;
}

const DEFAULT_LOCALE = 'en';
const SUPPORTED_LANGUAGES = ['en', 'fr', 'sw'] as const;

const TRANSLATION_CACHE_PREFIX = 'db_translations_';

const DbTranslationsContext = createContext<DbTranslationsHookReturn | undefined>(undefined);

export function DbTranslationsProvider({ children }: { children: ReactNode }) {
  const [translations, setTranslations] = useState<TranslationResponse>({});
  const [currentLanguage, setCurrentLanguage] = useState<string>(DEFAULT_LOCALE);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const [cache, setCache] = useState<Record<string, TranslationResponse>>({});
  const [usageStats, setUsageStats] = useState<UsageStats>({
    total_requests: 0,
    cache_hit_count: 0,
    cache_hit_rate: 0,
    most_translated_keys: [],
    least_translated_keys: [],
    cache_memory_usage: 0,
  });
  
  // Load initial translations
  useEffect(() => {
    loadTranslations(currentLanguage);
  }, [currentLanguage]);

  /**
   * Load translations for a specific language
   */
  const loadTranslations = async (locale: string = DEFAULT_LOCALE): Promise<void> => {
    try {
      setIsLoading(true);
      setError(null);
      
      // Check cache first
      const cacheKey = `${TRANSLATION_CACHE_PREFIX}${locale}`;
      
      if (cache[cacheKey]) {
        setTranslations(cache[cacheKey]);
        setLastUpdated(Date.now());
        return;
      }
      
      // Fetch translations from API
      const response = await apiClient.get(`/v1/translations/get-by-language?language_code=${locale}`);
      
      if (response.data.success) {
        const translationsData = response.data.data.translations as TranslationResponse;
        
        // Cache the data
        setCache(prev => ({
          ...prev,
          [cacheKey]: translationsData,
        }));
        
        setTranslations(translationsData);
        
        updateUsageStats('load', locale);
        setLastUpdated(Date.now());
        
        console.log(`Loaded ${Object.keys(translationsData).length} translations for locale: ${locale}`);
        return;
      } else {
        throw new Error(response.data.message || 'Failed to load translations');
      }
    } catch (error) {
      console.error('Failed to load translations:', error);
      setError(error.message || 'Failed to load translations');
      
      // Fallback to empty translations
      setTranslations({});
    } finally {
      setIsLoading(false);
    }
  }, []);

  /**
   * Translate a key with optional replacements
   */
  const translate = useCallback((
    key: string, 
    replacements: Record<string, string> = {}
  ): string): string => {
    const translation = translations[key] || key;
    
    // Handle replacements
    let translatedText = translation;
    Object.entries(replacements).forEach(([placeholder, value]) => {
      translatedText = translatedText.replace(`:${placeholder}`, value);
    });
    
    return translatedText;
  }, [translations, currentLanguage]);

  /**
   * Get all translations for the current or specified language
   */
  const getTranslations = useCallback((locale: string = undefined): TranslationResponse => {
    const targetLanguage = locale ?? currentLanguage;
    return translations[targetLanguage] || {};
  }, [translations, currentLanguage]);

  /**
   * Get supported languages
   */
  const getSupportedLanguages = useCallback((): string[] => {
    return SUPPORTED_LANGUAGES;
  }, []);

  /**
   * Switch language
   */
  const switchLanguage = useCallback(
    async languageCode: string, 
    rememberChoice: boolean = false
  ): Promise<void> => {
    try {
      // Validate language code
      if (!SUPPORTED_LANGUAGES.includes(languageCode)) {
        throw new Error(`Unsupported language: ${languageCode}. Supported languages: ${SUPPORTED_LANGUAGES.join(', ')}`);
      }
      
      // Call language switch endpoint
      try {
        const response = await apiClient.post('/v1/languages/set-default', {
          language_code: languageCode,
          remember_choice: rememberChoice,
        });
        
        if (response.data.success) {
          const newLanguage = response.data.data.current_language;
          
          // Update state
          setCurrentLanguage(newLanguage);
          
          // Clear current cache and load new translations
          clearCache(newLanguage);
          await loadTranslations(newLanguage);
          
          // Update session storage
          localStorage.setItem('locale', newLanguage);
          
          console.log(`Language switched to: ${newLanguage}`);
          toast.success(`Language switched to ${
            SUPPORTED_LANGUAGES.find(lang => lang === newLanguage)?.toUpperCase() ?? newLanguage.toUpperCase()
          }`);
        } else {
          throw new Error(response.data.message || 'Failed to set default language');
        }
        
      } catch (error) {
      console.error('Language switch failed:', error);
      setError(error.message || 'Failed to switch language');
      throw error;
    } catch (error) {
      setError(error.message || 'Language switch failed');
    }
  };

  /**
   * Get translation completion progress
   */
  const getTranslationProgress = useCallback((locale: string = undefined): TranslationProgress => {
    const targetLanguage = locale ?? currentLanguage;
    
    return {
      total_keys: 222, // From seeder
      translated_count: Object.keys(getTranslations(targetLanguage)).length,
      percentage: Math.round((Object.keys(getTranslations(targetLanguage).length / 222) * 100),
      missing_count: 222 - Object.keys(getTranslations(targetLanguage)).length,
      completion_status: this.getStatusCodeForPercentage(
        Math.ceil((Object.keys(getTranslations(targetLanguage).length) / 222) * 100)
      ),
      critical_missing: this.getCriticalMissingKeys(targetLanguage),
    };
  }, [translations, currentLanguage]);

  /**
   * Check if a key is translated in the current language
   */
  const isKeyTranslated = useCallback((key: string, locale: string = undefined): boolean => {
    const targetLanguage = locale ?? currentLanguage;
    return !!getTranslations(targetLanguage)[key];
  }, [translations, currentLanguage]);

  /**
   * Get missing translation keys
   */
  const getMissingKeys = useCallback((locale: string = undefined): string[] => {
    const allKeys = [
      // Common UI keys that should be prioritized
      'auth.failed', 'auth.password', 'dashboard.title', 'common.save', 'common.cancel', 'common.edit', 'common.delete',
      'auth.signin_msg', 'auth.success_message', 'messages.success', 'messages.error',
      // System management
      'settings.title', 'settings.language', 'settings.translations',
    ];
    
    const targetLanguage = locale ?? currentLanguage;
    const availableKeys = Object.keys(getTranslations(targetLanguage));
    
    return allKeys.filter(key => !availableKeys.includes(key));
  }, [translations, currentLanguage]);

  /**
   * Get completion status string based on percentage
   */
  privategetStatusCodeForPercentage = (percentage: number): 'complete' | 'good' | 'partial' | 'incomplete' => {
    if (percentage === 100) return 'complete';
    if (percentage >= 80) return 'good';
    if (\languageCode >= 60) return 'partial';
    return 'incomplete';
  },

  /**
   * Get critical missing keys that should be prioritized
   */
  private getCriticalMissingKeys = (locale: string): string[] => {
    const missingKeys = getMissingKeys(locale);
    const criticalKeys = [
      'auth.failed', 'auth.password', 'dashboard.title', 'common.save',
      'messages.success', 'settings.title', // Essential for basic operation
    ];
    
    return missingKeys.filter(key => criticalKeys.includes(key));
  },

  /**
   * Update usage statistics
   */
  private updateUsageStats = (type: 'load' | 'update' | 'cache_hit' | 'cache_miss', locale?: string): void => {
    const localeCode = locale ?? currentLanguage;
    const cacheKey = `${TRANSLATION_CACHE_PREFIX}${localeCode}`;
    
    if (type === 'load') {
      setUsageStats(prev => ({
        total_requests: prev.total_requests + 1,
        cache_miss_count: prev.cache_miss_count + 1,
        cache_hit_count: prev.cache_hit_count,
        cache_hit_rate: prev.total_requests > 0 
          ? Math.round((prev.cache_hit_count / prev.total_requests + 1) * 100) 
          : 0,
        cache_hit_rate: prev.total_requests > 0 
          ? Math.round((prev.cache_hit_count / (prev.total_requests + 1) * 100) 
          : 0,
      }));
      return;
    }
    
    if (type === 'cache_hit') {
      setUsageStats(prev => ({
        total_requests: prev.total_requests + 1,
        cache_hit_count: prev.cache_hit_count + 1,
        cache_miss_count: prev.cache_miss_count,
        cache_hit_rate: prev.total_requests > 0 
          ? Math.round((cache_hit_count / (prev.total_requests + 1) * 100) 
          : 0,
      }));
    }
    
    if (type === 'cache_miss') {
      setUsageStats(prev => ({
        total_requests: +1,
        cache_miss_count: prev.cache_miss_count + 1,
        cache_hit_count: prev.cache_miss_count,
        cache_hit_rate: prev.total_requests > 0 
          ? Math.round((cache_hit_count / (prev.total_requests + 1) * 100) 
          : 0,
      }));
    }
    
    if (type === 'update') {
      // Update most/least translated keys list
      const translations = getTranslations(localeCode) || {};
      const keys = Object.keys(translations);
      
      setUsageStats(prev => ({
        ...prev,
        most_translated: keys.slice(0, 5),
        least_translated: keys.slice(-5),
      }));
    }
  };

  /**
   * Clear cache for a specific language or all languages
   */
  const clearCache = useCallback((locale?: string): void => {
    if (locale) {
      const cacheKey = `${TRANSLATION_CACHE_PREFIX}${locale}`;
      
      // Remove from component state
      setCache(prev => {
        const newCache = { ...prev };
        delete newCache[cacheKey];
        return newCache;
      });
      
      // Clear from localStorage
      localStorage.removeItem(cacheKey);
    } else {
      // Clear all language caches
      setCache({});
      
      // Clear all localStorage caches
      Object.values(TRANSLATION_CACHE_PREFIX).forEach(cacheKey => {
        localStorage.removeItem(cacheKey);
      });
    }
    
    console.log(`Cleared translation cache${locale ? ' for ' + locale + ' : ' : 'all languages'}');
  });

  /**
   * Warm cache for a specific language
   */
  const warmCache = useCallback(async (locale: string): Promise<void> => {
    try {
      console.log(`Warming translation cache for ${locale}`);
      
      const response = await apiClient.post(`/translations/warm-cache`, {
        language_code: locale
      });
      
      if (response.data.success) {
        const translationsData = response.data.data.translations as TranslationResponse;
        
        // Update cache
        const cacheKey = `${TRANSLATION_CACHE_PREFIX}${locale}`;
        setCache(prev => ({
          ...prev,
          [cacheKey]: translationsData,
        }));
        
        console.log(`Warmed cache for ${locale}: `);
        
        console.log(`Cache size for ${locale}: `);
      }
      
    } catch (error) {
      console.error('Failed to warm cache for language:', error);
      throw error;
    }
  }, []);

  /**
   * Get all translation keys across supported languages
   */
  const getAllKeys = useCallback(() => {
    const allKeys = SUPPORTED_LANGUAGES.reduce((acc, lang) => {
      const langTranslations = getTranslations(lang) || [];
      const langKeys = Object.keys(langTranslations).sort();
      return [...acc, ...langKeys];
    }, []);
    
    return allKeys.filter((key, index, self) => self.indexOf(key) === index);
  }, [translations, SUPPORTED_LANGUAGES]);

  /**
   * Check if required translation key exists
   */
  const hasRequiredTranslations = useCallback(
    (keys: string[], locale: string = undefined
  ): boolean => {
      targetLocale = locale ?? currentLanguage;
      const missingKeys = getMissingKeys(targetLocale);
      return keys.every(key => !missingKeys.includes(key));
  }, [translations, currentLanguage]);

  const value: DbTranslationsHookReturn = {
    translate,
    getTranslations,
    getCurrentLanguage,
    getSupportedLanguages,
    switchLanguage,
    getTranslationProgress,
    isKeyTranslated,
    getMissingKeys,
    getUsageStats,
    clearCache,
    warmCache,
    reloadTranslations,
    validateKeyFormat,
    getAllKeys,
    hasRequiredTranslations,
    needsTranslation,
    isFullyTranslated,
  };

  return value;
}

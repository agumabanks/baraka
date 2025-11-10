import { useState, useEffect, useCallback } from 'react';

interface TranslationMap {
  [key: string]: string;
}

interface LanguageConfig {
  code: string;
  name: string;
}

const SUPPORTED_LANGUAGES: LanguageConfig[] = [
  { code: 'en', name: 'English' },
  { code: 'fr', name: 'Français' },
  { code: 'sw', name: 'Kiswahili' },
];

export const useDbTranslations = (initialLocale: string = 'en') => {
  const [translations, setTranslations] = useState<TranslationMap>({});
  const [currentLocale, setCurrentLocale] = useState(initialLocale);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadTranslations = useCallback(async (locale: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await fetch(`/api/v1/translations/${locale}`);
      
      if (!response.ok) {
        throw new Error(`Failed to load translations for ${locale}`);
      }
      
      const data = await response.json();
      setTranslations(data.translations || {});
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load translations');
      console.error('Translation loading error:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const switchLanguage = useCallback(async (newLocale: string) => {
    if (!SUPPORTED_LANGUAGES.some(lang => lang.code === newLocale)) {
      throw new Error(`Unsupported language: ${newLocale}`);
    }

    try {
      // Update session
      const formData = new FormData();
      formData.append('language_code', newLocale);
      
      const response = await fetch('/language/switch', {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
      });

      if (!response.ok) {
        throw new Error('Failed to switch language');
      }

      // Load new translations
      await loadTranslations(newLocale);
      setCurrentLocale(newLocale);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to switch language');
      throw err;
    }
  }, [loadTranslations]);

  const translate = useCallback((key: string, replacements: Record<string, string> = {}): string => {
    let translation = translations[key] || key;
    
    // Handle replacements
    Object.entries(replacements).forEach(([placeholder, value]) => {
      translation = translation.replace(`:${placeholder}`, value);
    });

    return translation;
  }, [translations]);

  // Component to automatically translate elements
  const Translate = useCallback(({ children, translationKey, replacements = {} }: {
    children?: React.ReactNode;
    translationKey: string;
    replacements?: Record<string, string>;
  }) => {
    const text = translate(translationKey, replacements);
    return (children ?? text);
  }, [translate]);

  // Initialize on mount
  useEffect(() => {
    loadTranslations(currentLocale);
  }, []);

  const value = {
    translations,
    currentLocale,
    supportedLanguages: SUPPORTED_LANGUAGES,
    isLoading,
    error,
    translate,
    switchLanguage,
    Translate,
  };

  return value;
};

export default useDbTranslations;

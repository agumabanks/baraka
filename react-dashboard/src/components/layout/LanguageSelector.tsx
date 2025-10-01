import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import type { LanguageSelectorProps } from '../../types/header';

/**
 * Language Selector Component
 * Dropdown for language switching with flag icons
 */
const LanguageSelector: React.FC<LanguageSelectorProps> = ({
  currentLanguage,
  languages,
  onLanguageChange
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const handleLanguageSelect = (language: typeof currentLanguage) => {
    onLanguageChange(language);
    setIsOpen(false);
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      setIsOpen(false);
    }
  };

  return (
    <div className="relative">
      <button
        type="button"
        className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-mono-gray-700 hover:text-mono-black transition-colors rounded-lg hover:bg-mono-gray-50"
        onClick={() => setIsOpen(!isOpen)}
        onKeyDown={handleKeyDown}
        aria-expanded={isOpen}
        aria-haspopup="listbox"
        aria-label="Select language"
      >
        <span className={`flag-icon flag-icon-${currentLanguage.flag}`} />
        <span className="hidden md:inline">{currentLanguage.name}</span>
        <ChevronDown
          size={16}
          className={`transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
        />
      </button>

      {isOpen && (
        <>
          {/* Overlay for mobile */}
          <div
            className="fixed inset-0 z-10 lg:hidden"
            onClick={() => setIsOpen(false)}
            aria-hidden="true"
          />

          {/* Dropdown menu */}
          <div
            className="absolute right-0 mt-2 w-48 bg-mono-white border border-mono-gray-200 rounded-lg shadow-lg z-20"
            role="listbox"
            aria-label="Language options"
          >
            {languages.map((language) => (
              <button
                key={language.code}
                type="button"
                className={`w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-mono-gray-50 transition-colors ${
                  language.code === currentLanguage.code
                    ? 'bg-mono-gray-100 text-mono-black'
                    : 'text-mono-gray-700'
                }`}
                onClick={() => handleLanguageSelect(language)}
                role="option"
                aria-selected={language.code === currentLanguage.code}
              >
                <span className={`flag-icon flag-icon-${language.flag}`} />
                <span className="text-sm font-medium">{language.name}</span>
                {language.code === currentLanguage.code && (
                  <span className="ml-auto text-mono-black">✓</span>
                )}
              </button>
            ))}
          </div>
        </>
      )}
    </div>
  );
};

export default LanguageSelector;
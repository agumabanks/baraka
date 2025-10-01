import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import type { LanguageSelectorProps } from '../../types/header';

const LanguageSelector: React.FC<LanguageSelectorProps> = ({
  currentLanguage,
  languages,
  onLanguageChange,
}) => {
  const [isOpen, setIsOpen] = useState(false);

  const toggle = () => setIsOpen((prev) => !prev);

  const handleSelect = (languageCode: string) => {
    const language = languages.find((item) => item.code === languageCode);
    if (language) {
      onLanguageChange(language);
      setIsOpen(false);
    }
  };

  return (
    <div className="relative">
      <button
        type="button"
        className="flex items-center gap-2 rounded-full border border-mono-gray-300 px-3.5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-mono-gray-700 transition-colors hover:border-mono-black hover:text-mono-black"
        onClick={toggle}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-label="Select interface language"
      >
        {currentLanguage.code.toUpperCase()}
        <ChevronDown size={14} className={`transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && (
        <div className="absolute right-0 mt-3 w-40 rounded-2xl border border-mono-gray-200 bg-mono-white shadow-xl">
          <ul role="listbox" className="py-2">
            {languages.map((language) => (
              <li key={language.code} role="option" aria-selected={language.code === currentLanguage.code}>
                <button
                  type="button"
                  className={`flex w-full items-center justify-between px-4 py-2 text-sm transition-colors ${
                    language.code === currentLanguage.code
                      ? 'bg-mono-gray-100 text-mono-black'
                      : 'text-mono-gray-700 hover:bg-mono-gray-50 hover:text-mono-black'
                  }`}
                  onClick={() => handleSelect(language.code)}
                >
                  <span>{language.name}</span>
                  {language.code === currentLanguage.code && <span className="text-xs">✓</span>}
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
};

export default LanguageSelector;

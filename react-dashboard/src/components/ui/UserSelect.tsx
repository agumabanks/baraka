import React, { useState, useRef, useEffect } from 'react';
import Avatar from './Avatar';

export interface UserOption {
  id: string;
  name: string;
  avatar?: string | null;
  initials?: string;
}

interface UserSelectProps {
  label?: string;
  value: string;
  onChange: (userId: string) => void;
  options: UserOption[];
  placeholder?: string;
  error?: string;
  disabled?: boolean;
}

const UserSelect: React.FC<UserSelectProps> = ({
  label,
  value,
  onChange,
  options,
  placeholder = 'Select a team member...',
  error,
  disabled = false,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const dropdownRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        setIsOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const filteredOptions = options.filter((option) =>
    option.name.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const selectedUser = options.find((opt) => opt.id === value);

  const handleSelect = (userId: string) => {
    onChange(userId);
    setIsOpen(false);
    setSearchTerm('');
  };

  const handleClear = (e: React.MouseEvent) => {
    e.stopPropagation();
    onChange('');
    setSearchTerm('');
  };

  return (
    <div className="relative">
      {label && (
        <label className="block text-sm font-medium text-mono-black mb-2">
          {label}
        </label>
      )}
      <div
        ref={dropdownRef}
        className={`relative ${error ? 'border-red-500' : ''}`}
      >
        <button
          type="button"
          onClick={() => {
            setIsOpen(!isOpen);
            if (!isOpen) inputRef.current?.focus();
          }}
          disabled={disabled}
          className={`w-full px-4 py-2 border rounded-lg text-left flex items-center justify-between transition-colors ${
            error ? 'border-red-500' : 'border-mono-gray-300'
          } ${disabled ? 'bg-mono-gray-100 cursor-not-allowed' : 'bg-white hover:border-mono-gray-400'} focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent`}
        >
          <div className="flex items-center gap-2 flex-1 min-w-0">
            {selectedUser ? (
              <>
                <Avatar src={selectedUser.avatar ?? undefined} fallback={selectedUser.initials ?? '?'} size="sm" />
                <span className="text-sm font-medium truncate">{selectedUser.name}</span>
              </>
            ) : (
              <span className="text-sm text-mono-gray-500">{placeholder}</span>
            )}
          </div>
          <div className="flex items-center gap-1 ml-2 flex-shrink-0">
            {selectedUser && !disabled && (
              <button
                type="button"
                onClick={handleClear}
                className="text-mono-gray-400 hover:text-mono-black p-1"
                aria-label="Clear selection"
              >
                <i className="fas fa-times text-sm" aria-hidden="true" />
              </button>
            )}
            <i
              className={`fas fa-chevron-down text-mono-gray-400 transition-transform ${
                isOpen ? 'rotate-180' : ''
              }`}
              aria-hidden="true"
            />
          </div>
        </button>

        {isOpen && (
          <div className="absolute top-full left-0 right-0 z-50 mt-1 bg-white border border-mono-gray-300 rounded-lg shadow-lg">
            <div className="p-2 border-b border-mono-gray-200">
              <input
                ref={inputRef}
                type="text"
                placeholder="Search team members..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full px-3 py-2 border border-mono-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-transparent"
              />
            </div>
            <div className="max-h-64 overflow-y-auto">
              {filteredOptions.length > 0 ? (
                filteredOptions.map((option) => (
                  <button
                    key={option.id}
                    type="button"
                    onClick={() => handleSelect(option.id)}
                    className={`w-full px-4 py-3 text-left flex items-center gap-3 transition-colors ${
                      value === option.id
                        ? 'bg-mono-black text-white'
                        : 'hover:bg-mono-gray-50'
                    }`}
                  >
                    <Avatar
                      src={option.avatar ?? undefined}
                      fallback={option.initials ?? '?'}
                      size="sm"
                    />
                    <div className="flex-1">
                      <div className="text-sm font-medium">{option.name}</div>
                    </div>
                    {value === option.id && (
                      <i className="fas fa-check text-sm" aria-hidden="true" />
                    )}
                  </button>
                ))
              ) : (
                <div className="px-4 py-3 text-sm text-mono-gray-500">
                  No team members found
                </div>
              )}
            </div>
          </div>
        )}
      </div>
      {error && (
        <p className="mt-1 text-sm text-red-600">{error}</p>
      )}
    </div>
  );
};

export default UserSelect;

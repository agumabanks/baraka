import React from 'react';

/**
 * Form input component with monochrome styling and validation states
 */
interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  /** Optional label text */
  label?: string;
  /** Error message to display */
  error?: string;
  /** Helper text to display */
  helperText?: string;
}

const Input: React.FC<InputProps> = ({ label, error, helperText, className = '', ...rest }) => {
  const inputClasses = `w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-mono-black transition-colors ${
    error ? 'border-red-500' : 'border-mono-gray-300'
  } ${className}`;

  return (
    <div className="space-y-1">
      {label && (
        <label className="block text-sm font-medium text-mono-gray-900">
          {label}
        </label>
      )}
      <input className={inputClasses} {...rest} />
      {error && <p className="text-sm text-red-600">{error}</p>}
      {helperText && !error && <p className="text-sm text-mono-gray-600">{helperText}</p>}
    </div>
  );
};

export default Input;
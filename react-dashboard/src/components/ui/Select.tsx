import React from 'react';

/**
 * Custom styled select dropdown with monochrome theme
 */
interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  /** Optional label text */
  label?: string;
  /** Error message to display */
  error?: string;
  /** Array of options for the select */
  options: { value: string; label: string }[];
}

const Select: React.FC<SelectProps> = ({ label, error, options, className = '', ...rest }) => {
  const selectClasses = `w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-mono-black transition-colors bg-mono-white ${
    error ? 'border-red-500' : 'border-mono-gray-300'
  } ${className}`;

  return (
    <div className="space-y-1">
      {label && (
        <label className="block text-sm font-medium text-mono-gray-900">
          {label}
        </label>
      )}
      <select className={selectClasses} {...rest}>
        {options.map(option => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error && <p className="text-sm text-red-600">{error}</p>}
    </div>
  );
};

export default Select;
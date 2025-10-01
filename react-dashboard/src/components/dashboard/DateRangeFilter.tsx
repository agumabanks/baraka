import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

interface DateRangeFilterProps {
  /** Current date filter value */
  value?: string;
  /** Callback when filter changes */
  onChange?: (dateRange: string) => void;
  /** Whether to show loading state */
  loading?: boolean;
}

/**
 * Date Range Filter Component
 * Matches Blade dashboard filter with YYYY-MM-DD format
 * Rounded pill styling with monochrome design
 */
const DateRangeFilter: React.FC<DateRangeFilterProps> = ({
  value = '',
  onChange,
  loading = false,
}) => {
  const [inputValue, setInputValue] = useState(value);
  const navigate = useNavigate();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (inputValue.trim()) {
      // Navigate with filter parameter
      const url = `/dashboard?filter_date=${encodeURIComponent(inputValue)}`;
      navigate(url);
      
      if (onChange) {
        onChange(inputValue);
      }
    }
  };

  return (
    <div className="flex justify-end">
      <form onSubmit={handleSubmit} className="flex items-center gap-3">
        <input
          type="text"
          name="filter_date"
          placeholder="YYYY-MM-DD"
          autoComplete="off"
          className="px-4 py-2 border border-mono-gray-300 rounded-full bg-mono-white text-mono-gray-900 placeholder-mono-gray-500 focus:outline-none focus:ring-2 focus:ring-mono-black focus:border-mono-black transition-all duration-200 min-w-[200px]"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          required
          disabled={loading}
        />
        <button
          type="submit"
          className="px-4 py-2 bg-mono-black text-mono-white border border-mono-black rounded-full hover:bg-mono-gray-800 focus:outline-none focus:ring-2 focus:ring-mono-black focus:ring-offset-2 transition-all duration-200 font-medium uppercase text-sm tracking-wide disabled:opacity-50 disabled:cursor-not-allowed"
          disabled={loading}
        >
          {loading ? 'Loading...' : 'Filter'}
        </button>
      </form>
    </div>
  );
};

export default DateRangeFilter;
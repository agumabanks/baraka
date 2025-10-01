import React from 'react';
import Spinner from './Spinner';

/**
 * Full-page loading spinner component
 * Displays a centered loading state with monochrome styling
 */
interface LoadingSpinnerProps {
  /** Optional message to display below spinner */
  message?: string;
  /** Size of the spinner */
  size?: 'sm' | 'md' | 'lg';
  /** Additional CSS classes */
  className?: string;
}

const LoadingSpinner: React.FC<LoadingSpinnerProps> = ({
  message = 'Loading...',
  size = 'lg',
  className = '',
}) => {
  return (
    <div
      className={`flex flex-col items-center justify-center min-h-[400px] ${className}`}
      role="status"
      aria-live="polite"
    >
      <Spinner size={size} />
      {message && (
        <p className="mt-4 text-sm text-mono-gray-600 font-medium">
          {message}
        </p>
      )}
    </div>
  );
};

export default LoadingSpinner;
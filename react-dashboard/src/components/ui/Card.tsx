import React from 'react';

/**
 * Container component with monochrome styling and optional sections
 */
interface CardProps {
  /** Card content */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
  /** Optional header content */
  header?: React.ReactNode;
  /** Optional footer content */
  footer?: React.ReactNode;
}

const Card: React.FC<CardProps> = ({ children, className = '', header, footer }) => {
  return (
    <div
      className={`bg-mono-white border border-mono-gray-200 rounded-2xl shadow-lg transition-shadow hover:shadow-xl ${className}`}
    >
      {header && (
        <div className="px-6 py-4 border-b border-mono-gray-200 bg-mono-gray-25">
          {header}
        </div>
      )}
      <div className="p-6">
        {children}
      </div>
      {footer && (
        <div className="px-6 py-4 border-t border-mono-gray-200 bg-mono-gray-50">
          {footer}
        </div>
      )}
    </div>
  );
};

export default Card;

import React from 'react';

/**
 * User profile image component with fallback to initials
 */
interface AvatarProps {
  /** Image source URL */
  src?: string;
  /** Alt text for the image */
  alt?: string;
  /** Size variant */
  size?: 'sm' | 'md' | 'lg';
  /** Fallback text (e.g., initials) */
  fallback?: string;
  /** Additional CSS classes */
  className?: string;
}

const Avatar: React.FC<AvatarProps> = ({ src, alt = '', size = 'md', fallback, className = '' }) => {
  const sizeClasses = {
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-12 h-12 text-lg',
  };

  const baseClasses = `rounded-full border-2 border-mono-gray-200 flex items-center justify-center font-medium text-mono-gray-900 bg-mono-gray-100 ${sizeClasses[size]} ${className}`;

  if (src) {
    return <img src={src} alt={alt} className={baseClasses} />;
  }

  return <div className={baseClasses}>{fallback || '?'}</div>;
};

export default Avatar;
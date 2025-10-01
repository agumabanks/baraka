import React from 'react';

/**
 * Status indicator badge with monochrome styling
 */
interface BadgeProps {
  /** Visual variant of the badge */
  variant?: 'solid' | 'outline' | 'ghost';
  /** Size variant of the badge */
  size?: 'sm' | 'md' | 'lg';
  /** Badge content */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
}

const Badge: React.FC<BadgeProps> = ({ variant = 'solid', size = 'md', children, className = '' }) => {
  const baseClasses = 'inline-flex items-center font-medium rounded-md';

  const variantClasses = {
    solid: 'bg-mono-black text-mono-white',
    outline: 'border border-mono-gray-300 text-mono-gray-900',
    ghost: 'text-mono-gray-700',
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-sm',
    lg: 'px-3 py-1 text-base',
  };

  const classes = `${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`;

  return <span className={classes}>{children}</span>;
};

export default Badge;
import React from 'react';

/**
 * Status indicator badge with monochrome styling
 */
interface BadgeProps {
  /** Visual variant of the badge */
  variant?: 'solid' | 'outline' | 'ghost' | 'warning' | 'error' | 'info' | 'success' | 'default' | 'destructive';
  /** Size variant of the badge */
  size?: 'sm' | 'md' | 'lg';
  /** Badge content */
  children: React.ReactNode;
  /** Additional CSS classes */
  className?: string;
}

const Badge: React.FC<BadgeProps> = ({ variant = 'solid', size = 'md', children, className = '' }) => {
  const baseClasses = 'inline-flex items-center font-medium rounded-md';

  const variantClasses: Record<NonNullable<BadgeProps['variant']>, string> = {
    solid: 'bg-mono-black text-mono-white',
    outline: 'border border-mono-gray-300 text-mono-gray-900',
    ghost: 'text-mono-gray-700',
    warning: 'bg-amber-500/20 text-amber-700 border border-amber-600/50',
    error: 'bg-rose-500/20 text-rose-700 border border-rose-600/50',
    info: 'bg-sky-500/20 text-sky-700 border border-sky-600/50',
    success: 'bg-emerald-500/20 text-emerald-700 border border-emerald-600/50',
    default: 'bg-mono-gray-200 text-mono-gray-900',
    destructive: 'bg-rose-500/20 text-rose-700 border border-rose-600/50',
  };

  const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-0.5 text-sm',
    lg: 'px-3 py-1 text-base',
  };

  const classes = `${baseClasses} ${variantClasses[variant]} ${sizeClasses[size]} ${className}`;

  return <span className={classes}>{children}</span>;
};

export { Badge };
export default Badge;

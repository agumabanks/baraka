import React from 'react';

interface AlertProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'default' | 'destructive' | 'warning' | 'success' | 'info';
}

const VARIANT_STYLES: Record<NonNullable<AlertProps['variant']>, string> = {
  default: 'border border-mono-gray-300 bg-mono-gray-50 text-mono-gray-900',
  destructive: 'border border-rose-300 bg-rose-50 text-rose-800',
  warning: 'border border-amber-300 bg-amber-50 text-amber-900',
  success: 'border border-emerald-300 bg-emerald-50 text-emerald-800',
  info: 'border border-sky-300 bg-sky-50 text-sky-800',
};

export const Alert: React.FC<AlertProps> = ({
  variant = 'default',
  className = '',
  children,
  ...rest
}) => (
  <div
    role="alert"
    className={`flex items-start gap-3 rounded-lg p-4 ${VARIANT_STYLES[variant]} ${className}`.trim()}
    {...rest}
  >
    {children}
  </div>
);

interface AlertDescriptionProps extends React.HTMLAttributes<HTMLDivElement> {}

export const AlertDescription: React.FC<AlertDescriptionProps> = ({ className = '', children, ...rest }) => (
  <div className={`text-sm leading-5 text-current ${className}`.trim()} {...rest}>
    {children}
  </div>
);

export default Alert;

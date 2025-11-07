import React, { forwardRef, useState, useId } from 'react';
import { AlertCircle, Eye, EyeOff } from 'lucide-react';

/**
 * Accessible Form Input Component
 * WCAG 2.1 AA compliant with comprehensive ARIA support
 */
interface AccessibleInputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label: string;
  error?: string;
  helperText?: string;
  required?: boolean;
  showPasswordToggle?: boolean;
  icon?: React.ReactNode;
  containerClassName?: string;
  labelClassName?: string;
  inputClassName?: string;
  errorClassName?: string;
  helperTextClassName?: string;
}

export const AccessibleInput = forwardRef<HTMLInputElement, AccessibleInputProps>(
  ({
    label,
    error,
    helperText,
    required = false,
    showPasswordToggle = false,
    icon,
    type = 'text',
    containerClassName = '',
    labelClassName = '',
    inputClassName = '',
    errorClassName = '',
    helperTextClassName = '',
    id: providedId,
    'aria-describedby': ariaDescribedBy,
    ...props
  }, ref) => {
    const generatedId = useId();
    const id = providedId || generatedId;
    const [showPassword, setShowPassword] = useState(false);
    const [focused, setFocused] = useState(false);
    
    const actualType = showPasswordToggle && type === 'password' 
      ? (showPassword ? 'text' : 'password') 
      : type;

    const inputContainerId = `${id}-container`;
    const errorId = `${id}-error`;
    const helperTextId = `${id}-helper`;
    const descriptionIds = [
      error ? errorId : null,
      helperText ? helperTextId : null,
      ariaDescribedBy,
    ].filter(Boolean).join(' ');

    const hasError = Boolean(error);
    const hasIcon = Boolean(icon);

    return (
      <div 
        className={`accessible-input-container ${containerClassName}`}
        id={inputContainerId}
        data-required={required}
        data-error={hasError}
        data-focused={focused}
      >
        <label 
          htmlFor={id}
          className={`
            accessible-input-label
            block text-sm font-medium text-gray-700 mb-1
            ${labelClassName}
            ${required ? 'required-field' : ''}
            ${hasError ? 'error-text' : ''}
          `}
          aria-required={required}
        >
          {label}
          {required && (
            <span 
              className="text-red-500 ml-1" 
              aria-label="required"
            >
              *
            </span>
          )}
        </label>

        <div className="relative">
          {hasIcon && (
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <div className={`
                accessible-input-icon
                ${hasError ? 'text-red-400' : 'text-gray-400'}
              `}>
                {icon}
              </div>
            </div>
          )}

          <input
            ref={ref}
            id={id}
            type={actualType}
            className={`
              accessible-input
              block w-full rounded-md border-gray-300 shadow-sm
              focus:border-blue-500 focus:ring-blue-500
              disabled:bg-gray-50 disabled:text-gray-500
              ${hasIcon ? 'pl-10' : 'pl-3'}
              ${showPasswordToggle ? 'pr-10' : 'pr-3'}
              ${hasError ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}
              ${inputClassName}
            `}
            aria-describedby={descriptionIds || undefined}
            aria-invalid={hasError}
            aria-required={required}
            onFocus={() => setFocused(true)}
            onBlur={() => setFocused(false)}
            {...props}
          />

          {showPasswordToggle && type === 'password' && (
            <button
              type="button"
              className="absolute inset-y-0 right-0 pr-3 flex items-center"
              onClick={() => setShowPassword(!showPassword)}
              aria-label={showPassword ? 'Hide password' : 'Show password'}
              aria-pressed={showPassword}
            >
              {showPassword ? (
                <EyeOff className="h-5 w-5 text-gray-400" />
              ) : (
                <Eye className="h-5 w-5 text-gray-400" />
              )}
            </button>
          )}
        </div>

        {error && (
          <div
            id={errorId}
            className={`
              accessible-input-error
              flex items-center mt-1 text-sm text-red-600
              ${errorClassName}
            `}
            role="alert"
            aria-live="polite"
          >
            <AlertCircle className="h-4 w-4 mr-1 flex-shrink-0" />
            {error}
          </div>
        )}

        {helperText && !error && (
          <div
            id={helperTextId}
            className={`
              accessible-input-helper
              mt-1 text-sm text-gray-500
              ${helperTextClassName}
            `}
          >
            {helperText}
          </div>
        )}
      </div>
    );
  }
);

AccessibleInput.displayName = 'AccessibleInput';

/**
 * Accessible Select Component
 */
interface AccessibleSelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label: string;
  error?: string;
  helperText?: string;
  required?: boolean;
  options: Array<{ value: string; label: string; disabled?: boolean }>;
  placeholder?: string;
  containerClassName?: string;
  labelClassName?: string;
  selectClassName?: string;
}

export const AccessibleSelect = forwardRef<HTMLSelectElement, AccessibleSelectProps>(
  ({
    label,
    error,
    helperText,
    required = false,
    options,
    placeholder,
    containerClassName = '',
    labelClassName = '',
    selectClassName = '',
    id: providedId,
    ...props
  }, ref) => {
    const generatedId = useId();
    const id = providedId || generatedId;
    
    const inputContainerId = `${id}-container`;
    const errorId = `${id}-error`;
    const helperTextId = `${id}-helper`;
    const descriptionIds = [
      error ? errorId : null,
      helperText ? helperTextId : null,
    ].filter(Boolean).join(' ');

    const hasError = Boolean(error);

    return (
      <div 
        className={`accessible-select-container ${containerClassName}`}
        id={inputContainerId}
        data-required={required}
        data-error={hasError}
      >
        <label 
          htmlFor={id}
          className={`
            accessible-select-label
            block text-sm font-medium text-gray-700 mb-1
            ${labelClassName}
            ${required ? 'required-field' : ''}
            ${hasError ? 'error-text' : ''}
          `}
          aria-required={required}
        >
          {label}
          {required && (
            <span className="text-red-500 ml-1" aria-label="required">
              *
            </span>
          )}
        </label>

        <select
          ref={ref}
          id={id}
          className={`
            accessible-select
            block w-full rounded-md border-gray-300 shadow-sm
            focus:border-blue-500 focus:ring-blue-500
            disabled:bg-gray-50 disabled:text-gray-500
            ${hasError ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}
            ${selectClassName}
          `}
          aria-describedby={descriptionIds || undefined}
          aria-invalid={hasError}
          aria-required={required}
          {...props}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((option) => (
            <option 
              key={option.value} 
              value={option.value}
              disabled={option.disabled}
            >
              {option.label}
            </option>
          ))}
        </select>

        {error && (
          <div
            id={errorId}
            className="accessible-select-error flex items-center mt-1 text-sm text-red-600"
            role="alert"
            aria-live="polite"
          >
            <AlertCircle className="h-4 w-4 mr-1 flex-shrink-0" />
            {error}
          </div>
        )}

        {helperText && !error && (
          <div
            id={helperTextId}
            className="accessible-select-helper mt-1 text-sm text-gray-500"
          >
            {helperText}
          </div>
        )}
      </div>
    );
  }
);

AccessibleSelect.displayName = 'AccessibleSelect';

/**
 * Accessible Textarea Component
 */
interface AccessibleTextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label: string;
  error?: string;
  helperText?: string;
  required?: boolean;
  containerClassName?: string;
  labelClassName?: string;
  textareaClassName?: string;
}

export const AccessibleTextarea = forwardRef<HTMLTextAreaElement, AccessibleTextareaProps>(
  ({
    label,
    error,
    helperText,
    required = false,
    containerClassName = '',
    labelClassName = '',
    textareaClassName = '',
    id: providedId,
    ...props
  }, ref) => {
    const generatedId = useId();
    const id = providedId || generatedId;
    
    const inputContainerId = `${id}-container`;
    const errorId = `${id}-error`;
    const helperTextId = `${id}-helper`;
    const descriptionIds = [
      error ? errorId : null,
      helperText ? helperTextId : null,
    ].filter(Boolean).join(' ');

    const hasError = Boolean(error);

    return (
      <div 
        className={`accessible-textarea-container ${containerClassName}`}
        id={inputContainerId}
        data-required={required}
        data-error={hasError}
      >
        <label 
          htmlFor={id}
          className={`
            accessible-textarea-label
            block text-sm font-medium text-gray-700 mb-1
            ${labelClassName}
            ${required ? 'required-field' : ''}
            ${hasError ? 'error-text' : ''}
          `}
          aria-required={required}
        >
          {label}
          {required && (
            <span className="text-red-500 ml-1" aria-label="required">
              *
            </span>
          )}
        </label>

        <textarea
          ref={ref}
          id={id}
          className={`
            accessible-textarea
            block w-full rounded-md border-gray-300 shadow-sm
            focus:border-blue-500 focus:ring-blue-500
            disabled:bg-gray-50 disabled:text-gray-500
            ${hasError ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''}
            ${textareaClassName}
          `}
          aria-describedby={descriptionIds || undefined}
          aria-invalid={hasError}
          aria-required={required}
          {...props}
        />

        {error && (
          <div
            id={errorId}
            className="accessible-textarea-error flex items-center mt-1 text-sm text-red-600"
            role="alert"
            aria-live="polite"
          >
            <AlertCircle className="h-4 w-4 mr-1 flex-shrink-0" />
            {error}
          </div>
        )}

        {helperText && !error && (
          <div
            id={helperTextId}
            className="accessible-textarea-helper mt-1 text-sm text-gray-500"
          >
            {helperText}
          </div>
        )}
      </div>
    );
  }
);

AccessibleTextarea.displayName = 'AccessibleTextarea';
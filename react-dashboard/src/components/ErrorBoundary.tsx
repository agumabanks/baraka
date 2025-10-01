import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';
import Button from './ui/Button';
import Card from './ui/Card';

/**
 * Error boundary props
 */
interface ErrorBoundaryProps {
  /** Child components to wrap */
  children: ReactNode;
  /** Optional fallback component */
  fallback?: ReactNode;
  /** Callback when error occurs */
  onError?: (error: Error, errorInfo: ErrorInfo) => void;
}

/**
 * Error boundary state
 */
interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
}

/**
 * Error Boundary Component
 * Catches JavaScript errors anywhere in the child component tree
 * Displays a fallback UI with monochrome styling
 */
class ErrorBoundary extends Component<ErrorBoundaryProps, ErrorBoundaryState> {
  constructor(props: ErrorBoundaryProps) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  /**
   * Update state when error is caught
   */
  static getDerivedStateFromError(error: Error): Partial<ErrorBoundaryState> {
    return {
      hasError: true,
      error,
    };
  }

  /**
   * Log error details
   */
  componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    // Log error to console in development
    if (import.meta.env.DEV) {
      console.error('ErrorBoundary caught an error:', error, errorInfo);
    }

    // Update state with error info
    this.setState({
      errorInfo,
    });

    // Call optional error callback
    if (this.props.onError) {
      this.props.onError(error, errorInfo);
    }
  }

  /**
   * Reset error state
   */
  handleReset = (): void => {
    this.setState({
      hasError: false,
      error: null,
      errorInfo: null,
    });
  };

  /**
   * Reload the page
   */
  handleReload = (): void => {
    window.location.reload();
  };

  render(): ReactNode {
    const { hasError, error, errorInfo } = this.state;
    const { children, fallback } = this.props;

    // If there's an error, show fallback UI
    if (hasError) {
      // Use custom fallback if provided
      if (fallback) {
        return fallback;
      }

      // Default error UI with monochrome styling
      return (
        <div className="min-h-screen bg-mono-white flex items-center justify-center p-6">
          <div className="max-w-2xl w-full">
            <Card className="text-center">
              <div className="space-y-6">
                {/* Error Icon */}
                <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-mono-black text-mono-white">
                  <i className="fas fa-exclamation-triangle text-2xl" aria-hidden="true" />
                </div>

                {/* Error Title */}
                <div>
                  <h1 className="text-2xl font-bold text-mono-black mb-2">
                    Something went wrong
                  </h1>
                  <p className="text-mono-gray-600">
                    We're sorry, but something unexpected happened. Please try refreshing the page.
                  </p>
                </div>

                {/* Error Details (Development only) */}
                {import.meta.env.DEV && error && (
                  <div className="text-left bg-mono-gray-50 rounded-lg p-4 border border-mono-gray-200">
                    <div className="font-mono text-xs space-y-2">
                      <div>
                        <span className="font-semibold text-mono-gray-700">Error:</span>
                        <pre className="mt-1 text-mono-gray-900 overflow-x-auto whitespace-pre-wrap">
                          {error.toString()}
                        </pre>
                      </div>
                      {errorInfo && (
                        <div>
                          <span className="font-semibold text-mono-gray-700">Stack trace:</span>
                          <pre className="mt-1 text-mono-gray-600 overflow-x-auto whitespace-pre-wrap max-h-40 overflow-y-auto">
                            {errorInfo.componentStack}
                          </pre>
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Action Buttons */}
                <div className="flex items-center justify-center gap-4 pt-2">
                  <Button
                    variant="secondary"
                    size="md"
                    onClick={this.handleReset}
                  >
                    <i className="fas fa-redo mr-2" aria-hidden="true" />
                    Try Again
                  </Button>
                  <Button
                    variant="primary"
                    size="md"
                    onClick={this.handleReload}
                  >
                    <i className="fas fa-sync mr-2" aria-hidden="true" />
                    Reload Page
                  </Button>
                </div>

                {/* Contact Support Link */}
                <p className="text-sm text-mono-gray-500">
                  If the problem persists, please{' '}
                  <a
                    href="/support"
                    className="text-mono-black underline hover:text-mono-gray-700 transition-colors"
                  >
                    contact support
                  </a>
                  .
                </p>
              </div>
            </Card>
          </div>
        </div>
      );
    }

    // No error, render children normally
    return children;
  }
}

export default ErrorBoundary;
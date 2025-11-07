/**
 * Real-time Connection Status
 * WebSocket/SSE connection status indicator with controls
 */

import React from 'react';
import Card from '../../ui/Card';
import Button from '../../ui/Button';

interface RealTimeConnectionStatusProps {
  /** Whether WebSocket is connected */
  isConnected: boolean;
  /** Connection error message */
  connectionError: string | null;
  /** Retry connection handler */
  onRetry: () => void;
  /** Toggle connection handler */
  onToggle: () => void;
  /** Show detailed status */
  showDetails?: boolean;
  /** Compact mode for header */
  compact?: boolean;
}

/**
 * Real-time Connection Status Component
 * Displays WebSocket/SSE connection status with retry and toggle controls
 */
const RealTimeConnectionStatus: React.FC<RealTimeConnectionStatusProps> = ({
  isConnected,
  connectionError,
  onRetry,
  onToggle,
  showDetails = false,
  compact = false,
}) => {
  // Get status color and icon
  const getStatusConfig = () => {
    if (connectionError) {
      return {
        color: 'text-red-600 bg-red-50 border-red-200',
        icon: 'fas fa-exclamation-circle',
        text: 'Connection Error',
        dot: 'bg-red-500',
      };
    }
    
    if (isConnected) {
      return {
        color: 'text-green-600 bg-green-50 border-green-200',
        icon: 'fas fa-wifi',
        text: 'Connected',
        dot: 'bg-green-500',
      };
    }
    
    return {
      color: 'text-yellow-600 bg-yellow-50 border-yellow-200',
      icon: 'fas fa-wifi-slash',
      text: 'Disconnected',
      dot: 'bg-yellow-500',
    };
  };

  const config = getStatusConfig();

  // Compact version for header
  if (compact) {
    return (
      <div className="flex items-center gap-2">
        <div className="flex items-center gap-2">
          <div className={`w-2 h-2 rounded-full ${config.dot} ${isConnected ? 'animate-pulse' : ''}`} />
          <span className={`text-sm font-medium ${isConnected ? 'text-green-600' : 'text-mono-gray-600'}`}>
            {isConnected ? 'Live' : 'Manual'}
          </span>
        </div>
        <Button
          variant="outline"
          size="xs"
          onClick={onToggle}
          className="text-xs"
        >
          <i className={`fas ${isConnected ? 'fa-pause' : 'fa-play'} mr-1`} />
          {isConnected ? 'Pause' : 'Connect'}
        </Button>
      </div>
    );
  }

  return (
    <Card className={`p-4 border ${config.color}`}>
      <div className="space-y-3">
        {/* Header with status */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="relative">
              <div className={`w-3 h-3 rounded-full ${config.dot} ${isConnected ? 'animate-pulse' : ''}`} />
              {isConnected && (
                <div className="absolute inset-0 w-3 h-3 rounded-full bg-current opacity-20 animate-ping" />
              )}
            </div>
            <div>
              <h4 className="text-sm font-semibold">Real-time Connection</h4>
              <p className="text-xs text-mono-gray-600">{config.text}</p>
            </div>
          </div>
          <i className={`fas ${config.icon} text-lg`} aria-hidden="true" />
        </div>

        {/* Error message */}
        {connectionError && (
          <div className="bg-red-100 border border-red-200 rounded-lg p-3">
            <div className="flex items-start gap-2">
              <i className="fas fa-exclamation-triangle text-red-600 mt-0.5" aria-hidden="true" />
              <div>
                <p className="text-sm font-medium text-red-800">Connection Failed</p>
                <p className="text-xs text-red-700 mt-1">{connectionError}</p>
              </div>
            </div>
          </div>
        )}

        {/* Connection details */}
        {showDetails && isConnected && (
          <div className="space-y-2">
            <div className="text-xs text-mono-gray-600">
              <div className="flex justify-between">
                <span>Protocol:</span>
                <span className="font-medium">WebSocket</span>
              </div>
              <div className="flex justify-between">
                <span>Status:</span>
                <span className="font-medium text-green-600">Active</span>
              </div>
              <div className="flex justify-between">
                <span>Latency:</span>
                <span className="font-medium">~50ms</span>
              </div>
            </div>
          </div>
        )}

        {/* Controls */}
        <div className="flex gap-2">
          {connectionError ? (
            <Button
              variant="primary"
              size="sm"
              onClick={onRetry}
              className="flex-1"
            >
              <i className="fas fa-redo mr-2" aria-hidden="true" />
              Retry Connection
            </Button>
          ) : (
            <>
              <Button
                variant={isConnected ? "outline" : "primary"}
                size="sm"
                onClick={onToggle}
                className="flex-1"
              >
                <i className={`fas ${isConnected ? 'fa-pause' : 'fa-play'} mr-2`} aria-hidden="true" />
                {isConnected ? 'Disconnect' : 'Connect'}
              </Button>
              
              {isConnected && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={onRetry}
                  title="Reconnect"
                >
                  <i className="fas fa-sync-alt" aria-hidden="true" />
                </Button>
              )}
            </>
          )}
        </div>

        {/* Connection info */}
        <div className="text-xs text-mono-gray-500 space-y-1">
          <div className="flex items-center justify-between">
            <span>Data Stream:</span>
            <span className={isConnected ? 'text-green-600' : 'text-mono-gray-400'}>
              {isConnected ? 'Active' : 'Inactive'}
            </span>
          </div>
          <div className="flex items-center justify-between">
            <span>Auto-refresh:</span>
            <span className={isConnected ? 'text-green-600' : 'text-mono-gray-400'}>
              {isConnected ? 'Enabled' : 'Disabled'}
            </span>
          </div>
        </div>
      </div>
    </Card>
  );
};

export default RealTimeConnectionStatus;
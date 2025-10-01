import React from 'react';
import Card from '../ui/Card';

/**
 * Skeleton loading card for KPI cards
 * Displays a placeholder with pulse animation
 */
interface SkeletonCardProps {
  /** Additional CSS classes */
  className?: string;
}

const SkeletonCard: React.FC<SkeletonCardProps> = ({ className = '' }) => {
  return (
    <Card className={className}>
      <div className="animate-pulse space-y-4">
        {/* Icon placeholder */}
        <div className="w-12 h-12 bg-mono-gray-200 rounded-full" />
        
        {/* Title placeholder */}
        <div className="space-y-2">
          <div className="h-4 bg-mono-gray-200 rounded w-2/3" />
          <div className="h-6 bg-mono-gray-300 rounded w-1/2" />
        </div>
        
        {/* Subtitle placeholder */}
        <div className="h-3 bg-mono-gray-200 rounded w-1/2" />
        
        {/* Trend indicator placeholder */}
        <div className="flex items-center gap-2 pt-2">
          <div className="w-4 h-4 bg-mono-gray-200 rounded" />
          <div className="h-3 bg-mono-gray-200 rounded w-16" />
        </div>
      </div>
    </Card>
  );
};

/**
 * Skeleton loading for statement cards
 */
export const SkeletonStatementCard: React.FC<SkeletonCardProps> = ({ className = '' }) => {
  return (
    <Card className={className}>
      <div className="animate-pulse space-y-4">
        {/* Header */}
        <div className="h-5 bg-mono-gray-300 rounded w-1/2" />
        
        {/* Statement rows */}
        <div className="space-y-3">
          <div className="flex justify-between">
            <div className="h-4 bg-mono-gray-200 rounded w-20" />
            <div className="h-4 bg-mono-gray-300 rounded w-24" />
          </div>
          <div className="flex justify-between">
            <div className="h-4 bg-mono-gray-200 rounded w-20" />
            <div className="h-4 bg-mono-gray-300 rounded w-24" />
          </div>
          <hr className="border-mono-gray-200" />
          <div className="flex justify-between">
            <div className="h-5 bg-mono-gray-300 rounded w-20" />
            <div className="h-5 bg-mono-gray-400 rounded w-28" />
          </div>
        </div>
      </div>
    </Card>
  );
};

/**
 * Skeleton loading for workflow items
 */
export const SkeletonWorkflowItem: React.FC<SkeletonCardProps> = ({ className = '' }) => {
  return (
    <div className={`animate-pulse p-4 border border-mono-gray-200 rounded-lg ${className}`}>
      <div className="flex items-start justify-between gap-4">
        <div className="flex-1 space-y-2">
          <div className="h-4 bg-mono-gray-300 rounded w-3/4" />
          <div className="h-3 bg-mono-gray-200 rounded w-full" />
          <div className="flex items-center gap-2 mt-2">
            <div className="h-3 bg-mono-gray-200 rounded w-16" />
            <div className="h-3 bg-mono-gray-200 rounded w-20" />
          </div>
        </div>
        <div className="w-20 h-6 bg-mono-gray-200 rounded" />
      </div>
    </div>
  );
};

/**
 * Skeleton loading for charts
 */
export const SkeletonChart: React.FC<SkeletonCardProps> = ({ className = '' }) => {
  return (
    <Card className={className}>
      <div className="animate-pulse space-y-4">
        {/* Chart title */}
        <div className="h-5 bg-mono-gray-300 rounded w-1/3" />
        
        {/* Chart area */}
        <div className="h-64 bg-mono-gray-200 rounded flex items-end justify-around p-4 gap-2">
          <div className="bg-mono-gray-300 rounded-t w-full h-3/4" />
          <div className="bg-mono-gray-300 rounded-t w-full h-1/2" />
          <div className="bg-mono-gray-300 rounded-t w-full h-5/6" />
          <div className="bg-mono-gray-300 rounded-t w-full h-2/3" />
          <div className="bg-mono-gray-300 rounded-t w-full h-4/5" />
        </div>
      </div>
    </Card>
  );
};

export default SkeletonCard;
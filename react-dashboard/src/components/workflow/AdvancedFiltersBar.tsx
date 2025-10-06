import React, { useCallback } from 'react';
import Button from '../ui/Button';

export interface AdvancedFilters {
  priority: string;
  status: string;
  severity: string;
  search: string;
  dateFrom: string;
  dateTo: string;
  assignedTo: string;
  tags: string[];
}

interface AdvancedFiltersBarProps {
  filters: AdvancedFilters;
  onFilterChange: (filters: AdvancedFilters) => void;
  onClear: () => void;
  onExport: (format: 'csv' | 'excel') => void;
}

const AdvancedFiltersBar: React.FC<AdvancedFiltersBarProps> = React.memo(({
  filters,
  onFilterChange,
  onClear,
  onExport,
}) => {
  const handleChange = useCallback((key: keyof AdvancedFilters, value: string) => {
    onFilterChange({ ...filters, [key]: value });
  }, [filters, onFilterChange]);

  return (
    <div className="bg-white border border-mono-gray-200 rounded-lg p-4 space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
          Advanced Filters
        </h3>
        <div className="flex gap-2">
          <Button variant="secondary" size="sm" onClick={() => onExport('csv')}>
            <i className="fas fa-file-csv mr-2" aria-hidden="true" />
            Export CSV
          </Button>
          <Button variant="secondary" size="sm" onClick={() => onExport('excel')}>
            <i className="fas fa-file-excel mr-2" aria-hidden="true" />
            Export Excel
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Search */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            Search
          </label>
          <input
            type="text"
            placeholder="Search tracking number..."
            value={filters.search}
            onChange={(e) => handleChange('search', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          />
        </div>

        {/* Priority */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            Priority
          </label>
          <select
            value={filters.priority}
            onChange={(e) => handleChange('priority', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          >
            <option value="all">All Priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        {/* Status */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            Status
          </label>
          <select
            value={filters.status}
            onChange={(e) => handleChange('status', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          >
            <option value="all">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="delayed">Delayed</option>
          </select>
        </div>

        {/* Severity */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            Severity
          </label>
          <select
            value={filters.severity}
            onChange={(e) => handleChange('severity', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          >
            <option value="all">All Severities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>

        {/* Date From */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            From Date
          </label>
          <input
            type="date"
            value={filters.dateFrom}
            onChange={(e) => handleChange('dateFrom', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          />
        </div>

        {/* Date To */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            To Date
          </label>
          <input
            type="date"
            value={filters.dateTo}
            onChange={(e) => handleChange('dateTo', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          />
        </div>

        {/* Assigned To */}
        <div>
          <label className="block text-xs font-medium text-mono-gray-700 mb-1">
            Assigned To
          </label>
          <input
            type="text"
            placeholder="User name..."
            value={filters.assignedTo}
            onChange={(e) => handleChange('assignedTo', e.target.value)}
            className="w-full px-3 py-2 text-sm border border-mono-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-mono-black"
          />
        </div>

        {/* Clear Button */}
        <div className="flex items-end">
          <Button
            variant="secondary"
            size="sm"
            onClick={onClear}
            className="w-full"
          >
            <i className="fas fa-times mr-2" aria-hidden="true" />
            Clear All Filters
          </Button>
        </div>
      </div>
    </div>
  );
});

AdvancedFiltersBar.displayName = 'AdvancedFiltersBar';

export default AdvancedFiltersBar;

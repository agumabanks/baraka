import React, { useState, useMemo } from 'react';
import { ChevronUp, ChevronDown, Search, Filter } from 'lucide-react';

/**
 * Accessible Data Table Component
 * WCAG 2.1 AA compliant with comprehensive keyboard navigation and screen reader support
 */

interface Column<T> {
  key: keyof T | string;
  title: string;
  sortable?: boolean;
  filterable?: boolean;
  render?: (value: any, row: T) => React.ReactNode;
  width?: string;
  align?: 'left' | 'center' | 'right';
  className?: string;
}

interface AccessibleTableProps<T> {
  data: T[];
  columns: Column<T>[];
  title?: string;
  caption?: string;
  description?: string;
  loading?: boolean;
  emptyMessage?: string;
  selectable?: boolean;
  onSelectionChange?: (selectedRows: T[]) => void;
  onRowClick?: (row: T) => void;
  rowClassName?: string | ((row: T) => string);
  className?: string;
  containerClassName?: string;
  showSearch?: boolean;
  showFilters?: boolean;
  pageSize?: number;
  ariaLabel?: string;
}

export function AccessibleTable<T extends Record<string, any>>({
  data,
  columns,
  title,
  caption,
  description,
  loading = false,
  emptyMessage = 'No data available',
  selectable = false,
  onSelectionChange,
  onRowClick,
  rowClassName,
  className = '',
  containerClassName = '',
  showSearch = false,
  showFilters = false,
  pageSize = 10,
  ariaLabel,
}: AccessibleTableProps<T>) {
  const [sortColumn, setSortColumn] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRows, setSelectedRows] = useState<Set<number>>(new Set());
  const [currentPage, setCurrentPage] = useState(1);
  const [focusedCell, setFocusedCell] = useState<{ row: number; col: number } | null>(null);

  // Generate unique IDs for accessibility
  const tableId = useMemo(() => `table-${Math.random().toString(36).substr(2, 9)}`, []);
  const searchId = useMemo(() => `search-${Math.random().toString(36).substr(2, 9)}`, []);

  // Filter and search data
  const filteredData = useMemo(() => {
    let result = data;

    if (searchTerm) {
      result = result.filter(row =>
        columns.some(col => {
          const value = row[col.key as keyof T];
          return String(value || '').toLowerCase().includes(searchTerm.toLowerCase());
        })
      );
    }

    return result;
  }, [data, searchTerm, columns]);

  // Sort data
  const sortedData = useMemo(() => {
    if (!sortColumn) return filteredData;

    return [...filteredData].sort((a, b) => {
      const aVal = a[sortColumn as keyof T];
      const bVal = b[sortColumn as keyof T];

      if (aVal === bVal) return 0;

      const comparison = aVal < bVal ? -1 : 1;
      return sortDirection === 'asc' ? comparison : -comparison;
    });
  }, [filteredData, sortColumn, sortDirection]);

  // Paginate data
  const paginatedData = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return sortedData.slice(start, start + pageSize);
  }, [sortedData, currentPage, pageSize]);

  const totalPages = Math.ceil(sortedData.length / pageSize);

  // Handle sorting
  const handleSort = (columnKey: string) => {
    if (sortColumn === columnKey) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortColumn(columnKey);
      setSortDirection('asc');
    }
  };

  // Handle row selection
  const handleRowSelection = (rowIndex: number, checked: boolean) => {
    const newSelected = new Set(selectedRows);
    if (checked) {
      newSelected.add(rowIndex);
    } else {
      newSelected.delete(rowIndex);
    }
    setSelectedRows(newSelected);
    
    if (onSelectionChange) {
      const selectedItems = Array.from(newSelected).map(index => sortedData[index]).filter(Boolean);
      onSelectionChange(selectedItems);
    }
  };

  // Handle select all
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      const allIndices = new Set(paginatedData.map((_, index) => index));
      setSelectedRows(allIndices);
      if (onSelectionChange) {
        onSelectionChange(paginatedData);
      }
    } else {
      setSelectedRows(new Set());
      if (onSelectionChange) {
        onSelectionChange([]);
      }
    }
  };

  // Keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent, rowIndex: number, colIndex: number) => {
    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        setFocusedCell({ row: Math.min(rowIndex + 1, paginatedData.length - 1), col: colIndex });
        break;
      case 'ArrowUp':
        e.preventDefault();
        setFocusedCell({ row: Math.max(rowIndex - 1, 0), col: colIndex });
        break;
      case 'ArrowRight':
        e.preventDefault();
        setFocusedCell({ row: rowIndex, col: Math.min(colIndex + 1, columns.length - 1) });
        break;
      case 'ArrowLeft':
        e.preventDefault();
        setFocusedCell({ row: rowIndex, col: Math.max(colIndex - 1, 0) });
        break;
      case 'Enter':
      case ' ':
        e.preventDefault();
        if (onRowClick && colIndex === 0) { // First column or row
          onRowClick(paginatedData[rowIndex]);
        }
        break;
    }
  };

  if (loading) {
    return (
      <div className={`accessible-table-loading ${containerClassName}`}>
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-1/4 mb-4"></div>
          <div className="space-y-2">
            {Array.from({ length: 5 }).map((_, i) => (
              <div key={i} className="h-4 bg-gray-200 rounded"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  const allSelected = paginatedData.length > 0 && selectedRows.size === paginatedData.length;
  const someSelected = selectedRows.size > 0 && selectedRows.size < paginatedData.length;

  return (
    <div className={`accessible-table-wrapper ${containerClassName}`}>
      {/* Table Header */}
      <div className="table-header mb-4">
        {title && (
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            {title}
          </h3>
        )}
        
        {description && (
          <p className="text-sm text-gray-600 mb-3">
            {description}
          </p>
        )}

        {/* Search and Filter Controls */}
        <div className="flex flex-col sm:flex-row gap-3 mb-3">
          {showSearch && (
            <div className="relative flex-1 max-w-sm">
              <label htmlFor={searchId} className="sr-only">
                Search table
              </label>
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                id={searchId}
                type="text"
                placeholder="Search..."
                className="pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          )}
          
          {showFilters && (
            <button
              className="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              aria-label="Open filters"
            >
              <Filter className="h-4 w-4" />
              Filters
            </button>
          )}
        </div>
      </div>

      {/* Table Container */}
      <div className="table-container overflow-x-auto">
        <table
          className={`accessible-table min-w-full divide-y divide-gray-200 ${className}`}
          id={tableId}
          role="table"
          aria-label={ariaLabel || title}
        >
          <caption className="sr-only">
            {caption || title || 'Data table'}
          </caption>
          
          <thead className="bg-gray-50">
            <tr role="row">
              {selectable && (
                <th scope="col" className="px-6 py-3 text-left">
                  <label className="flex items-center">
                    <input
                      type="checkbox"
                      className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      checked={allSelected}
                      aria-describedby={`${tableId}-selection-status`}
                      onChange={(e) => handleSelectAll(e.target.checked)}
                    />
                    <span className="ml-2 text-sm text-gray-700">Select All</span>
                  </label>
                  <div id={`${tableId}-selection-status`} className="sr-only">
                    {someSelected 
                      ? `${selectedRows.size} of ${paginatedData.length} items selected`
                      : allSelected 
                        ? 'All items selected'
                        : 'No items selected'
                    }
                  </div>
                </th>
              )}
              
              {columns.map((column, index) => (
                <th
                  key={column.key as string}
                  scope="col"
                  className={`
                    px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider
                    ${column.sortable ? 'cursor-pointer hover:bg-gray-100' : ''}
                    ${column.className || ''}
                  `}
                  style={{ width: column.width }}
                  aria-sort={
                    sortColumn === column.key
                      ? sortDirection === 'asc'
                        ? 'ascending'
                        : 'descending'
                      : undefined
                  }
                  onClick={() => column.sortable && handleSort(column.key as string)}
                  tabIndex={column.sortable ? 0 : undefined}
                  onKeyDown={(e) => {
                    if (column.sortable && (e.key === 'Enter' || e.key === ' ')) {
                      e.preventDefault();
                      handleSort(column.key as string);
                    }
                  }}
                >
                  <div className="flex items-center gap-1">
                    <span>{column.title}</span>
                    {column.sortable && sortColumn === column.key && (
                      sortDirection === 'asc' 
                        ? <ChevronUp className="h-4 w-4" aria-hidden="true" />
                        : <ChevronDown className="h-4 w-4" aria-hidden="true" />
                    )}
                  </div>
                </th>
              ))}
            </tr>
          </thead>
          
          <tbody className="bg-white divide-y divide-gray-200">
            {paginatedData.length === 0 ? (
              <tr>
                <td
                  colSpan={columns.length + (selectable ? 1 : 0)}
                  className="px-6 py-12 text-center text-gray-500"
                  role="row"
                >
                  {emptyMessage}
                </td>
              </tr>
            ) : (
              paginatedData.map((row, rowIndex) => {
                const actualRowIndex = (currentPage - 1) * pageSize + rowIndex;
                const isSelected = selectedRows.has(actualRowIndex);
                const isFocused = focusedCell?.row === rowIndex;
                
                return (
                  <tr
                    key={actualRowIndex}
                    className={`
                      accessible-table-row
                      ${onRowClick ? 'cursor-pointer hover:bg-gray-50' : ''}
                      ${isSelected ? 'bg-blue-50' : ''}
                      ${isFocused ? 'ring-2 ring-blue-500' : ''}
                      ${typeof rowClassName === 'function' ? rowClassName(row) : rowClassName || ''}
                    `}
                    role="row"
                    onClick={() => onRowClick?.(row)}
                    tabIndex={onRowClick ? 0 : undefined}
                    onKeyDown={(e) => handleKeyDown(e, rowIndex, 0)}
                    data-row-index={actualRowIndex}
                  >
                    {selectable && (
                      <td className="px-6 py-4 whitespace-nowrap">
                        <input
                          type="checkbox"
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          checked={isSelected}
                          onChange={(e) => handleRowSelection(actualRowIndex, e.target.checked)}
                          onClick={(e) => e.stopPropagation()}
                        />
                      </td>
                    )}
                    
                    {columns.map((column, colIndex) => {
                      const value = row[column.key as keyof T];
                      const cellContent = column.render ? column.render(value, row) : value;
                      
                      return (
                        <td
                          key={column.key as string}
                          className={`
                            px-6 py-4 whitespace-nowrap text-sm
                            ${column.align === 'center' ? 'text-center' : ''}
                            ${column.align === 'right' ? 'text-right' : ''}
                            ${isFocused && colIndex === 0 ? 'ring-2 ring-blue-500' : ''}
                          `}
                          role="cell"
                          tabIndex={colIndex === 0 ? 0 : undefined}
                          onKeyDown={(e) => handleKeyDown(e, rowIndex, colIndex)}
                        >
                          {cellContent}
                        </td>
                      );
                    })}
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {totalPages > 1 && (
        <div className="flex items-center justify-between mt-4 px-6 py-3 bg-white border-t border-gray-200">
          <div className="flex-1 flex justify-between sm:hidden">
            <button
              onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
              disabled={currentPage === 1}
              className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
              disabled={currentPage === totalPages}
              className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          
          <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p className="text-sm text-gray-700">
                Showing{' '}
                <span className="font-medium">{(currentPage - 1) * pageSize + 1}</span>
                {' '}to{' '}
                <span className="font-medium">
                  {Math.min(currentPage * pageSize, sortedData.length)}
                </span>
                {' '}of{' '}
                <span className="font-medium">{sortedData.length}</span>
                {' '}results
              </p>
            </div>
            
            <div>
              <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                  disabled={currentPage === 1}
                  className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  aria-label="Previous page"
                >
                  Previous
                </button>
                
                {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                  <button
                    key={page}
                    onClick={() => setCurrentPage(page)}
                    className={`
                      relative inline-flex items-center px-4 py-2 border text-sm font-medium
                      ${page === currentPage
                        ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      }
                    `}
                    aria-current={page === currentPage ? 'page' : undefined}
                  >
                    {page}
                  </button>
                ))}
                
                <button
                  onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                  disabled={currentPage === totalPages}
                  className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                  aria-label="Next page"
                >
                  Next
                </button>
              </nav>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
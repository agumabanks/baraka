/**
 * Export utility functions for exporting data to CSV and Excel
 */

export const exportToCSV = (data: any[], filename: string = 'export.csv') => {
  if (!data || data.length === 0) {
    console.warn('No data to export');
    return;
  }

  // Get headers from first object
  const headers = Object.keys(data[0]);
  
  // Create CSV content
  const csvContent = [
    // Header row
    headers.join(','),
    // Data rows
    ...data.map(row =>
      headers.map(header => {
        const value = row[header];
        // Handle values that might contain commas or quotes
        if (value === null || value === undefined) return '';
        const stringValue = String(value);
        if (stringValue.includes(',') || stringValue.includes('"') || stringValue.includes('\n')) {
          return `"${stringValue.replace(/"/g, '""')}"`;
        }
        return stringValue;
      }).join(',')
    )
  ].join('\n');

  // Create blob and download
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

export const exportToExcel = (data: any[], filename: string = 'export.xlsx') => {
  // For Excel export, we'll use CSV format with .xlsx extension
  // In production, you'd want to use a library like xlsx or exceljs
  if (!data || data.length === 0) {
    console.warn('No data to export');
    return;
  }

  // Create Excel-compatible CSV with UTF-8 BOM
  const headers = Object.keys(data[0]);
  const BOM = '\uFEFF';
  
  const csvContent = BOM + [
    headers.join('\t'),
    ...data.map(row =>
      headers.map(header => {
        const value = row[header];
        if (value === null || value === undefined) return '';
        return String(value);
      }).join('\t')
    )
  ].join('\n');

  const blob = new Blob([csvContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

export const prepareWorkflowDataForExport = (items: any[]) => {
  return items.map(item => ({
    'Tracking Number': item.tracking_number || item.id || 'N/A',
    'Title': item.title || 'N/A',
    'Description': item.description || 'N/A',
    'Priority': item.priority || 'N/A',
    'Status': item.status || 'N/A',
    'Origin Branch': item.origin_branch || 'N/A',
    'Destination Branch': item.destination_branch || 'N/A',
    'Promised At': item.promised_at || 'N/A',
    'Created At': item.created_at || 'N/A',
    'Updated At': item.updated_at || 'N/A',
  }));
};

export const prepareExceptionsForExport = (exceptions: any[]) => {
  return exceptions.map(exception => ({
    'Tracking Number': exception.tracking_number || 'N/A',
    'Exception Type': exception.exception_type || 'N/A',
    'Severity': exception.exception_severity || 'N/A',
    'Branch': exception.branch || 'N/A',
    'Description': exception.description || 'N/A',
    'Created At': exception.created_at || 'N/A',
    'Updated At': exception.updated_at || 'N/A',
    'Resolved': exception.resolved ? 'Yes' : 'No',
  }));
};

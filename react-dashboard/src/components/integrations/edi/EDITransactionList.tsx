import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Badge } from '../../ui/Badge';
import { Spinner } from '../../ui/Spinner';
import { Input } from '../../ui/Input';
import { EDiTransaction, EDITransactionFilters, EDIDocumentType, EDITradingPartner } from '../../../types/edi';

interface EDITransactionListProps {
  transactions: EDiTransaction[];
  loading: boolean;
  pagination?: any;
  filters: EDITransactionFilters;
  documentTypes: EDIDocumentType[];
  tradingPartners: EDITradingPartner[];
  onFilterChange: (filters: Partial<EDITransactionFilters>) => void;
  onViewDetails: (transaction: EDiTransaction) => void;
  onRefresh: () => void;
}

export const EDITransactionList: React.FC<EDITransactionListProps> = ({
  transactions,
  loading,
  pagination,
  filters,
  documentTypes,
  tradingPartners,
  onFilterChange,
  onViewDetails,
  onRefresh,
}) => {
  const [localFilters, setLocalFilters] = useState(filters);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge variant="default">Pending</Badge>;
      case 'processing':
        return <Badge variant="warning">Processing</Badge>;
      case 'completed':
        return <Badge variant="success">Completed</Badge>;
      case 'failed':
        return <Badge variant="destructive">Failed</Badge>;
      case 'acknowledged':
        return <Badge variant="success">Acknowledged</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  const getDocumentTypeLabel = (code: string) => {
    const docType = documentTypes.find(dt => dt.code === code);
    return docType ? `${code} - ${docType.name}` : code;
  };

  const getTradingPartnerName = (id?: string) => {
    if (!id) return 'N/A';
    const partner = tradingPartners.find(p => p.id === id);
    return partner ? partner.name : id;
  };

  const applyFilters = () => {
    onFilterChange(localFilters);
  };

  const clearFilters = () => {
    const clearedFilters: EDITransactionFilters = { page: 1, per_page: 20 };
    setLocalFilters(clearedFilters);
    onFilterChange(clearedFilters);
  };

  if (loading) {
    return (
      <Card className="p-8">
        <div className="flex justify-center">
          <Spinner size="lg" />
        </div>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Filters */}
      <Card className="p-6">
        <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Filters</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">
              Document Type
            </label>
            <select
              value={localFilters.transaction_type || ''}
              onChange={(e) => setLocalFilters(prev => ({ 
                ...prev, 
                transaction_type: e.target.value as any || undefined 
              }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="">All Types</option>
              {documentTypes.map((type) => (
                <option key={type.code} value={type.code}>
                  {type.code} - {type.name}
                </option>
              ))}
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">
              Status
            </label>
            <select
              value={localFilters.status || ''}
              onChange={(e) => setLocalFilters(prev => ({ 
                ...prev, 
                status: e.target.value as any || undefined 
              }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="processing">Processing</option>
              <option value="completed">Completed</option>
              <option value="failed">Failed</option>
              <option value="acknowledged">Acknowledged</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">
              Trading Partner
            </label>
            <select
              value={localFilters.trading_partner_id || ''}
              onChange={(e) => setLocalFilters(prev => ({ 
                ...prev, 
                trading_partner_id: e.target.value || undefined 
              }))}
              className="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
            >
              <option value="">All Partners</option>
              {tradingPartners.map((partner) => (
                <option key={partner.id} value={partner.id}>
                  {partner.name}
                </option>
              ))}
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">
              Control Number
            </label>
            <Input
              value={localFilters.control_number || ''}
              onChange={(e) => setLocalFilters(prev => ({ 
                ...prev, 
                control_number: e.target.value || undefined 
              }))}
              placeholder="Search control number..."
              className="w-full"
            />
          </div>
        </div>
        
        <div className="flex gap-2 mt-4">
          <Button onClick={applyFilters} className="bg-blue-600 hover:bg-blue-700 text-white">
            Apply Filters
          </Button>
          <Button onClick={clearFilters} variant="outline">
            Clear
          </Button>
          <Button onClick={onRefresh} variant="outline">
            Refresh
          </Button>
        </div>
      </Card>

      {/* Transactions List */}
      <div className="space-y-4">
        {transactions.length === 0 ? (
          <Card className="p-8 text-center">
            <p className="text-mono-gray-600">No EDI transactions found.</p>
          </Card>
        ) : (
          transactions.map((transaction) => (
            <Card key={transaction.id} className="p-6 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-3 mb-2">
                    <h3 className="text-lg font-semibold text-mono-gray-900">
                      {getDocumentTypeLabel(transaction.transaction_type)}
                    </h3>
                    {getStatusBadge(transaction.status)}
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                      <p className="text-mono-gray-600">Transaction ID</p>
                      <p className="font-mono text-xs text-mono-gray-900 truncate">
                        {transaction.id}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Control Number</p>
                      <p className="font-mono text-xs text-mono-gray-900">
                        {transaction.control_number}
                      </p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Trading Partner</p>
                      <p className="text-mono-gray-900">{getTradingPartnerName(transaction.trading_partner_id)}</p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Created</p>
                      <p className="text-mono-gray-900">{formatDate(transaction.created_at)}</p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm mt-3">
                    <div>
                      <p className="text-mono-gray-600">Sender</p>
                      <p className="text-mono-gray-900">{transaction.sender}</p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Receiver</p>
                      <p className="text-mono-gray-900">{transaction.receiver}</p>
                    </div>
                    <div>
                      <p className="text-mono-gray-600">Version</p>
                      <p className="text-mono-gray-900">{transaction.version}</p>
                    </div>
                    {transaction.processing_completed_at && (
                      <div>
                        <p className="text-mono-gray-600">Completed</p>
                        <p className="text-mono-gray-900">{formatDate(transaction.processing_completed_at)}</p>
                      </div>
                    )}
                  </div>

                  {transaction.error_message && (
                    <div className="mt-3 p-3 bg-red-50 border border-red-200 rounded">
                      <p className="text-sm font-medium text-red-800">Error</p>
                      <p className="text-sm text-red-700 mt-1">{transaction.error_message}</p>
                    </div>
                  )}

                  {transaction.acknowledgment_id && (
                    <div className="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                      <p className="text-sm font-medium text-green-800">
                        Acknowledgment Received
                      </p>
                      <p className="text-sm text-green-700 mt-1">
                        ID: {transaction.acknowledgment_id}
                      </p>
                    </div>
                  )}
                </div>

                <div className="flex flex-col gap-2 ml-4">
                  <Button
                    onClick={() => onViewDetails(transaction)}
                    variant="outline"
                    size="sm"
                  >
                    View Details
                  </Button>
                </div>
              </div>
            </Card>
          ))
        )}
      </div>

      {/* Pagination */}
      {pagination && pagination.last_page > 1 && (
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div className="text-sm text-mono-gray-600">
              Showing {((pagination.current_page - 1) * pagination.per_page) + 1} to{' '}
              {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of{' '}
              {pagination.total} results
            </div>
            <div className="flex gap-2">
              <Button
                onClick={() => onFilterChange({ page: pagination.current_page - 1 })}
                disabled={pagination.current_page === 1}
                variant="outline"
                size="sm"
              >
                Previous
              </Button>
              <span className="px-3 py-2 text-sm text-mono-gray-900">
                Page {pagination.current_page} of {pagination.last_page}
              </span>
              <Button
                onClick={() => onFilterChange({ page: pagination.current_page + 1 })}
                disabled={pagination.current_page === pagination.last_page}
                variant="outline"
                size="sm"
              >
                Next
              </Button>
            </div>
          </div>
        </Card>
      )}
    </div>
  );
};
import React, { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';
import { Spinner } from '../../components/ui/Spinner';
import { Input } from '../../components/ui/Input';
import { ediApi } from '../../services/api';
import { EDiTransaction, EDITransactionFilters, EDITransactionSummary } from '../../types/edi';
import { EDITransactionList } from '../../components/integrations/edi/EDITransactionList';
import { EDITransactionDetails } from '../../components/integrations/edi/EDITransactionDetails';
import { EDISubmissionModal } from '../../components/integrations/edi/EDISubmissionModal';
import { EDIPerformanceMetricsPanel } from '../../components/integrations/edi/EDIPerformanceMetrics';
import { EDIBatchSubmission } from '../../components/integrations/edi/EDIBatchSubmission';
import { toast } from '../../stores/toastStore';

type TabType = 'overview' | 'transactions' | 'submissions' | 'performance' | 'partners';

export const EDITransactionDashboard: React.FC = () => {
  const [activeTab, setActiveTab] = useState<TabType>('overview');
  const [filters, setFilters] = useState<EDITransactionFilters>({
    page: 1,
    per_page: 20,
  });
  const [selectedTransaction, setSelectedTransaction] = useState<EDiTransaction | null>(null);
  const [showDetails, setShowDetails] = useState(false);
  const [showSubmissionModal, setShowSubmissionModal] = useState(false);
  const [showBatchSubmission, setShowBatchSubmission] = useState(false);
  const queryClient = useQueryClient();

  // Queries
  const { data: transactionsResponse, isLoading: transactionsLoading } = useQuery({
    queryKey: ['edi', 'transactions', filters],
    queryFn: () => ediApi.getTransactions(filters),
  });

  const { data: tradingPartnersResponse, isLoading: partnersLoading } = useQuery({
    queryKey: ['edi', 'trading-partners'],
    queryFn: ediApi.getTradingPartners,
  });

  const { data: documentTypesResponse, isLoading: documentTypesLoading } = useQuery({
    queryKey: ['edi', 'document-types'],
    queryFn: ediApi.getDocumentTypes,
  });

  const { data: submissionHistoryResponse, isLoading: submissionHistoryLoading } = useQuery({
    queryKey: ['edi', 'submission-history'],
    queryFn: ediApi.getSubmissionHistory,
  });

  const { data: metricsResponse, isLoading: metricsLoading } = useQuery({
    queryKey: ['edi', 'metrics'],
    queryFn: ediApi.getPerformanceMetrics,
  });

  // Mutation for submitting transactions
  const submitTransactionMutation = useMutation({
    mutationFn: ({
      document_type,
      payload,
      trading_partner,
    }: {
      document_type: string;
      payload: any;
      trading_partner?: string;
    }) => ediApi.submitTransaction(document_type, payload, trading_partner),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['edi', 'transactions'] });
      queryClient.invalidateQueries({ queryKey: ['edi', 'submission-history'] });
      setShowSubmissionModal(false);
      toast.success({
        title: 'Transaction Submitted',
        description: 'EDI transaction has been submitted successfully.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Submission Failed',
        description: error instanceof Error ? error.message : 'Failed to submit EDI transaction.',
      });
    },
  });

  // Mutation for batch submission
  const batchSubmissionMutation = useMutation({
    mutationFn: ({ file, metadata }: { file: File; metadata: any }) =>
      ediApi.submitBatch(file, metadata),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['edi', 'transactions'] });
      queryClient.invalidateQueries({ queryKey: ['edi', 'submission-history'] });
      setShowBatchSubmission(false);
      toast.success({
        title: 'Batch Submitted',
        description: 'EDI batch submission has been processed successfully.',
      });
    },
    onError: (error: unknown) => {
      toast.error({
        title: 'Batch Submission Failed',
        description: error instanceof Error ? error.message : 'Failed to submit EDI batch.',
      });
    },
  });

  const handleTransactionSubmit = (documentType: string, payload: any, tradingPartner?: string) => {
    submitTransactionMutation.mutate({
      document_type: documentType,
      payload,
      trading_partner: tradingPartner,
    });
  };

  const handleBatchSubmit = (file: File, metadata: any) => {
    batchSubmissionMutation.mutate({ file, metadata });
  };

  const handleViewDetails = (transaction: EDiTransaction) => {
    setSelectedTransaction(transaction);
    setShowDetails(true);
  };

  const handleFilterChange = (newFilters: Partial<EDITransactionFilters>) => {
    setFilters(prev => ({ ...prev, ...newFilters, page: 1 }));
  };

  const transactions = transactionsResponse?.data?.data || [];
  const pagination = transactionsResponse?.data?.pagination;
  const summary = transactionsResponse?.data?.summary;
  const tradingPartners = tradingPartnersResponse?.data || [];
  const documentTypes = documentTypesResponse?.data || [];
  const submissionHistory = submissionHistoryResponse?.data || [];
  const metrics = metricsResponse?.data;

  const tabs = [
    { id: 'overview', label: 'Overview', icon: 'LayoutDashboard' },
    { id: 'transactions', label: 'Transactions', icon: 'FileText' },
    { id: 'submissions', label: 'Submissions', icon: 'Upload' },
    { id: 'performance', label: 'Performance', icon: 'TrendingUp' },
    { id: 'partners', label: 'Trading Partners', icon: 'Users' },
  ] as const;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-mono-gray-900">EDI Transaction Management</h1>
          <p className="text-mono-gray-600 mt-1">
            Monitor EDI transactions, manage submissions, and track performance
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            onClick={() => setShowSubmissionModal(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white"
          >
            Submit Transaction
          </Button>
          <Button
            onClick={() => setShowBatchSubmission(true)}
            variant="outline"
          >
            Batch Submission
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-blue-600">{summary?.total || 0}</p>
            <p className="text-sm text-mono-gray-600">Total</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-yellow-600">{summary?.pending || 0}</p>
            <p className="text-sm text-mono-gray-600">Pending</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-blue-600">{summary?.processing || 0}</p>
            <p className="text-sm text-mono-gray-600">Processing</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-green-600">{summary?.completed || 0}</p>
            <p className="text-sm text-mono-gray-600">Completed</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-red-600">{summary?.failed || 0}</p>
            <p className="text-sm text-mono-gray-600">Failed</p>
          </div>
        </Card>
        <Card className="p-4">
          <div className="text-center">
            <p className="text-2xl font-bold text-purple-600">{summary?.acknowledged || 0}</p>
            <p className="text-sm text-mono-gray-600">Acknowledged</p>
          </div>
        </Card>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <div className="flex items-center gap-2">
                {tab.icon === 'LayoutDashboard' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                )}
                {tab.icon === 'FileText' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                )}
                {tab.icon === 'Upload' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                  </svg>
                )}
                {tab.icon === 'TrendingUp' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                )}
                {tab.icon === 'Users' && (
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                  </svg>
                )}
                {tab.label}
              </div>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      <div className="min-h-[600px]">
        {activeTab === 'overview' && (
          <div className="space-y-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <EDIPerformanceMetricsPanel 
                data={metrics} 
                loading={metricsLoading} 
              />
              <div className="space-y-4">
                <h3 className="text-lg font-semibold text-mono-gray-900">Recent Activity</h3>
                <Card className="p-6">
                  {submissionHistoryLoading ? (
                    <div className="flex justify-center py-8">
                      <Spinner size="md" />
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {submissionHistory.slice(0, 5).map((submission: any) => (
                        <div key={submission.id} className="flex items-center justify-between p-3 bg-gray-50 rounded">
                          <div className="flex items-center gap-3">
                            <div className={`w-2 h-2 rounded-full ${
                              submission.status === 'completed' ? 'bg-green-500' : 
                              submission.status === 'failed' ? 'bg-red-500' : 'bg-yellow-500'
                            }`} />
                            <div>
                              <p className="text-sm font-medium text-mono-gray-900">{submission.file_name}</p>
                              <p className="text-xs text-mono-gray-600">{submission.submitted_at}</p>
                            </div>
                          </div>
                          <Badge 
                            variant={submission.status === 'completed' ? 'success' : 
                                   submission.status === 'failed' ? 'destructive' : 'default'}
                          >
                            {submission.status}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </Card>
              </div>
            </div>
          </div>
        )}

        {activeTab === 'transactions' && (
          <EDITransactionList
            transactions={transactions}
            loading={transactionsLoading}
            pagination={pagination}
            filters={filters}
            documentTypes={documentTypes}
            tradingPartners={tradingPartners}
            onFilterChange={handleFilterChange}
            onViewDetails={handleViewDetails}
            onRefresh={() => queryClient.invalidateQueries({ queryKey: ['edi', 'transactions'] })}
          />
        )}

        {activeTab === 'submissions' && (
          <div className="space-y-6">
            <Card className="p-6">
              <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Submission History</h3>
              {submissionHistoryLoading ? (
                <div className="flex justify-center py-8">
                  <Spinner size="md" />
                </div>
              ) : (
                <div className="space-y-4">
                  {submissionHistory.map((submission: any) => (
                    <div key={submission.id} className="border rounded p-4">
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="font-medium text-mono-gray-900">{submission.file_name}</h4>
                          <p className="text-sm text-mono-gray-600">
                            {submission.record_count} records • {submission.file_size} bytes
                          </p>
                        </div>
                        <Badge 
                          variant={submission.status === 'completed' ? 'success' : 
                                 submission.status === 'failed' ? 'destructive' : 'default'}
                        >
                          {submission.status}
                        </Badge>
                      </div>
                      <div className="mt-2 text-sm text-mono-gray-600">
                        Submitted: {submission.submitted_at} by {submission.submitted_by}
                      </div>
                      {submission.error_message && (
                        <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                          {submission.error_message}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </Card>
          </div>
        )}

        {activeTab === 'performance' && (
          <EDIPerformanceMetricsPanel 
            data={metrics} 
            loading={metricsLoading}
            detailed
          />
        )}

        {activeTab === 'partners' && (
          <div className="space-y-6">
            <Card className="p-6">
              <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Trading Partners</h3>
              {partnersLoading ? (
                <div className="flex justify-center py-8">
                  <Spinner size="md" />
                </div>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {tradingPartners.map((partner: any) => (
                    <div key={partner.id} className="border rounded p-4">
                      <div className="flex items-center justify-between mb-2">
                        <h4 className="font-medium text-mono-gray-900">{partner.name}</h4>
                        <Badge 
                          variant={partner.connection_status === 'connected' ? 'success' : 
                                 partner.connection_status === 'error' ? 'destructive' : 'default'}
                        >
                          {partner.connection_status}
                        </Badge>
                      </div>
                      <div className="space-y-1 text-sm text-mono-gray-600">
                        <p>ISA ID: {partner.isa_id}</p>
                        <p>GS ID: {partner.gs_id}</p>
                        <p>Type: {partner.connection_type.toUpperCase()}</p>
                        {partner.last_connected_at && (
                          <p>Last Connected: {new Date(partner.last_connected_at).toLocaleDateString()}</p>
                        )}
                      </div>
                      {partner.error_message && (
                        <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                          {partner.error_message}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </Card>
          </div>
        )}
      </div>

      {/* Modals */}
      {showSubmissionModal && (
        <EDISubmissionModal
          documentTypes={documentTypes}
          tradingPartners={tradingPartners}
          onClose={() => setShowSubmissionModal(false)}
          onSubmit={handleTransactionSubmit}
          loading={submitTransactionMutation.isPending}
        />
      )}

      {showBatchSubmission && (
        <EDIBatchSubmission
          onClose={() => setShowBatchSubmission(false)}
          onSubmit={handleBatchSubmit}
          loading={batchSubmissionMutation.isPending}
        />
      )}

      {showDetails && selectedTransaction && (
        <EDITransactionDetails
          transaction={selectedTransaction}
          onClose={() => {
            setShowDetails(false);
            setSelectedTransaction(null);
          }}
        />
      )}
    </div>
  );
};
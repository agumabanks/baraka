import React from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Badge } from '../../ui/Badge';
import { EDiTransaction, EDiTransactionDetail, EDIAcknowledgment } from '../../../types/edi';

interface EDITransactionDetailsProps {
  transaction: EDiTransaction | EDiTransactionDetail;
  onClose: () => void;
}

export const EDITransactionDetails: React.FC<EDITransactionDetailsProps> = ({
  transaction,
  onClose,
}) => {
  const detailData = transaction as EDiTransactionDetail;
  const processingLogs = detailData.processing_logs ?? [];
  const parsedSegments = detailData.parsed_segments ?? [];
  const rawPayload = detailData.raw_payload ?? transaction.payload;

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
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

  const getAcknowledgmentBadge = (status: string) => {
    switch (status) {
      case 'pending':
        return <Badge variant="default">Pending</Badge>;
      case 'generated':
        return <Badge variant="warning">Generated</Badge>;
      case 'sent':
        return <Badge variant="info">Sent</Badge>;
      case 'accepted':
        return <Badge variant="success">Accepted</Badge>;
      case 'rejected':
        return <Badge variant="destructive">Rejected</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold text-mono-gray-900">EDI Transaction Details</h2>
            <Button
              onClick={onClose}
              variant="ghost"
              size="sm"
            >
              ×
            </Button>
          </div>

          <div className="space-y-6">
            {/* Basic Information */}
            <Card className="p-6">
              <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Basic Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Transaction ID</label>
                  <p className="text-sm text-mono-gray-900 font-mono">{transaction.id}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Document Type</label>
                  <p className="text-sm text-mono-gray-900">{transaction.document_type}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Status</label>
                  <div className="mt-1">{getStatusBadge(transaction.status)}</div>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Control Number</label>
                  <p className="text-sm text-mono-gray-900 font-mono">{transaction.control_number}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Version</label>
                  <p className="text-sm text-mono-gray-900">{transaction.version}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Transaction Set</label>
                  <p className="text-sm text-mono-gray-900">{transaction.transaction_set}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Sender</label>
                  <p className="text-sm text-mono-gray-900">{transaction.sender}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Receiver</label>
                  <p className="text-sm text-mono-gray-900">{transaction.receiver}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Created</label>
                  <p className="text-sm text-mono-gray-900">{formatDate(transaction.created_at)}</p>
                </div>
                {transaction.processing_started_at && (
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Processing Started</label>
                    <p className="text-sm text-mono-gray-900">{formatDate(transaction.processing_started_at)}</p>
                  </div>
                )}
                {transaction.processing_completed_at && (
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Processing Completed</label>
                    <p className="text-sm text-mono-gray-900">{formatDate(transaction.processing_completed_at)}</p>
                  </div>
                )}
                <div>
                  <label className="text-sm font-medium text-mono-gray-600">Last Updated</label>
                  <p className="text-sm text-mono-gray-900">{formatDate(transaction.updated_at)}</p>
                </div>
              </div>
            </Card>

            {/* Acknowledgment Information */}
            {transaction.acknowledgment && (
              <Card className="p-6">
                <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Acknowledgment</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Acknowledgment ID</label>
                    <p className="text-sm text-mono-gray-900 font-mono">{transaction.acknowledgment.id}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Type</label>
                    <p className="text-sm text-mono-gray-900">{transaction.acknowledgment.acknowledgment_type}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Status</label>
                    <div className="mt-1">{getAcknowledgmentBadge(transaction.acknowledgment.status)}</div>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Functional Group Control</label>
                    <p className="text-sm text-mono-gray-900 font-mono">{transaction.acknowledgment.functional_group_control_number}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Control Number</label>
                    <p className="text-sm text-mono-gray-900 font-mono">{transaction.acknowledgment.control_number}</p>
                  </div>
                  <div>
                    <label className="text-sm font-medium text-mono-gray-600">Created</label>
                    <p className="text-sm text-mono-gray-900">{formatDate(transaction.acknowledgment.created_at)}</p>
                  </div>
                  {transaction.acknowledgment.sent_at && (
                    <div>
                      <label className="text-sm font-medium text-mono-gray-600">Sent</label>
                      <p className="text-sm text-mono-gray-900">{formatDate(transaction.acknowledgment.sent_at)}</p>
                    </div>
                  )}
                </div>
                {(transaction.acknowledgment.error_code || transaction.acknowledgment.error_description) && (
                  <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded">
                    <h4 className="text-sm font-medium text-red-800 mb-2">Acknowledgment Error</h4>
                    {transaction.acknowledgment.error_code && (
                      <p className="text-sm text-red-700">
                        <strong>Code:</strong> {transaction.acknowledgment.error_code}
                      </p>
                    )}
                    {transaction.acknowledgment.error_description && (
                      <p className="text-sm text-red-700 mt-1">
                        <strong>Description:</strong> {transaction.acknowledgment.error_description}
                      </p>
                    )}
                  </div>
                )}
              </Card>
            )}

            {/* Payload */}
            <Card className="p-6">
              <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Payload</h3>
              <div className="bg-gray-50 p-4 rounded-lg">
                <pre className="text-sm text-mono-gray-900 whitespace-pre-wrap overflow-x-auto">
                  {typeof rawPayload === 'string'
                    ? rawPayload
                    : JSON.stringify(rawPayload ?? transaction.payload, null, 2)}
                </pre>
              </div>
            </Card>

            {/* Error Information */}
            {transaction.error_message && (
              <Card className="p-6">
                <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Error Information</h3>
                <div className="bg-red-50 border border-red-200 p-4 rounded-lg">
                  <p className="text-sm text-red-800">{transaction.error_message}</p>
                </div>
              </Card>
            )}

            {/* Processing Logs */}
            {processingLogs.length > 0 && (
              <Card className="p-6">
                <h3 className="text-lg font-semibold text-mono-gray-900 mb-4">Processing Logs</h3>
                <div className="space-y-3">
                  {processingLogs.map((log) => (
                    <div
                      key={log.id}
                      className={`p-3 rounded border ${
                        log.level === 'error' 
                          ? 'bg-red-50 border-red-200' 
                          : log.level === 'warning'
                          ? 'bg-yellow-50 border-yellow-200'
                          : 'bg-gray-50 border-gray-200'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <Badge 
                            variant={log.level === 'error' ? 'destructive' : 
                                   log.level === 'warning' ? 'warning' : 'default'}
                            className="text-xs"
                          >
                            {log.level.toUpperCase()}
                          </Badge>
                          <span className="text-sm font-mono text-mono-gray-600">
                            {formatDate(log.created_at)}
                          </span>
                        </div>
                        {log.segment_position && (
                          <span className="text-xs text-mono-gray-500">
                            Position: {log.segment_position}
                          </span>
                        )}
                      </div>
                      <p className="text-sm text-mono-gray-900 mt-1">{log.message}</p>
                      {log.code && (
                        <p className="text-xs text-mono-gray-600 mt-1">
                          Code: {log.code}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              </Card>
            )}
          </div>

          <div className="flex justify-end mt-6">
            <Button
              onClick={onClose}
              variant="outline"
            >
              Close
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};
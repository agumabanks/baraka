import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Input } from '../../ui/Input';
import type { EDIDocumentType, EDITradingPartner } from '../../../types/edi';

interface EDISubmissionModalProps {
  documentTypes: EDIDocumentType[];
  tradingPartners?: EDITradingPartner[];
  onSubmit: (documentType: string, payload: Record<string, unknown>, tradingPartner?: string) => void;
  onClose: () => void;
  loading?: boolean;
}

export const EDISubmissionModal: React.FC<EDISubmissionModalProps> = ({
  documentTypes,
  tradingPartners = [],
  onSubmit,
  onClose,
  loading = false,
}) => {
  const [documentType, setDocumentType] = useState(documentTypes[0]?.code ?? '');
  const [payload, setPayload] = useState('');
  const [partner, setPartner] = useState(tradingPartners[0]?.id ?? '');

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    try {
      const parsed = payload.trim() ? JSON.parse(payload) : {};
      onSubmit(documentType, parsed, partner || undefined);
    } catch (error) {
      alert('Payload must be valid JSON.');
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <Card className="w-full max-w-2xl p-6">
        <h2 className="text-xl font-semibold text-mono-gray-900">Submit EDI Transaction</h2>
        <p className="text-sm text-mono-gray-600 mt-1">
          Provide the document type and JSON payload to submit an EDI transaction.
        </p>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">Document Type</label>
            <select
              value={documentType}
              onChange={(event) => setDocumentType(event.target.value)}
              className="w-full rounded-md border border-mono-gray-300 px-3 py-2 text-sm"
            >
              {documentTypes.map((type) => (
                <option key={type.code} value={type.code}>
                  {type.code} - {type.name}
                </option>
              ))}
            </select>
          </div>

          {tradingPartners.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-mono-gray-700 mb-1">Trading Partner</label>
              <select
                value={partner}
                onChange={(event) => setPartner(event.target.value)}
                className="w-full rounded-md border border-mono-gray-300 px-3 py-2 text-sm"
              >
                {tradingPartners.map((tp) => (
                  <option key={tp.id} value={tp.id}>
                    {tp.name} ({tp.connection_type.toUpperCase()})
                  </option>
                ))}
              </select>
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">JSON Payload</label>
            <textarea
              value={payload}
              onChange={(event) => setPayload(event.target.value)}
              className="w-full min-h-[200px] rounded-md border border-mono-gray-300 px-3 py-2 font-mono text-sm"
              placeholder={`{
  "tracking_number": "..."
}`}
            />
            <p className="mt-1 text-xs text-mono-gray-500">
              Paste a JSON payload that matches the selected EDI document type.
            </p>
          </div>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white" disabled={loading}>
              {loading ? 'Submitting…' : 'Submit Transaction'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default EDISubmissionModal;

import React, { useState } from 'react';
import { Card } from '../../ui/Card';
import { Button } from '../../ui/Button';
import { Input } from '../../ui/Input';

interface EDIBatchSubmissionProps {
  onSubmit: (file: File, metadata: Record<string, unknown>) => void;
  onClose: () => void;
  loading?: boolean;
}

export const EDIBatchSubmission: React.FC<EDIBatchSubmissionProps> = ({ onSubmit, onClose, loading = false }) => {
  const [file, setFile] = useState<File | null>(null);
  const [description, setDescription] = useState('');

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (!file) {
      alert('Select a file to upload.');
      return;
    }

    onSubmit(file, {
      description,
      uploaded_at: new Date().toISOString(),
    });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <Card className="w-full max-w-xl p-6">
        <h2 className="text-xl font-semibold text-mono-gray-900">Submit EDI Batch</h2>
        <p className="text-sm text-mono-gray-600 mt-1">
          Upload a batch file for processing. Supported formats include JSON and CSV.
        </p>

        <form onSubmit={handleSubmit} className="mt-4 space-y-4">
          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">Batch File</label>
            <input
              type="file"
              accept=".json,.csv,.txt"
              onChange={(event) => setFile(event.target.files?.[0] ?? null)}
              className="w-full text-sm"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-mono-gray-700 mb-1">Description (optional)</label>
            <Input
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              placeholder="Morning batch upload"
            />
          </div>

          <div className="flex justify-end gap-3">
            <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" className="bg-blue-600 hover:bg-blue-700 text-white" disabled={loading || !file}>
              {loading ? 'Uploading…' : 'Submit Batch'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default EDIBatchSubmission;

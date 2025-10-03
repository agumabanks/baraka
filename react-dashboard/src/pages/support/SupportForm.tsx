import React, { useEffect, useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router-dom';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import Select from '../../components/ui/Select';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { supportApi } from '../../services/api';
import type { Department, SupportFormData, SupportPriority } from '../../types/support';

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
];

const SupportForm: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const isEditMode = !!id;

  const [formData, setFormData] = useState<SupportFormData>({
    department_id: '',
    service: '',
    priority: 'medium' as SupportPriority,
    subject: '',
    description: '',
    attached_file: null,
  });
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [departments, setDepartments] = useState<Department[]>([]);

  // Fetch departments for create mode
  const { data: createMetaData, isLoading: isLoadingCreateMeta } = useQuery({
    queryKey: ['support', 'create-meta'],
    queryFn: async () => {
      const response = await supportApi.getCreateMeta();
      return response.data;
    },
    enabled: !isEditMode,
  });

  // Fetch support data for edit mode
  const { data: editMetaData, isLoading: isLoadingEditMeta } = useQuery({
    queryKey: ['support', 'edit-meta', id],
    queryFn: async () => {
      if (!id) throw new Error('Support ID is required');
      const response = await supportApi.getEditMeta(Number(id));
      return response.data;
    },
    enabled: isEditMode,
  });

  // Set departments based on mode
  useEffect(() => {
    if (createMetaData?.departments) {
      setDepartments(createMetaData.departments);
    } else if (editMetaData?.departments) {
      setDepartments(editMetaData.departments);
    }
  }, [createMetaData, editMetaData]);

  // Populate form for edit mode
  useEffect(() => {
    if (isEditMode && editMetaData?.support) {
      const support = editMetaData.support;
      // Find the department ID from the departments list
      const dept = editMetaData.departments.find((d) => d.title === support.department);

      setFormData({
        department_id: dept ? String(dept.id) : '',
        service: support.service ?? '',
        priority: support.priority,
        subject: support.subject,
        description: support.description,
        attached_file: null,
      });
    }
  }, [isEditMode, editMetaData]);

  const createMutation = useMutation({
    mutationFn: async (payload: SupportFormData) => {
      const response = await supportApi.createSupport(payload);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support', 'tickets'] });
      window.alert('Support ticket created successfully.');
      navigate('/dashboard/support');
    },
    onError: (error: Error) => {
      console.error('Failed to create support ticket', error);
      window.alert('Failed to create support ticket. Please try again.');
    },
  });

  const updateMutation = useMutation({
    mutationFn: async (payload: SupportFormData) => {
      if (!id) throw new Error('Support ID is required');
      const response = await supportApi.updateSupport(Number(id), payload);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['support', 'tickets'] });
      queryClient.invalidateQueries({ queryKey: ['support', 'ticket', id] });
      window.alert('Support ticket updated successfully.');
      navigate(`/dashboard/support/${id}`);
    },
    onError: (error: Error) => {
      console.error('Failed to update support ticket', error);
      window.alert('Failed to update support ticket. Please try again.');
    },
  });

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();

    if (!formData.department_id || !formData.service.trim() || !formData.subject.trim() || !formData.description.trim()) {
      window.alert('Please fill in all required fields.');
      return;
    }

    const payload: SupportFormData = {
      ...formData,
      service: formData.service.trim(),
      subject: formData.subject.trim(),
      description: formData.description.trim(),
      attached_file: selectedFile,
    };

    if (isEditMode) {
      updateMutation.mutate(payload);
    } else {
      createMutation.mutate(payload);
    }
  };

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] || null;
    setSelectedFile(file);
  };

  const handleChange = (field: keyof SupportFormData, value: string) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }));
  };

  const isLoading = isLoadingCreateMeta || isLoadingEditMeta;
  const isSaving = createMutation.isPending || updateMutation.isPending;

  if (isLoading) {
    return (
      <div className="flex h-full items-center justify-center">
        <LoadingSpinner message={isEditMode ? 'Loading support ticket' : 'Loading form'} />
      </div>
    );
  }

  const departmentOptions = departments.map((dept) => ({
    value: String(dept.id),
    label: dept.title,
  }));

  return (
    <div className="space-y-6">
      <header className="space-y-3">
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate('/dashboard/support')}
            className="uppercase tracking-[0.2em]"
          >
            ← Back
          </Button>
        </div>
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
          Support Ticket
        </p>
        <div className="space-y-3">
          <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
            {isEditMode ? 'Edit Support Ticket' : 'Create New Support Ticket'}
          </h1>
          <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
            {isEditMode
              ? 'Update the support ticket details below.'
              : 'Fill in the details below to create a new support ticket.'}
          </p>
        </div>
      </header>

      <Card className="border border-mono-gray-200">
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid gap-6 md:grid-cols-2">
            <Select
              label="Department *"
              value={formData.department_id}
              onChange={(e) => handleChange('department_id', e.target.value)}
              options={[
                { value: '', label: 'Select department' },
                ...departmentOptions,
              ]}
              required
            />

            <Select
              label="Priority *"
              value={formData.priority}
              onChange={(e) => handleChange('priority', e.target.value as SupportPriority)}
              options={priorityOptions}
              required
            />
          </div>

          <Input
            label="Service *"
            value={formData.service}
            onChange={(e) => handleChange('service', e.target.value)}
            placeholder="e.g., Delivery, Pickup, Tracking"
            required
          />

          <Input
            label="Subject *"
            value={formData.subject}
            onChange={(e) => handleChange('subject', e.target.value)}
            placeholder="Brief description of the issue"
            required
          />

          <div>
            <label htmlFor="description" className="mb-2 block text-sm font-medium text-mono-gray-700">
              Description *
            </label>
            <textarea
              id="description"
              rows={6}
              className="w-full rounded-lg border border-mono-gray-300 px-4 py-2 text-sm focus:border-mono-black focus:outline-none focus:ring-1 focus:ring-mono-black"
              placeholder="Detailed description of the issue..."
              value={formData.description}
              onChange={(e) => handleChange('description', e.target.value)}
              required
            />
          </div>

          <div>
            <label htmlFor="attached_file" className="mb-2 block text-sm font-medium text-mono-gray-700">
              Attachment (Optional)
            </label>
            <input
              id="attached_file"
              type="file"
              className="w-full text-sm text-mono-gray-700 file:mr-4 file:rounded-lg file:border-0 file:bg-mono-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-mono-gray-700 hover:file:bg-mono-gray-200"
              onChange={handleFileChange}
            />
            {selectedFile && (
              <p className="mt-2 text-xs text-mono-gray-600">
                Selected: {selectedFile.name}
              </p>
            )}
          </div>

          <div className="flex justify-end gap-3 border-t border-mono-gray-200 pt-6">
            <Button
              type="button"
              variant="ghost"
              onClick={() => navigate('/dashboard/support')}
              disabled={isSaving}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              disabled={isSaving}
              className="uppercase tracking-[0.25em]"
            >
              {isSaving ? 'Saving...' : isEditMode ? 'Update Ticket' : 'Create Ticket'}
            </Button>
          </div>
        </form>
      </Card>
    </div>
  );
};

export default SupportForm;

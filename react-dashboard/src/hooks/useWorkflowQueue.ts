import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { workflowQueueApi } from '../services/api';
import type { WorkflowItem } from '../types/dashboard';

/**
 * Hook to fetch workflow queue items for the dashboard widget
 * Fetches from /dashboard/workflow-queue endpoint with real-time updates
 */
export const useWorkflowQueue = () => {
  return useQuery<WorkflowItem[], Error>({
    queryKey: ['workflow-queue'],
    queryFn: async () => {
      const response = await workflowQueueApi.getQueue();
      return response.data;
    },
    staleTime: 30 * 1000, // 30 seconds
    refetchInterval: 30 * 1000, // Auto-refresh every 30 seconds
    refetchOnWindowFocus: true,
  });
};

/**
 * Hook to create a new workflow item
 */
export const useCreateWorkflowItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<WorkflowItem>) => {
      const response = await workflowQueueApi.create(data);
      return response.data;
    },
    onSuccess: () => {
      // Invalidate both workflow-queue and workflow-board queries
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to update an existing workflow item
 */
export const useUpdateWorkflowItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: { id: string; data: Partial<WorkflowItem> }) => {
      const response = await workflowQueueApi.update(id, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to delete a workflow item
 */
export const useDeleteWorkflowItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string) => {
      await workflowQueueApi.delete(id);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to update workflow item status
 */
export const useUpdateWorkflowStatus = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, status }: { id: string; status: 'pending' | 'in_progress' | 'completed' | 'delayed' }) => {
      const response = await workflowQueueApi.updateStatus(id, status);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to assign workflow item to a user
 */
export const useAssignWorkflowItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, assignedTo }: { id: string; assignedTo: string }) => {
      const response = await workflowQueueApi.assign(id, assignedTo);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to bulk update workflow items
 */
export const useBulkUpdateWorkflowItems = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ ids, data }: { ids: string[]; data: Record<string, any> }) => {
      const response = await workflowQueueApi.bulkUpdate(ids, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

/**
 * Hook to bulk delete workflow items
 */
export const useBulkDeleteWorkflowItems = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (ids: string[]) => {
      await workflowQueueApi.bulkDelete(ids);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['workflow-queue'] });
      queryClient.invalidateQueries({ queryKey: ['workflow-board'] });
    },
  });
};

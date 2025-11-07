import { useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { workflowQueueApi } from '../services/api';
import type { WorkflowItem, WorkflowStatus } from '../types/dashboard';
import useWorkflowStore, { type WorkflowState, type WorkflowSummary } from '../stores/workflowStore';

type WorkflowTasksQueryResult = {
  tasks: WorkflowItem[];
  summary: WorkflowSummary;
  meta?: Record<string, unknown>;
};

/**
 * Transform camelCase workflow data to snake_case for API
 */
const transformWorkflowData = (data: Partial<WorkflowItem>): Record<string, unknown> => {
  const payload: Record<string, unknown> = {};

  for (const [key, value] of Object.entries(data)) {
    // Handle assigned_to (can be null, undefined, or a string ID)
    if (key === 'assignedTo') {
      payload.assigned_to = value && value !== '' ? value : null;
      continue;
    }

    // Handle tracking_number
    if (key === 'trackingNumber') {
      payload.tracking_number = value;
      continue;
    }

    // Handle due_at
    if (key === 'dueDate') {
      payload.due_at = value;
      continue;
    }

    // Keep other fields as-is
    payload[key] = value;
  }

  return payload;
};

/**
 * Hook to fetch workflow queue items for the dashboard widget
 * Fetches from /dashboard/workflow-queue endpoint with real-time updates
 */
export const useWorkflowQueue = () => {
  const setQueue = useWorkflowStore((state: WorkflowState) => state.setQueue);
  const setSyncing = useWorkflowStore((state: WorkflowState) => state.setSyncing);

  const query = useQuery<WorkflowTasksQueryResult, Error>({
    queryKey: ['workflow-queue'],
    queryFn: async () => {
      const response = await workflowQueueApi.getQueue();
      if (!response?.success) {
        throw new Error(response?.message ?? 'Failed to load workflow tasks');
      }

      const payload = response.data ?? {};
      const tasks = Array.isArray(payload.tasks) ? (payload.tasks as WorkflowItem[]) : [];
      const summary = normaliseSummary(payload.summary, tasks);

      return {
        tasks,
        summary,
        meta: payload.meta ?? {},
      };
    },
    staleTime: 30 * 1000, // 30 seconds
    refetchInterval: 30 * 1000, // Auto-refresh every 30 seconds
    refetchOnWindowFocus: true,
  });

  useEffect(() => {
    if (query.data) {
      setQueue(query.data.tasks, query.data.summary);
      setSyncing(false);
    }
  }, [query.data, setQueue, setSyncing]);

  useEffect(() => {
    setSyncing(query.isFetching);
  }, [query.isFetching, setSyncing]);

  useEffect(() => {
    if (query.isError) {
      setSyncing(false);
    }
  }, [query.isError, setSyncing]);

  return query;
};

const normaliseSummary = (rawSummary: unknown, tasks: WorkflowItem[]): WorkflowSummary => {
  const summary: WorkflowSummary = {
    total: tasks.length,
    pending: 0,
    in_progress: 0,
    testing: 0,
    awaiting_feedback: 0,
    delayed: 0,
    completed: 0,
  };

  if (rawSummary && typeof rawSummary === 'object') {
    const summaryRecord = rawSummary as Record<string, unknown>;
    for (const key of Object.keys(summary)) {
      const value = summaryRecord[key];
      if (typeof value === 'number') {
        summary[key as keyof WorkflowSummary] = value;
      }
    }
  } else {
    tasks.forEach((item) => {
      const status = item.status ?? 'pending';
      if (summary[status as keyof WorkflowSummary] !== undefined) {
        summary[status as keyof WorkflowSummary] += 1;
      }
    });
  }

  return summary;
};

/**
 * Hook to create a new workflow item
 */
export const useCreateWorkflowItem = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<WorkflowItem>) => {
      const payload = transformWorkflowData(data);
      const response = await workflowQueueApi.create(payload);
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
      const payload = transformWorkflowData(data);
      const response = await workflowQueueApi.update(id, payload);
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
    mutationFn: async ({ id, status }: { id: string; status: WorkflowStatus }) => {
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

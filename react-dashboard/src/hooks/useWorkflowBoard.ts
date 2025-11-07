import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { workflowApi, operationsApi } from '../services/api';
import type { WorkflowBoardResponse } from '../types/workflow';
import useWorkflowStore, { type WorkflowState } from '../stores/workflowStore';

const STALE_TIME = 30 * 1000;

export const useWorkflowBoard = () => {
  const setBoard = useWorkflowStore((state: WorkflowState) => state.setBoard);
  const setSyncing = useWorkflowStore((state: WorkflowState) => state.setSyncing);

  const query = useQuery<WorkflowBoardResponse, Error>({
    queryKey: ['workflow-board'],
    queryFn: async () => {
      try {
        const response = await workflowApi.getBoard();
        if (!response.success) {
          throw new Error(response.message || 'Failed to load workflow board');
        }
        return response.data;
      } catch (error: any) {
        // Handle authentication errors
        if (error.response?.status === 401 || error.response?.status === 403) {
          throw new Error('Authentication required. Please log in again.');
        }
        // Handle API errors
        if (error.response?.data?.message) {
          throw new Error(error.response.data.message);
        }
        // Handle network errors
        if (error.message === 'Network Error') {
          throw new Error('Unable to connect to the server. Please check your connection.');
        }
        // Default error
        throw new Error(error.message || 'Failed to load workflow board');
      }
    },
    staleTime: STALE_TIME,
    refetchInterval: STALE_TIME,
    retry: 1, // Only retry once to fail faster
  });

  useEffect(() => {
    setSyncing(query.isFetching);
  }, [query.isFetching, setSyncing]);

  useEffect(() => {
    if (query.data) {
      setBoard(query.data.queues?.unassigned_shipments ?? []);
      setSyncing(false);
    }
  }, [query.data, setBoard, setSyncing]);

  useEffect(() => {
    if (query.isError) {
      setSyncing(false);
    }
  }, [query.isError, setSyncing]);

  return query;
};

export const useOperationsInsights = () =>
  useQuery<{
    dispatch: Record<string, unknown>;
    exceptionMetrics: Record<string, unknown> | null;
    alerts: Array<Record<string, unknown>> | null;
    shipmentMetrics: Record<string, unknown> | null;
    workerUtilization: Record<string, unknown> | null;
  }, Error>({
    queryKey: ['operations-insights'],
    queryFn: async () => {
      const [dispatch, exceptionMetrics, alerts, shipmentMetrics, workerUtilization] = await Promise.allSettled([
        operationsApi.getDispatchBoard(),
        operationsApi.getExceptionMetrics(),
        operationsApi.getAlerts(),
        operationsApi.getShipmentMetrics(),
        operationsApi.getWorkerUtilization(),
      ]);

      if (dispatch.status !== 'fulfilled' || !dispatch.value.success) {
        throw new Error('Unable to load dispatch board');
      }

      return {
        dispatch: dispatch.value.data,
        exceptionMetrics:
          exceptionMetrics.status === 'fulfilled' && exceptionMetrics.value.success
            ? exceptionMetrics.value.data
            : null,
        alerts:
          alerts.status === 'fulfilled' && alerts.value.success
            ? alerts.value.data
            : null,
        shipmentMetrics:
          shipmentMetrics.status === 'fulfilled' && shipmentMetrics.value.success
            ? shipmentMetrics.value.data
            : null,
        workerUtilization:
          workerUtilization.status === 'fulfilled' && workerUtilization.value.success
            ? workerUtilization.value.data
            : null,
      };
    },
    staleTime: 60 * 1000,
    refetchInterval: 60 * 1000,
  });

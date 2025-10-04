import { useQuery } from '@tanstack/react-query';
import { workflowApi, operationsApi } from '../services/api';
import type { WorkflowBoardResponse } from '../types/workflow';

const STALE_TIME = 30 * 1000;

export const useWorkflowBoard = () =>
  useQuery<WorkflowBoardResponse, Error>({
    queryKey: ['workflow-board'],
    queryFn: async () => {
      const response = await workflowApi.getBoard();
      if (!response.success) {
        throw new Error('Failed to load workflow board');
      }
      return response.data;
    },
    staleTime: STALE_TIME,
    refetchInterval: STALE_TIME,
  });

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

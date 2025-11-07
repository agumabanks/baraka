import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { driverRostersApi, driverTimeLogsApi, driversApi } from '../services/api';
import type {
  DriverDetailResponse,
  DriverFormPayload,
  DriverListParams,
  DriverListResponse,
  DriverRosterFormPayload,
  DriverRosterListParams,
  DriverRosterRecord,
  DriverTimeLogFormPayload,
  DriverTimeLogListParams,
  DriverTimeLogRecord,
} from '../types/drivers';

const STALE_TIME = 60 * 1000;

export const useDriverList = (params?: DriverListParams) =>
  useQuery<DriverListResponse, Error>({
    queryKey: ['drivers', params],
    queryFn: async () => {
      const response = await driversApi.getDrivers(params);
      return response.data;
    },
    staleTime: STALE_TIME,
    placeholderData: (previousData) => previousData,
  });

export const useDriverDetail = (driverId: number | string | null, enabled = true) =>
  useQuery<DriverDetailResponse, Error>({
    queryKey: ['driver', driverId],
    queryFn: async () => {
      if (!driverId) {
        throw new Error('Driver identifier is required');
      }
      const response = await driversApi.getDriver(driverId);
      return response.data;
    },
    enabled: Boolean(driverId) && enabled,
    staleTime: STALE_TIME,
  });

export const useDriverMutations = () => {
  const queryClient = useQueryClient();

  const createDriver = useMutation({
    mutationFn: (payload: DriverFormPayload) => driversApi.createDriver(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['drivers'] });
    },
  });

  const updateDriver = useMutation({
    mutationFn: ({ driverId, payload }: { driverId: number | string; payload: DriverFormPayload }) =>
      driversApi.updateDriver(driverId, payload),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['drivers'] });
      queryClient.invalidateQueries({ queryKey: ['driver', variables.driverId] });
    },
  });

  const toggleStatus = useMutation({
    mutationFn: ({ driverId, status }: { driverId: number | string; status: DriverDetailResponse['status'] }) =>
      driversApi.toggleStatus(driverId, status),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['drivers'] });
      queryClient.invalidateQueries({ queryKey: ['driver', variables.driverId] });
    },
  });

  return {
    createDriver,
    updateDriver,
    toggleStatus,
  };
};

export const useDriverRosterList = (params?: DriverRosterListParams, enabled = true) =>
  useQuery<DriverRosterRecord[], Error>({
    queryKey: ['driver-rosters', params],
    queryFn: async () => {
      const response = await driverRostersApi.getRosters(params);
      return response.data.data;
    },
    enabled,
    staleTime: STALE_TIME,
    placeholderData: (previousData) => previousData,
  });

export const useDriverTimeLogs = (params?: DriverTimeLogListParams, enabled = true) =>
  useQuery<DriverTimeLogRecord[], Error>({
    queryKey: ['driver-time-logs', params],
    queryFn: async () => {
      const response = await driverTimeLogsApi.getLogs(params);
      return response.data.data;
    },
    enabled,
    staleTime: 30 * 1000,
    placeholderData: (previousData) => previousData,
  });

export const useDriverRosterMutations = () => {
  const queryClient = useQueryClient();

  const createRoster = useMutation({
    mutationFn: (payload: DriverRosterFormPayload) => driverRostersApi.createRoster(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['driver-rosters'] });
    },
  });

  const updateRoster = useMutation({
    mutationFn: ({ rosterId, payload }: { rosterId: number | string; payload: Partial<DriverRosterFormPayload> }) =>
      driverRostersApi.updateRoster(rosterId, payload),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['driver-rosters'] });
      if (variables.payload.driver_id) {
        queryClient.invalidateQueries({ queryKey: ['driver', variables.payload.driver_id] });
      }
    },
  });

  const deleteRoster = useMutation({
    mutationFn: (rosterId: number | string) => driverRostersApi.deleteRoster(rosterId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['driver-rosters'] });
    },
  });

  return {
    createRoster,
    updateRoster,
    deleteRoster,
  };
};

export const useDriverTimeLogMutations = () => {
  const queryClient = useQueryClient();

  const createLog = useMutation({
    mutationFn: (payload: DriverTimeLogFormPayload) => driverTimeLogsApi.createLog(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['driver-time-logs'] });
    },
  });

  return {
    createLog,
  };
};

import { useQuery } from '@tanstack/react-query';
import { branchesApi } from '../services/api';
import type {
  BranchDetailResponse,
  BranchHierarchyResponse,
  BranchListParams,
  BranchListResponse,
} from '../types/branches';

const STALE_TIME = 60 * 1000; // 60 seconds

export const useBranchList = (params?: BranchListParams) =>
  useQuery<BranchListResponse, Error>({
    queryKey: ['branches', params],
    queryFn: async () => {
      const response = await branchesApi.getBranches(params);
      return response.data;
    },
    staleTime: STALE_TIME,
    placeholderData: (previousData) => previousData,
  });

export const useBranchDetail = (
  branchId: number | string | null,
  options?: { enabled?: boolean }
) =>
  useQuery<BranchDetailResponse, Error>({
    queryKey: ['branch', branchId],
    queryFn: async () => {
      if (!branchId) {
        return Promise.reject(new Error('Branch identifier is required'));
      }
      const response = await branchesApi.getBranch(branchId);
      return response.data;
    },
    enabled: Boolean(branchId) && (options?.enabled ?? true),
    staleTime: STALE_TIME,
  });

export const useBranchHierarchy = () =>
  useQuery<BranchHierarchyResponse, Error>({
    queryKey: ['branch-hierarchy'],
    queryFn: async () => {
      const response = await branchesApi.getHierarchy();
      return response.data;
    },
    staleTime: STALE_TIME,
  });

import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { branchesApi } from '../services/api';
import type {
  BranchDetailResponse,
  BranchFormPayload,
  BranchHierarchyResponse,
  BranchListParams,
  BranchListResponse,
  BranchStatusValue,
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

export const useBranchMutations = () => {
  const queryClient = useQueryClient();

  const createBranch = useMutation({
    mutationFn: (payload: BranchFormPayload) => branchesApi.createBranch(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['branches'] });
      queryClient.invalidateQueries({ queryKey: ['branch-hierarchy'] });
    },
  });

  const updateBranch = useMutation({
    mutationFn: ({ branchId, payload }: { branchId: number | string; payload: Partial<BranchFormPayload> }) =>
      branchesApi.updateBranch(branchId, payload),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['branches'] });
      queryClient.invalidateQueries({ queryKey: ['branch', variables.branchId] });
    },
  });

  const toggleBranchStatus = useMutation({
    mutationFn: ({ branchId, status }: { branchId: number | string; status: BranchStatusValue }) =>
      branchesApi.toggleStatus(branchId, status),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['branches'] });
      queryClient.invalidateQueries({ queryKey: ['branch', variables.branchId] });
    },
  });

  return {
    createBranch,
    updateBranch,
    toggleBranchStatus,
  };
};

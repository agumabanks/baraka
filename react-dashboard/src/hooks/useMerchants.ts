import { useQuery } from '@tanstack/react-query';
import { merchantsApi } from '../services/api';
import type {
  MerchantDetailResponse,
  MerchantListParams,
  MerchantListResponse,
} from '../types/merchants';

const STALE_TIME = 60 * 1000;

export const useMerchantList = (params?: MerchantListParams) =>
  useQuery<MerchantListResponse, Error>({
    queryKey: ['merchants', params],
    queryFn: async () => {
      const response = await merchantsApi.getMerchants(params);
      return response.data;
    },
    staleTime: STALE_TIME,
    keepPreviousData: true,
  });

export const useMerchantDetail = (
  merchantId: number | string | null,
  options?: { enabled?: boolean },
) =>
  useQuery<MerchantDetailResponse, Error>({
    queryKey: ['merchant', merchantId],
    queryFn: async () => {
      if (!merchantId) {
        throw new Error('Merchant identifier is required');
      }
      const response = await merchantsApi.getMerchant(merchantId);
      return response.data;
    },
    enabled: Boolean(merchantId) && (options?.enabled ?? true),
    staleTime: STALE_TIME,
  });


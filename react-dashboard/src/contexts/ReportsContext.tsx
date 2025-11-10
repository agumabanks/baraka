import React, { createContext, useContext, useState, ReactNode, useCallback } from 'react';
import { apiClient } from '../services/api';

interface Report {
  id: string;
  name: string;
  type: string;
  period: string;
  status: string;
  created_at: string;
  updated_at: string;
  data?: Record<string, any>;
}

interface ReportsContextType {
  reports: Report[];
  isLoading: boolean;
  error: string | null;
  fetchReports: (filters?: Record<string, any>) => Promise<void>;
  generateReport: (type: string, params?: Record<string, any>) => Promise<Report>;
  getReport: (id: string) => Promise<Report>;
  deleteReport: (id: string) => Promise<void>;
  exportReport: (id: string, format: string) => Promise<Blob>;
}

const ReportsContext = createContext<ReportsContextType | undefined>(undefined);

export function ReportsProvider({ children }: { children: ReactNode }) {
  const [reports, setReports] = useState<Report[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchReports = useCallback(async (filters?: Record<string, any>) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.get('/v1/reports', { params: filters });
      
      if (response.data.success) {
        setReports(response.data.data.reports || []);
      } else {
        throw new Error(response.data.message || 'Failed to fetch reports');
      }
    } catch (err) {
      setError(err.message || 'Failed to fetch reports');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const generateReport = useCallback(async (type: string, params?: Record<string, any>) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.post('/v1/reports/generate', { type, ...params });
      
      if (response.data.success) {
        const newReport = response.data.data.report;
        setReports(prev => [newReport, ...prev]);
        return newReport;
      } else {
        throw new Error(response.data.message || 'Failed to generate report');
      }
    } catch (err) {
      setError(err.message || 'Failed to generate report');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const getReport = useCallback(async (id: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.get(`/v1/reports/${id}`);
      
      if (response.data.success) {
        return response.data.data.report;
      } else {
        throw new Error(response.data.message || 'Failed to fetch report');
      }
    } catch (err) {
      setError(err.message || 'Failed to fetch report');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const deleteReport = useCallback(async (id: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.delete(`/v1/reports/${id}`);
      
      if (response.data.success) {
        setReports(prev => prev.filter(r => r.id !== id));
      } else {
        throw new Error(response.data.message || 'Failed to delete report');
      }
    } catch (err) {
      setError(err.message || 'Failed to delete report');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const exportReport = useCallback(async (id: string, format: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.get(`/v1/reports/${id}/export`, {
        params: { format },
        responseType: 'blob'
      });
      
      return response.data;
    } catch (err) {
      setError(err.message || 'Failed to export report');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const value: ReportsContextType = {
    reports,
    isLoading,
    error,
    fetchReports,
    generateReport,
    getReport,
    deleteReport,
    exportReport,
  };

  return (
    <ReportsContext.Provider value={value}>
      {children}
    </ReportsContext.Provider>
  );
}

export function useReports() {
  const context = useContext(ReportsContext);
  if (!context) {
    throw new Error('useReports must be used within ReportsProvider');
  }
  return context;
}

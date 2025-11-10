import React, { createContext, useContext, useState, ReactNode, useCallback } from 'react';
import { apiClient } from '../services/api';

interface Shipment {
  id: string;
  tracking_number: string;
  status: string;
  origin: string;
  destination: string;
  weight: number;
  created_at: string;
  updated_at: string;
}

interface ShipmentContextType {
  shipments: Shipment[];
  isLoading: boolean;
  error: string | null;
  fetchShipments: (filters?: Record<string, any>) => Promise<void>;
  createShipment: (data: Partial<Shipment>) => Promise<Shipment>;
  updateShipment: (id: string, data: Partial<Shipment>) => Promise<Shipment>;
  deleteShipment: (id: string) => Promise<void>;
  getShipment: (id: string) => Promise<Shipment>;
}

const ShipmentContext = createContext<ShipmentContextType | undefined>(undefined);

export function ShipmentProvider({ children }: { children: ReactNode }) {
  const [shipments, setShipments] = useState<Shipment[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchShipments = useCallback(async (filters?: Record<string, any>) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.get('/v1/shipments', { params: filters });
      
      if (response.data.success) {
        setShipments(response.data.data.shipments || []);
      } else {
        throw new Error(response.data.message || 'Failed to fetch shipments');
      }
    } catch (err) {
      setError(err.message || 'Failed to fetch shipments');
    } finally {
      setIsLoading(false);
    }
  }, []);

  const createShipment = useCallback(async (data: Partial<Shipment>) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.post('/v1/shipments', data);
      
      if (response.data.success) {
        const newShipment = response.data.data.shipment;
        setShipments(prev => [...prev, newShipment]);
        return newShipment;
      } else {
        throw new Error(response.data.message || 'Failed to create shipment');
      }
    } catch (err) {
      setError(err.message || 'Failed to create shipment');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const updateShipment = useCallback(async (id: string, data: Partial<Shipment>) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.put(`/v1/shipments/${id}`, data);
      
      if (response.data.success) {
        const updatedShipment = response.data.data.shipment;
        setShipments(prev => prev.map(s => s.id === id ? updatedShipment : s));
        return updatedShipment;
      } else {
        throw new Error(response.data.message || 'Failed to update shipment');
      }
    } catch (err) {
      setError(err.message || 'Failed to update shipment');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const deleteShipment = useCallback(async (id: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.delete(`/v1/shipments/${id}`);
      
      if (response.data.success) {
        setShipments(prev => prev.filter(s => s.id !== id));
      } else {
        throw new Error(response.data.message || 'Failed to delete shipment');
      }
    } catch (err) {
      setError(err.message || 'Failed to delete shipment');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const getShipment = useCallback(async (id: string) => {
    try {
      setIsLoading(true);
      setError(null);
      
      const response = await apiClient.get(`/v1/shipments/${id}`);
      
      if (response.data.success) {
        return response.data.data.shipment;
      } else {
        throw new Error(response.data.message || 'Failed to fetch shipment');
      }
    } catch (err) {
      setError(err.message || 'Failed to fetch shipment');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const value: ShipmentContextType = {
    shipments,
    isLoading,
    error,
    fetchShipments,
    createShipment,
    updateShipment,
    deleteShipment,
    getShipment,
  };

  return (
    <ShipmentContext.Provider value={value}>
      {children}
    </ShipmentContext.Provider>
  );
}

export function useShipments() {
  const context = useContext(ShipmentContext);
  if (!context) {
    throw new Error('useShipments must be used within ShipmentProvider');
  }
  return context;
}

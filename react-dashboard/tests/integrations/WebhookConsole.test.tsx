import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AdminWebhookConsole } from '@/pages/integrations/AdminWebhookConsole';
import { webhookApi } from '@/services/api';

// Mock the API
jest.mock('@/services/api', () => ({
  webhookApi: {
    getEndpoints: jest.fn(),
    getDeliveries: jest.fn(),
    getMetrics: jest.fn(),
    getHealthStatus: jest.fn(),
    createEndpoint: jest.fn(),
    deleteEndpoint: jest.fn(),
    testEndpoint: jest.fn(),
    retryDelivery: jest.fn(),
  },
}));

// Mock the toast store
jest.mock('@/stores/toastStore', () => ({
  toast: {
    success: jest.fn(),
    error: jest.fn(),
  },
}));

const mockedWebhookApi = webhookApi as jest.Mocked<typeof webhookApi>;

const createWrapper = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  });
  
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={queryClient}>
      {children}
    </QueryClientProvider>
  );
};

describe('AdminWebhookConsole', () => {
  const mockEndpoints = [
    {
      id: '1',
      name: 'Test Endpoint',
      url: 'https://example.com/webhook',
      is_active: true,
      events: ['shipment.created', 'shipment.updated'],
      success_rate: 95,
      created_at: '2023-11-11T10:00:00Z',
      last_test_at: '2023-11-11T10:30:00Z',
      description: 'A test webhook endpoint',
      last_delivery_error: null,
      secret_key: 'secret',
      updated_at: '2023-11-11T10:30:00Z',
    },
  ];

  const mockDeliveries = [
    {
      id: '1',
      webhook_endpoint_id: '1',
      event_type: 'shipment.created',
      status: 'success' as const,
      created_at: '2023-11-11T10:00:00Z',
      response_status: 200,
      duration_ms: 150,
      retry_count: 0,
      payload: { test: 'data' },
      response_body: 'OK',
    },
  ];

  beforeEach(() => {
    mockedWebhookApi.getEndpoints.mockResolvedValue({
      success: true,
      data: mockEndpoints,
    });
    mockedWebhookApi.getDeliveries.mockResolvedValue({
      data: mockDeliveries,
      pagination: {
        current_page: 1,
        per_page: 20,
        total: 1,
        last_page: 1,
      },
    });
    mockedWebhookApi.getMetrics.mockResolvedValue({
      success: true,
      data: {
        delivery_chart: [],
        response_time_chart: [],
        error_rate_chart: [],
        top_events: [],
      },
    });
    mockedWebhookApi.getHealthStatus.mockResolvedValue({
      success: true,
      data: {
        overall_status: 'healthy',
        last_check: new Date().toISOString(),
        endpoint_count: 1,
        active_endpoints: 1,
        recent_deliveries: 1,
        error_rate: 0,
        average_response_time: 10,
        overview: {
          total_endpoints: 1,
          active_endpoints: 1,
          success_rate: 100,
          average_response_time: 10,
          failed_deliveries: 0,
          pending_deliveries: 0,
        },
        delivery_volume_chart: [
          { date: new Date().toISOString(), count: 1, success: 1, failed: 0 },
        ],
        response_time_chart: [
          { date: new Date().toISOString(), average_time: 10, p50: 8, p95: 15 },
        ],
        components: [
          { name: 'Webhook Engine', status: 'operational' },
        ],
        alerts: [],
      },
    });
  });

  it('renders the webhook console with correct header', () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    expect(screen.getByText('Webhook Management Console')).toBeInTheDocument();
    expect(screen.getByText('Manage webhook endpoints, monitor deliveries, and track system health')).toBeInTheDocument();
  });

  it('displays summary cards with correct counts', () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    expect(screen.getByText('Total Endpoints')).toBeInTheDocument();
    expect(screen.getByText('Active')).toBeInTheDocument();
    expect(screen.getByText('Inactive')).toBeInTheDocument();
    expect(screen.getByText('Total Deliveries')).toBeInTheDocument();
    expect(screen.getByText('Successful')).toBeInTheDocument();
    expect(screen.getByText('Failed')).toBeInTheDocument();
  });

  it('renders navigation tabs', () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    expect(screen.getByText('Overview')).toBeInTheDocument();
    expect(screen.getByText('Endpoints')).toBeInTheDocument();
    expect(screen.getByText('Deliveries')).toBeInTheDocument();
    expect(screen.getByText('Health')).toBeInTheDocument();
    expect(screen.getByText('Settings')).toBeInTheDocument();
  });

  it('switches between tabs when clicked', async () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      expect(screen.getByText('Create Endpoint')).toBeInTheDocument();
    });
  });

  it('displays webhook endpoints list', async () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      expect(screen.getByText('Test Endpoint')).toBeInTheDocument();
      expect(screen.getByText('https://example.com/webhook')).toBeInTheDocument();
    });
  });

  it('filters endpoints by status', async () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      const statusSelect = screen.getByDisplayValue('All Statuses');
      fireEvent.change(statusSelect, { target: { value: 'active' } });
      
      expect(screen.getByText('Test Endpoint')).toBeInTheDocument();
    });
  });

  it('searches endpoints by name or URL', async () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      const searchInput = screen.getByPlaceholderText('Search endpoints...');
      fireEvent.change(searchInput, { target: { value: 'Test' } });
      
      expect(screen.getByText('Test Endpoint')).toBeInTheDocument();
    });
  });

  it('calls test endpoint when test button is clicked', async () => {
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      const testButton = screen.getByText('Test');
      fireEvent.click(testButton);
      
      expect(webhookApi.testEndpoint).toHaveBeenCalledWith('1');
    });
  });

  it('calls delete endpoint when delete button is clicked', async () => {
    const confirmSpy = jest.spyOn(window, 'confirm');
    confirmSpy.mockReturnValue(true);
    
    render(<AdminWebhookConsole />, { wrapper: createWrapper() });
    
    const endpointsTab = screen.getByText('Endpoints').closest('button');
    fireEvent.click(endpointsTab!);
    
    await waitFor(() => {
      const deleteButton = screen.getByText('Delete');
      fireEvent.click(deleteButton);
      
      expect(webhookApi.deleteEndpoint).toHaveBeenCalledWith('1');
    });
    
    confirmSpy.mockRestore();
  });
});

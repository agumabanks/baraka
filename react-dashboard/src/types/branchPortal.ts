export interface BranchPortalOverview {
  branch: {
    id: number;
    name: string;
    code: string;
    type: string;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    status: string;
    manager?: {
      id: number;
      name: string;
      email: string;
    } | null;
  };
  role: {
    type: 'manager' | 'worker';
    role: string;
    worker_id?: number;
  };
  metrics: {
    active_shipments: number;
    delivered_today: number;
    pending_pickups: number;
  };
  shipments: BranchPortalShipment[];
  links: {
    booking_wizard: string;
    shipments: string;
    branch_profile: string;
  };
  mode_distribution?: Array<{
    mode: string;
    label: string;
    count: number;
    active: number;
    percentage: number;
  }>;
}

export interface BranchPortalShipment {
  id: number;
  tracking_number: string;
  status: string;
  client?: {
    id: number;
    business_name: string;
  } | null;
  destination?: {
    id?: number;
    name?: string;
  } | null;
  expected_delivery_date?: string | null;
  amount?: number | null;
  currency?: string | null;
  created_at?: string | null;
}

import React, { useState, useEffect } from 'react';
import { 
  Shield, 
  AlertTriangle, 
  CheckCircle, 
  XCircle, 
  Clock, 
  TrendingUp, 
  FileText, 
  Download,
  Filter,
  Search,
  RefreshCw
} from 'lucide-react';

/**
 * Comprehensive Audit Dashboard
 * Shows accessibility compliance, audit trails, and compliance monitoring
 */
interface AuditDashboardProps {
  className?: string;
  refreshInterval?: number;
  showExportOptions?: boolean;
}

interface ComplianceSummary {
  total_tests: number;
  average_score: number;
  critical_violations: number;
  non_compliant_pages: number;
  violations_by_type: Record<string, number>;
}

interface AuditStats {
  total_actions: number;
  critical_actions: number;
  user_activity: Record<string, number>;
  actions_by_type: Record<string, number>;
  recent_activity: Array<{
    id: string;
    action: string;
    user: string;
    timestamp: string;
    severity: string;
  }>;
}

interface ComplianceViolation {
  id: string;
  framework: string;
  type: string;
  severity: string;
  description: string;
  discovered_at: string;
  status: 'open' | 'resolved' | 'false_positive';
}

export function AuditDashboard({ 
  className = '', 
  refreshInterval = 30000, 
  showExportOptions = true 
}: AuditDashboardProps) {
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'accessibility' | 'audit' | 'violations'>('overview');
  const [dateRange, setDateRange] = useState('7d');
  const [searchTerm, setSearchTerm] = useState('');

  // Sample data - in real implementation, this would come from API
  const [complianceData, setComplianceData] = useState<ComplianceSummary>({
    total_tests: 156,
    average_score: 87.3,
    critical_violations: 8,
    non_compliant_pages: 12,
    violations_by_type: {
      'color-contrast': 15,
      'image-alt': 8,
      'button-name': 5,
      'form-label': 12
    }
  });

  const [auditData, setAuditData] = useState<AuditStats>({
    total_actions: 1247,
    critical_actions: 23,
    user_activity: {},
    actions_by_type: {
      'create': 342,
      'read': 567,
      'update': 278,
      'delete': 60
    },
    recent_activity: [
      {
        id: '1',
        action: 'User login',
        user: 'admin@example.com',
        timestamp: '2025-11-07T03:20:00Z',
        severity: 'info'
      },
      {
        id: '2',
        action: 'Price calculation',
        user: 'user@example.com',
        timestamp: '2025-11-07T03:19:45Z',
        severity: 'info'
      },
      {
        id: '3',
        action: 'Failed login attempt',
        user: 'unknown',
        timestamp: '2025-11-07T03:19:30Z',
        severity: 'warning'
      }
    ]
  });

  const [violations, setViolations] = useState<ComplianceViolation[]>([
    {
      id: '1',
      framework: 'WCAG',
      type: 'color-contrast',
      severity: 'high',
      description: 'Button text has insufficient color contrast',
      discovered_at: '2025-11-07T02:30:00Z',
      status: 'open'
    },
    {
      id: '2',
      framework: 'GDPR',
      type: 'data-access',
      severity: 'critical',
      description: 'Personal data accessed without proper authorization',
      discovered_at: '2025-11-07T01:15:00Z',
      status: 'open'
    }
  ]);

  // Auto-refresh data
  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000));
      setLoading(false);
    };

    fetchData();
    const interval = setInterval(fetchData, refreshInterval);
    return () => clearInterval(interval);
  }, [refreshInterval]);

  // Calculate scores
  const accessibilityScore = Math.round(complianceData.average_score);
  const complianceLevel = accessibilityScore >= 90 ? 'excellent' : 
                         accessibilityScore >= 70 ? 'good' : 'needs-improvement';

  // Get severity color
  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'critical': return 'text-red-600 bg-red-50';
      case 'high': return 'text-orange-600 bg-orange-50';
      case 'medium': return 'text-yellow-600 bg-yellow-50';
      case 'low': return 'text-blue-600 bg-blue-50';
      default: return 'text-gray-600 bg-gray-50';
    }
  };

  // Get status color
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'resolved': return 'text-green-600 bg-green-50';
      case 'false_positive': return 'text-gray-600 bg-gray-50';
      default: return 'text-red-600 bg-red-50';
    }
  };

  return (
    <div className={`audit-dashboard ${className}`}>
      {/* Header */}
      <div className="bg-white shadow rounded-lg mb-6">
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <Shield className="h-6 w-6 text-blue-600" />
                Audit Dashboard
              </h1>
              <p className="text-sm text-gray-600 mt-1">
                Monitor accessibility compliance and system audit trails
              </p>
            </div>
            
            <div className="flex items-center gap-3">
              <select
                value={dateRange}
                onChange={(e) => setDateRange(e.target.value)}
                className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="1d">Last 24 hours</option>
                <option value="7d">Last 7 days</option>
                <option value="30d">Last 30 days</option>
                <option value="90d">Last 90 days</option>
              </select>
              
              <button
                onClick={() => window.location.reload()}
                className="flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50 focus:ring-2 focus:ring-blue-500"
                aria-label="Refresh dashboard"
              >
                <RefreshCw className="h-4 w-4" />
                Refresh
              </button>
              
              {showExportOptions && (
                <button
                  className="flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500"
                  aria-label="Export reports"
                >
                  <Download className="h-4 w-4" />
                  Export
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex" aria-label="Dashboard tabs">
            {[
              { id: 'overview', label: 'Overview', icon: Shield },
              { id: 'accessibility', label: 'Accessibility', icon: CheckCircle },
              { id: 'audit', label: 'Audit Trail', icon: FileText },
              { id: 'violations', label: 'Violations', icon: AlertTriangle }
            ].map(tab => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id as any)}
                className={`
                  flex items-center gap-2 py-4 px-6 border-b-2 font-medium text-sm
                  ${activeTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }
                `}
                aria-current={activeTab === tab.id ? 'page' : undefined}
              >
                <tab.icon className="h-4 w-4" />
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Content */}
      <div className="space-y-6">
        {/* Overview Tab */}
        {activeTab === 'overview' && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {/* Accessibility Score Card */}
            <div className="bg-white p-6 rounded-lg shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Accessibility Score</p>
                  <p className="text-2xl font-bold text-gray-900">{accessibilityScore}%</p>
                  <p className="text-sm text-gray-500">{complianceData.total_tests} tests</p>
                </div>
                <div className={`
                  p-3 rounded-full
                  ${accessibilityScore >= 90 ? 'bg-green-100 text-green-600' : 
                    accessibilityScore >= 70 ? 'bg-yellow-100 text-yellow-600' : 
                    'bg-red-100 text-red-600'}
                `}>
                  <CheckCircle className="h-6 w-6" />
                </div>
              </div>
              <div className="mt-4">
                <div className="flex items-center text-sm">
                  <span className="text-gray-500">Level: </span>
                  <span className={`ml-1 font-medium capitalize ${
                    accessibilityScore >= 90 ? 'text-green-600' : 
                    accessibilityScore >= 70 ? 'text-yellow-600' : 'text-red-600'
                  }`}>
                    {complianceLevel}
                  </span>
                </div>
              </div>
            </div>

            {/* Critical Violations Card */}
            <div className="bg-white p-6 rounded-lg shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Critical Violations</p>
                  <p className="text-2xl font-bold text-red-600">{complianceData.critical_violations}</p>
                  <p className="text-sm text-gray-500">Need immediate attention</p>
                </div>
                <div className="p-3 rounded-full bg-red-100 text-red-600">
                  <XCircle className="h-6 w-6" />
                </div>
              </div>
            </div>

            {/* Audit Actions Card */}
            <div className="bg-white p-6 rounded-lg shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Audit Actions</p>
                  <p className="text-2xl font-bold text-gray-900">{auditData.total_actions}</p>
                  <p className="text-sm text-gray-500">Last 24 hours</p>
                </div>
                <div className="p-3 rounded-full bg-blue-100 text-blue-600">
                  <FileText className="h-6 w-6" />
                </div>
              </div>
            </div>

            {/* Non-Compliant Pages Card */}
            <div className="bg-white p-6 rounded-lg shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">Non-Compliant Pages</p>
                  <p className="text-2xl font-bold text-orange-600">{complianceData.non_compliant_pages}</p>
                  <p className="text-sm text-gray-500">Require fixes</p>
                </div>
                <div className="p-3 rounded-full bg-orange-100 text-orange-600">
                  <AlertTriangle className="h-6 w-6" />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Accessibility Tab */}
        {activeTab === 'accessibility' && (
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Accessibility Compliance</h2>
              <p className="text-sm text-gray-600">WCAG 2.1 AA compliance monitoring</p>
            </div>
            
            <div className="p-6">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Compliance Chart */}
                <div>
                  <h3 className="text-sm font-medium text-gray-900 mb-4">Score Trends</h3>
                  <div className="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                    <p className="text-gray-500">Chart will be rendered here</p>
                  </div>
                </div>
                
                {/* Violation Types */}
                <div>
                  <h3 className="text-sm font-medium text-gray-900 mb-4">Violation Types</h3>
                  <div className="space-y-3">
                    {Object.entries(complianceData.violations_by_type).map(([type, count]) => (
                      <div key={type} className="flex items-center justify-between">
                        <span className="text-sm text-gray-600 capitalize">
                          {type.replace('-', ' ')}
                        </span>
                        <span className="text-sm font-medium text-gray-900">{count}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Audit Trail Tab */}
        {activeTab === 'audit' && (
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <div className="flex items-center justify-between">
                <div>
                  <h2 className="text-lg font-semibold text-gray-900">Audit Trail</h2>
                  <p className="text-sm text-gray-600">System activity and security logs</p>
                </div>
                <div className="flex items-center gap-2">
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input
                      type="text"
                      placeholder="Search audit logs..."
                      className="pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                    />
                  </div>
                </div>
              </div>
            </div>
            
            <div className="p-6">
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Action
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Timestamp
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Severity
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {auditData.recent_activity.map((activity) => (
                      <tr key={activity.id}>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {activity.action}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {activity.user}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {new Date(activity.timestamp).toLocaleString()}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`
                            inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            ${getSeverityColor(activity.severity)}
                          `}>
                            {activity.severity}
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Violations Tab */}
        {activeTab === 'violations' && (
          <div className="bg-white rounded-lg shadow">
            <div className="p-6 border-b border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900">Compliance Violations</h2>
              <p className="text-sm text-gray-600">Open issues requiring attention</p>
            </div>
            
            <div className="p-6">
              <div className="space-y-4">
                {violations.map((violation) => (
                  <div key={violation.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <span className="text-sm font-medium text-gray-900">
                            {violation.framework}
                          </span>
                          <span className={`
                            inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            ${getSeverityColor(violation.severity)}
                          `}>
                            {violation.severity}
                          </span>
                          <span className={`
                            inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            ${getStatusColor(violation.status)}
                          `}>
                            {violation.status.replace('_', ' ')}
                          </span>
                        </div>
                        <h3 className="text-sm font-medium text-gray-900 mb-1">
                          {violation.type.replace('-', ' ')}
                        </h3>
                        <p className="text-sm text-gray-600">
                          {violation.description}
                        </p>
                        <p className="text-xs text-gray-500 mt-2">
                          Discovered: {new Date(violation.discovered_at).toLocaleString()}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <button className="text-sm text-blue-600 hover:text-blue-800">
                          View Details
                        </button>
                        {violation.status === 'open' && (
                          <button className="text-sm text-green-600 hover:text-green-800">
                            Mark Resolved
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
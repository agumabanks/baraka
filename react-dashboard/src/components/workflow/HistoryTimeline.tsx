import React from 'react';
import Card from '../ui/Card';
import Badge from '../ui/Badge';

interface HistoryEntry {
  id: string;
  action: 'created' | 'updated' | 'status_changed' | 'assigned' | 'commented' | 'deleted';
  user: string;
  timestamp: string;
  details: Record<string, any>;
  old_value?: string;
  new_value?: string;
}

interface HistoryTimelineProps {
  workflowItemId: string;
  history: HistoryEntry[];
  isLoading?: boolean;
}

const actionIcons: Record<string, string> = {
  created: 'fas fa-plus-circle text-green-600',
  updated: 'fas fa-edit text-blue-600',
  status_changed: 'fas fa-exchange-alt text-purple-600',
  assigned: 'fas fa-user-tag text-orange-600',
  commented: 'fas fa-comment text-gray-600',
  deleted: 'fas fa-trash text-red-600',
};

const actionLabels: Record<string, string> = {
  created: 'Created',
  updated: 'Updated',
  status_changed: 'Status Changed',
  assigned: 'Assigned',
  commented: 'Commented',
  deleted: 'Deleted',
};

const HistoryTimeline: React.FC<HistoryTimelineProps> = ({
  history,
  isLoading = false,
}) => {
  const formatDateTime = (date: string) => {
    const d = new Date(date);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    
    return d.toLocaleDateString() + ' at ' + d.toLocaleTimeString();
  };

  const renderChangeDetails = (entry: HistoryEntry) => {
    switch (entry.action) {
      case 'created':
        return <span className="text-sm text-mono-gray-600">Task created</span>;
      
      case 'status_changed':
        return (
          <div className="flex items-center gap-2 text-sm">
            <Badge variant="outline" size="sm">{entry.old_value || 'unknown'}</Badge>
            <i className="fas fa-arrow-right text-xs text-mono-gray-400" aria-hidden="true" />
            <Badge variant="solid" size="sm">{entry.new_value || 'unknown'}</Badge>
          </div>
        );
      
      case 'assigned':
        return (
          <span className="text-sm text-mono-gray-600">
            Assigned to <strong className="text-mono-black">{entry.new_value || 'someone'}</strong>
          </span>
        );
      
      case 'updated':
        return (
          <div className="text-sm text-mono-gray-600">
            Updated{' '}
            {entry.details?.fields && (
              <span className="font-medium text-mono-black">
                {entry.details.fields.join(', ')}
              </span>
            )}
          </div>
        );
      
      case 'commented':
        return (
          <div className="text-sm text-mono-gray-600">
            Added a comment
            {entry.details?.preview && (
              <p className="mt-1 text-xs italic text-mono-gray-500 border-l-2 border-mono-gray-300 pl-2">
                "{entry.details.preview}"
              </p>
            )}
          </div>
        );
      
      default:
        return <span className="text-sm text-mono-gray-600">{entry.action}</span>;
    }
  };

  if (isLoading) {
    return (
      <Card className="border border-mono-gray-200">
        <div className="flex items-center justify-center py-8">
          <i className="fas fa-spinner fa-spin text-2xl text-mono-gray-400" aria-hidden="true" />
        </div>
      </Card>
    );
  }

  return (
    <Card className="border border-mono-gray-200">
      <header className="flex items-center justify-between mb-6">
        <div>
          <h3 className="text-lg font-semibold text-mono-black">Activity Timeline</h3>
          <p className="text-xs text-mono-gray-500">{history.length} event{history.length !== 1 ? 's' : ''} recorded</p>
        </div>
        <Button variant="secondary" size="sm">
          <i className="fas fa-download mr-2" aria-hidden="true" />
          Export
        </Button>
      </header>

      {history.length === 0 ? (
        <div className="text-center py-8">
          <i className="fas fa-history text-4xl text-mono-gray-300 mb-3" aria-hidden="true" />
          <p className="text-sm text-mono-gray-500">No activity recorded yet</p>
        </div>
      ) : (
        <div className="relative">
          {/* Timeline Line */}
          <div className="absolute left-4 top-0 bottom-0 w-px bg-mono-gray-200" aria-hidden="true" />

          {/* Timeline Items */}
          <div className="space-y-6">
            {history.map((entry) => (
              <div key={entry.id} className="relative flex gap-4 pl-10">
                {/* Timeline Dot */}
                <div className="absolute left-0 w-8 h-8 rounded-full bg-white border-2 border-mono-gray-200 flex items-center justify-center">
                  <i className={actionIcons[entry.action] || 'fas fa-circle text-mono-gray-400'} aria-hidden="true" />
                </div>

                {/* Content */}
                <div className="flex-1">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2">
                      <span className="text-sm font-semibold text-mono-black">
                        {actionLabels[entry.action] || entry.action}
                      </span>
                      <span className="text-xs text-mono-gray-500">by {entry.user}</span>
                    </div>
                    <span className="text-xs text-mono-gray-400">{formatDateTime(entry.timestamp)}</span>
                  </div>
                  <div className="mt-1">{renderChangeDetails(entry)}</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </Card>
  );
};

const Button: React.FC<{
  variant?: 'primary' | 'secondary';
  size?: 'sm' | 'md';
  children: React.ReactNode;
}> = ({ variant = 'primary', size = 'md', children }) => {
  const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors';
  const variantClasses = variant === 'primary' 
    ? 'bg-mono-black text-white hover:bg-mono-gray-800' 
    : 'bg-mono-gray-100 text-mono-black hover:bg-mono-gray-200';
  const sizeClasses = size === 'sm' ? 'px-3 py-1.5 text-sm' : 'px-4 py-2 text-base';
  
  return (
    <button className={`${baseClasses} ${variantClasses} ${sizeClasses}`}>
      {children}
    </button>
  );
};

export default HistoryTimeline;

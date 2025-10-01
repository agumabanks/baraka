import React from 'react';
import { hasPermission, hasRole } from '../../lib/rbac';

interface CanProps {
  /** Permission slug to check */
  permission?: string;
  /** Role slug to check */
  role?: string;
  /** Children to render if permission granted */
  children: React.ReactNode;
  /** Fallback to render if permission denied */
  fallback?: React.ReactNode;
}

/**
 * Can Component - RBAC Wrapper
 * Conditionally renders children based on user permissions/roles
 * Matches Laravel's @can/@role directives
 */
const Can: React.FC<CanProps> = ({ permission, role, children, fallback = null }) => {
  let hasAccess = true;
  
  if (permission) {
    hasAccess = hasPermission(permission);
  }
  
  if (role && hasAccess) {
    hasAccess = hasRole(role);
  }
  
  return hasAccess ? <>{children}</> : <>{fallback}</>;
};

export default Can;
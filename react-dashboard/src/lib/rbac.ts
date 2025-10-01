/**
 * RBAC (Role-Based Access Control) Helper
 * Checks user permissions matching Laravel's hasPermission()
 */

export interface UserPermissions {
  permissions: string[];
  roles: string[];
}

// Global permissions state (injected from server or API)
let currentUserPermissions: UserPermissions = {
  permissions: [],
  roles: [],
};

/**
 * Set current user permissions
 * Should be called on app initialization with user data
 */
export function setUserPermissions(permissions: UserPermissions): void {
  currentUserPermissions = permissions;
}

/**
 * Check if user has a specific permission
 * @param permission Permission slug (e.g., 'total_parcel', 'parcel_read')
 * @returns True if user has permission
 */
export function hasPermission(permission: string): boolean {
  return currentUserPermissions.permissions.includes(permission);
}

/**
 * Check if user has a specific role
 * @param role Role slug (e.g., 'admin', 'merchant')
 * @returns True if user has role
 */
export function hasRole(role: string): boolean {
  return currentUserPermissions.roles.includes(role);
}

/**
 * Check if user has ANY of the specified permissions
 * @param permissions Array of permission slugs
 * @returns True if user has at least one permission
 */
export function hasAnyPermission(permissions: string[]): boolean {
  return permissions.some(hasPermission);
}

/**
 * Get all current user permissions
 */
export function getUserPermissions(): UserPermissions {
  return currentUserPermissions;
}
const ROUTE_ALIASES: Record<string, string> = {
  'booking/step1': 'bookings',
  hubs: 'branches',
  customers: 'customers',
};

const ALIAS_TO_CANONICAL: Record<string, string> = {
  bookings: 'booking/step1',
  branches: 'hubs',
  customers: 'customers',
};

export const canonicalisePath = (rawPath?: string): string => {
  if (!rawPath) {
    return '';
  }

  const trimmed = rawPath.trim();
  if (!trimmed) {
    return '';
  }

  const withoutQuery = trimmed.split('?')[0]?.split('#')[0] ?? '';
  const withoutLeadingSlash = withoutQuery.startsWith('/') ? withoutQuery.slice(1) : withoutQuery;
  return withoutLeadingSlash.replace(/\/$/, '');
};

export const resolveRoutePath = (rawPath?: string): string => {
  const canonical = canonicalisePath(rawPath);
  if (!canonical || canonical === 'dashboard') {
    return '';
  }

  const withoutDashboardPrefix =
    canonical === 'dashboard'
      ? ''
      : canonical.startsWith('dashboard/')
      ? canonical.slice('dashboard/'.length)
      : canonical;

  if (!withoutDashboardPrefix) {
    return '';
  }

  return ROUTE_ALIASES[withoutDashboardPrefix] ?? withoutDashboardPrefix;
};

export const resolveDashboardNavigatePath = (rawPath?: string): string => {
  const canonical = canonicalisePath(rawPath);

  if (!canonical || canonical === 'dashboard') {
    return '/dashboard';
  }

  const withoutDashboardPrefix =
    canonical.startsWith('dashboard/')
      ? canonical.slice('dashboard/'.length)
      : canonical;

  const alias = ROUTE_ALIASES[withoutDashboardPrefix] ?? withoutDashboardPrefix;
  return alias ? `/dashboard/${alias}`.replace('//', '/') : '/dashboard';
};

export const getCanonicalFromAlias = (alias: string): string | undefined => {
  return ALIAS_TO_CANONICAL[alias];
};

export const getAliasToCanonicalMap = () => ({ ...ALIAS_TO_CANONICAL });
export const getRouteAliases = () => ({ ...ROUTE_ALIASES });

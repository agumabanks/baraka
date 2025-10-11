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

const stripPrefix = (path: string, prefix: string) => {
  if (!path) {
    return '';
  }

  if (path === prefix) {
    return '';
  }

  const prefixWithSlash = `${prefix}/`;
  if (path.startsWith(prefixWithSlash)) {
    return path.slice(prefixWithSlash.length);
  }

  return path;
};

const stripKnownPrefixes = (path: string) => {
  const prefixes = ['dashboard', 'admin'];

  return prefixes.reduce((acc, prefix) => stripPrefix(acc, prefix), path);
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

  const withoutPrefixes = stripKnownPrefixes(canonical);

  if (!withoutPrefixes) {
    return '';
  }

  return ROUTE_ALIASES[withoutPrefixes] ?? withoutPrefixes;
};

export const resolveDashboardNavigatePath = (rawPath?: string): string => {
  const trimmed = rawPath?.trim() ?? '';
  if (!trimmed) {
    return '/dashboard';
  }

  if (/^(https?:)?\/\//.test(trimmed) || trimmed.startsWith('mailto:') || trimmed.startsWith('tel:')) {
    return trimmed;
  }

  const canonical = canonicalisePath(trimmed);

  if (!canonical || canonical === 'dashboard') {
    return '/dashboard';
  }

  const withoutPrefixes = stripKnownPrefixes(canonical);
  const alias = ROUTE_ALIASES[withoutPrefixes] ?? withoutPrefixes;
  const target = alias ? `/dashboard/${alias}` : '/dashboard';

  const normalised = target.replace(/\/{2,}/g, '/');
  return normalised.endsWith('/') && normalised !== '/' ? normalised.slice(0, -1) : normalised;
};

export const getCanonicalFromAlias = (alias: string): string | undefined => {
  return ALIAS_TO_CANONICAL[alias];
};

export const getAliasToCanonicalMap = () => ({ ...ALIAS_TO_CANONICAL });
export const getRouteAliases = () => ({ ...ROUTE_ALIASES });

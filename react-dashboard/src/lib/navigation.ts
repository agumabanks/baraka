import type { NavBucket, NavItem, NavigationConfig } from '../types/navigation';
import { navigationConfig } from '../config/navigation';

export interface RouteMeta {
  path: string;
  label: string;
  parents: Array<{ label: string; path?: string }>;
  icon?: string;
}

const normalisePath = (path: string) => {
  if (!path) {
    return '';
  }
  return path.length > 1 && path.endsWith('/') ? path.slice(0, -1) : path;
};

const flattenItems = (
  items: NavItem[],
  parents: Array<{ label: string; path?: string }> = []
): RouteMeta[] => {
  const routes: RouteMeta[] = [];

  items.forEach((item) => {
    const currentParents = [...parents];
    if (item.label) {
      currentParents.push({ label: item.label, path: item.path || undefined });
    }

    if (item.path) {
      routes.push({
        path: normalisePath(item.path),
        label: item.label,
        parents: parents,
        icon: item.icon,
      });
    }

    if (item.children && item.children.length > 0) {
      routes.push(
        ...flattenItems(item.children, [
          ...parents,
          { label: item.label, path: item.path || undefined },
        ])
      );
    }
  });

  return routes;
};

const flattenBuckets = (config: NavigationConfig): RouteMeta[] => {
  const routes: RouteMeta[] = [];

  config.buckets
    .filter((bucket) => bucket.visible !== false)
    .forEach((bucket: NavBucket) => {
      const bucketParent = { label: bucket.label };
      routes.push(
        ...flattenItems(
          bucket.items.filter((item) => item.visible !== false),
          [bucketParent]
        )
      );
    });

  return routes;
};

export const navigationRoutes: RouteMeta[] = (() => {
  const flattened = flattenBuckets(navigationConfig);
  const uniqueMap = new Map<string, RouteMeta>();

  flattened.forEach((meta) => {
    const key = normalisePath(meta.path);
    if (!uniqueMap.has(key)) {
      uniqueMap.set(key, { ...meta, path: key });
    }
  });

  return Array.from(uniqueMap.values());
})();

export const findRouteMeta = (pathname: string): RouteMeta | undefined => {
  const cleaned = normalisePath(pathname);

  if (!cleaned) {
    return navigationRoutes.find((meta) => meta.path === '/dashboard' || meta.path === '/');
  }

  return (
    navigationRoutes.find((meta) => meta.path === cleaned) ||
    navigationRoutes.find(
      (meta) =>
        meta.path.length > 1 && cleaned.startsWith(`${meta.path}/`)
    )
  );
};

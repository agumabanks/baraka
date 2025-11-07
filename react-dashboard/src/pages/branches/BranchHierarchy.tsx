import React, { useMemo } from 'react';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Badge from '../../components/ui/Badge';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { useBranchHierarchy } from '../../hooks/useBranches';
import type { BranchHierarchyNode } from '../../types/branches';

interface TreeNodeProps {
  branch: BranchHierarchyNode;
  level: number;
}

const TreeNode: React.FC<TreeNodeProps> = ({ branch, level }) => {
  const indent = level * 24;
  
  const getTypeColor = (type: string) => {
    switch (type) {
      case 'HUB':
        return 'bg-mono-black text-mono-white';
      case 'REGIONAL':
        return 'bg-mono-gray-600 text-mono-white';
      case 'LOCAL':
        return 'bg-mono-gray-400 text-mono-white';
      default:
        return 'bg-mono-gray-200 text-mono-black';
    }
  };

  return (
    <div className="space-y-2">
      <div
        className="flex items-center gap-4 rounded-lg border border-mono-gray-200 bg-mono-white p-4 transition-all hover:shadow-md"
        style={{ marginLeft: `${indent}px` }}
      >
        <div className="flex-shrink-0">
          {level > 0 && (
            <div className="flex items-center gap-2 text-mono-gray-400">
              <i className="fas fa-level-up-alt fa-rotate-90" />
            </div>
          )}
        </div>
        
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h3 className="text-lg font-semibold text-mono-black">{branch.name}</h3>
            <Badge variant="solid" size="sm" className={getTypeColor(branch.type)}>
              {branch.type}
            </Badge>
            {branch.is_hub && (
              <Badge variant="solid" size="sm" className="bg-amber-500 text-white">
                HUB
              </Badge>
            )}
          </div>
          <div className="mt-1 flex items-center gap-4 text-sm text-mono-gray-600">
            <span className="font-mono font-semibold">{branch.code}</span>
            <span>•</span>
            <span>Level {branch.level}</span>
          </div>
        </div>

        <div className="flex items-center gap-6 text-sm">
          <div className="text-center">
            <p className="text-xs uppercase tracking-wider text-mono-gray-500">Workers</p>
            <p className="font-medium text-mono-black">{branch.workers_count || 0}</p>
          </div>
          
          <div className="text-center">
            <p className="text-xs uppercase tracking-wider text-mono-gray-500">Capacity</p>
            <p className="font-medium text-mono-black">
              {Math.round(branch.capacity_utilization || 0)}%
            </p>
          </div>

          <Button variant="ghost" size="sm" className="uppercase tracking-wider">
            View Details
          </Button>
        </div>
      </div>

      {branch.children && branch.children.length > 0 && (
        <div className="space-y-2">
          {branch.children.map((child) => (
            <TreeNode key={child.id} branch={child} level={level + 1} />
          ))}
        </div>
      )}
    </div>
  );
};

const BranchHierarchy: React.FC = () => {
  const { data, isLoading, isError, error, refetch } = useBranchHierarchy();

  const hierarchyTree = useMemo(() => data?.tree || [], [data]);
  const usingFallbackData = useMemo(() => hierarchyTree.some((branch) => typeof branch.id === 'string'), [hierarchyTree]);

  const stats = useMemo(() => {
    const countBranches = (branches: BranchHierarchyNode[]): { total: number; hubs: number; regional: number; local: number } => {
      let total = branches.length;
      let hubs = 0;
      let regional = 0;
      let local = 0;

      branches.forEach((branch) => {
        if (branch.is_hub) hubs++;
        if (branch.type === 'REGIONAL') regional++;
        if (branch.type === 'LOCAL') local++;

        if (branch.children && branch.children.length > 0) {
          const childStats = countBranches(branch.children);
          total += childStats.total;
          hubs += childStats.hubs;
          regional += childStats.regional;
          local += childStats.local;
        }
      });

      return { total, hubs, regional, local };
    };

    return countBranches(hierarchyTree);
  }, [hierarchyTree]);

  if (isLoading) {
    return <LoadingSpinner message="Loading branch hierarchy" />;
  }

  if (isError) {
    const message = error instanceof Error ? error.message : 'Unable to load branch hierarchy';
    return (
      <div className="flex min-h-[400px] flex-col items-center justify-center">
        <Card className="max-w-md text-center">
          <div className="space-y-4">
            <div className="inline-flex h-16 w-16 items-center justify-center rounded-full bg-mono-black text-mono-white">
              <i className="fas fa-exclamation-triangle text-2xl" />
            </div>
            <div>
              <h2 className="text-2xl font-semibold text-mono-black">Error Loading Hierarchy</h2>
              <p className="text-sm text-mono-gray-600">{message}</p>
            </div>
            <Button variant="primary" size="md" onClick={() => refetch()}>
              <i className="fas fa-redo mr-2" />
              Retry
            </Button>
          </div>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-xl">
        <header className="flex flex-col gap-6 border-b border-mono-gray-200 px-8 py-10 xl:flex-row xl:items-center xl:justify-between">
          <div className="space-y-3">
            <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
              Branch Network
            </p>
            <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
              Branch Hierarchy
            </h1>
            <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
              Visual representation of the complete branch network structure and relationships.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button variant="secondary" size="sm" className="uppercase tracking-[0.25em]" onClick={() => refetch()}>
              <i className="fas fa-sync-alt mr-2" />
              Refresh
            </Button>
            <Button variant="primary" size="sm" className="uppercase tracking-[0.25em]">
              <i className="fas fa-plus mr-2" />
              Add Branch
            </Button>
          </div>
        </header>

        {usingFallbackData && (
          <div className="border-b border-mono-gray-200 bg-amber-50 px-8 py-4 text-sm text-amber-800">
            Showing demo hierarchy while the branch table is empty. Create real branches to replace this snapshot.
          </div>
        )}

        <div className="grid gap-6 px-8 py-8 lg:grid-cols-4">
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Total Branches</p>
              <h2 className="text-3xl font-semibold text-mono-black">{stats.total}</h2>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Hub Branches</p>
              <h2 className="text-3xl font-semibold text-mono-black">{stats.hubs}</h2>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Regional Branches</p>
              <h2 className="text-3xl font-semibold text-mono-black">{stats.regional}</h2>
            </div>
          </Card>
          <Card className="border border-mono-gray-200 shadow-inner">
            <div className="space-y-2">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Local Branches</p>
              <h2 className="text-3xl font-semibold text-mono-black">{stats.local}</h2>
            </div>
          </Card>
        </div>

        <div className="border-t border-mono-gray-200 px-8 py-8">
          {hierarchyTree.length === 0 ? (
            <Card className="text-center">
              <div className="space-y-3">
                <h2 className="text-xl font-semibold text-mono-black">No branches found</h2>
                <p className="text-sm text-mono-gray-600">
                  Start by creating a hub branch to begin building your network.
                </p>
                <Button variant="primary" size="sm">
                  <i className="fas fa-plus mr-2" />
                  Create Hub Branch
                </Button>
              </div>
            </Card>
          ) : (
            <div className="space-y-4">
              {hierarchyTree.map((branch) => (
                <TreeNode key={branch.id} branch={branch} level={0} />
              ))}
            </div>
          )}
        </div>
      </section>
    </div>
  );
};

export default BranchHierarchy;

import { useCallback, useMemo, useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import Card from '../../components/ui/Card';
import Button from '../../components/ui/Button';
import Input from '../../components/ui/Input';
import LoadingSpinner from '../../components/ui/LoadingSpinner';
import { searchApi } from '../../services/api';
import type { ApiResponse } from '../../services/api';

type SearchResult = {
  id?: string | number;
  type?: string;
  title?: string;
  subtitle?: string;
  description?: string;
  text?: string;
  url?: string;
  model?: Record<string, unknown>;
  [key: string]: unknown;
};

type SearchFilter = 'all' | 'shipment' | 'parcel' | 'customer';

const isPlainObject = (value: unknown): value is Record<string, unknown> => {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
};

const normaliseResults = (payload: unknown): SearchResult[] => {
  const toResultArray = (input: unknown): SearchResult[] => {
    if (!Array.isArray(input)) {
      return [];
    }

    return input.map((item) => {
      if (!isPlainObject(item)) {
        return item as SearchResult;
      }

      const model = isPlainObject(item.model) ? item.model : undefined;
      return {
        ...item,
        model,
      } as SearchResult;
    });
  };

  if (Array.isArray(payload)) {
    return toResultArray(payload);
  }

  if (isPlainObject(payload)) {
    const candidates = [payload.results, payload.data, payload.items];
    for (const candidate of candidates) {
      const resolved = toResultArray(candidate);
      if (resolved.length > 0) {
        return resolved;
      }
    }
  }

  return [];
};

const extractMeta = (result: SearchResult): Array<{ label: string; value: string }> => {
  const meta: Array<{ label: string; value: string }> = [];
  const model: Record<string, unknown> | undefined = isPlainObject(result.model) ? result.model : undefined;
  const type = (result.type ?? '').toString().toLowerCase();

  if (!model) {
    return meta;
  }

  const push = (label: string, value: unknown) => {
    if (value === null || value === undefined || value === '') {
      return;
    }
    meta.push({ label, value: String(value) });
  };

  const get = (object: Record<string, unknown>, key: string): unknown => object[key];

  if (type === 'shipment') {
    push('Status', get(model, 'current_status'));
    push('ETA', get(model, 'expected_delivery_date') ?? get(model, 'promised_delivery'));

    const originBranch = get(model, 'origin_branch');
    if (isPlainObject(originBranch)) {
      push('Origin', originBranch['name'] ?? originBranch['title']);
    }

    const destBranch = get(model, 'dest_branch');
    if (isPlainObject(destBranch)) {
      push('Destination', destBranch['name'] ?? destBranch['title']);
    }
  } else if (type === 'parcel') {
    push('Status', get(model, 'status'));
    push('COD', get(model, 'cash_collection') ?? get(model, 'cod_amount'));
    push('Weight', get(model, 'weight') ?? get(model, 'weight_kg'));
  } else if (type === 'customer') {
    push('Phone', get(model, 'phone') ?? get(model, 'contact_no'));

    const hub = get(model, 'hub');
    if (isPlainObject(hub)) {
      push('Hub', hub['name'] ?? hub['title']);
    } else {
      const branch = get(model, 'branch');
      if (isPlainObject(branch)) {
        push('Hub', branch['name'] ?? branch['title']);
      }
    }
  }

  push('Created', get(model, 'created_at') ?? get(model, 'createdAt'));

  return meta.slice(0, 3);
};

const FILTER_OPTIONS: Array<{ key: SearchFilter; label: string; description: string }> = [
  { key: 'all', label: 'All', description: 'Shipments, parcels, customers' },
  { key: 'shipment', label: 'Shipments', description: 'Movement pipeline' },
  { key: 'parcel', label: 'Parcels', description: 'Package tracking' },
  { key: 'customer', label: 'Customers', description: 'Client directory' },
];

const GlobalSearchPage: React.FC = () => {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SearchResult[]>([]);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [selectedFilter, setSelectedFilter] = useState<SearchFilter>('all');
  const [lastQuery, setLastQuery] = useState('');
  const [searchMeta, setSearchMeta] = useState<Record<string, unknown> | null>(null);

  const statsQuery = useQuery<Record<string, unknown>>({
    queryKey: ['search', 'stats'],
    queryFn: async () => {
      const response = await searchApi.stats();
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load search stats');
      }
      const enriched = response as ApiResponse<Record<string, unknown>> & { stats?: unknown };
      const primary = isPlainObject(enriched.stats) ? enriched.stats : enriched.data;
      return isPlainObject(primary) ? primary : {};
    },
  });

  const suggestionsQuery = useQuery<Array<Record<string, unknown>>>({
    queryKey: ['search', 'autocomplete', query],
    enabled: query.trim().length >= 2,
    queryFn: async () => {
      const response = await searchApi.autocomplete(query.trim(), 8);
      if (!response.success) {
        throw new Error(response.message ?? 'Unable to load suggestions');
      }
      const suggestions = response.suggestions ?? [];
      return Array.isArray(suggestions) ? suggestions : [];
    },
    staleTime: 30_000,
  });

  const searchMutation = useMutation({
    mutationFn: async ({ term, type }: { term: string; type?: string }) => {
      const params: Record<string, unknown> = { per_page: 40 };
      if (type) {
        params.type = type;
      }
      const response = await searchApi.search(term, params);
      if (!response.success) {
        throw new Error(response.message ?? 'Search failed');
      }

      const data = normaliseResults(response.data);
      const enriched = response as ApiResponse<unknown> & { meta?: unknown };
      const meta = isPlainObject(enriched.meta) ? enriched.meta : {};
      return { data, meta };
    },
    onSuccess: ({ data, meta }, variables) => {
      setResults(data);
      setSearchMeta(meta);
      setErrorMessage(null);
      setLastQuery(variables.term);
    },
    onError: (mutationError) => {
      setErrorMessage(mutationError instanceof Error ? mutationError.message : 'Search failed');
      setResults([]);
      setSearchMeta(null);
    },
  });

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const trimmed = query.trim();
    if (!trimmed) {
      setErrorMessage('Enter a query to search.');
      return;
    }

    const typeParam = selectedFilter === 'all' ? undefined : selectedFilter;
    searchMutation.mutate({ term: trimmed, type: typeParam });
  };

  const handleFilterChange = useCallback((filter: SearchFilter) => {
    setSelectedFilter(filter);
    if (lastQuery) {
      const typeParam = filter === 'all' ? undefined : filter;
      searchMutation.mutate({ term: lastQuery, type: typeParam });
    }
  }, [lastQuery, searchMutation]);

  const handleSuggestionClick = (suggestion: Record<string, unknown>) => {
    const text = String(suggestion.text ?? suggestion.title ?? suggestion.subtitle ?? '');
    if (!text) {
      return;
    }
    setQuery(text);
    const typeParam = selectedFilter === 'all' ? undefined : selectedFilter;
    searchMutation.mutate({ term: text, type: typeParam });
  };

  const filteredResults = useMemo(() => {
    if (selectedFilter === 'all') {
      return results;
    }
    return results.filter((result) => (result.type ?? '').toString().toLowerCase() === selectedFilter);
  }, [results, selectedFilter]);

  const groupedResults = useMemo<Array<[string, SearchResult[]]>>(() => {
    const map = new Map<string, SearchResult[]>();
    filteredResults.forEach((result) => {
      const key = (result.type ?? 'other').toString().toLowerCase();
      const bucket = map.get(key) ?? [];
      bucket.push(result);
      map.set(key, bucket);
    });
    return Array.from(map.entries());
  }, [filteredResults]);

  const counts = useMemo(() => {
    const base: Record<SearchFilter | 'other', number> = {
      all: results.length,
      shipment: 0,
      parcel: 0,
      customer: 0,
      other: 0,
    };

    results.forEach((result) => {
      const key = (result.type ?? 'other').toString().toLowerCase();
      if (key === 'shipment' || key === 'parcel' || key === 'customer') {
        base[key] += 1;
      } else {
        base.other += 1;
      }
    });

    return base;
  }, [results]);

  const stats = statsQuery.data ?? {};
  const suggestions = suggestionsQuery.data ?? [];
  const isSearching = searchMutation.isPending;
  const totalVisible = filteredResults.length;
  const totalOverall = counts.all;

  return (
    <div className="space-y-8">
      <header className="space-y-2">
        <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">Tools</p>
        <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">Global Search</h1>
        <p className="text-sm text-mono-gray-600 max-w-2xl">
          Locate shipments, parcels, and customers instantly. Results stream directly from the Laravel SearchService, grouped for quick triage.
        </p>
      </header>

      <Card className="border border-mono-gray-200 p-6">
        <form className="space-y-4" onSubmit={handleSubmit}>
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
            <Input
              type="search"
              placeholder="Search tracking numbers, SSCCs, customer emails…"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              className="flex-1"
            />
            <Button type="submit" variant="primary" size="md" disabled={isSearching}>
              <i className="fas fa-search mr-2" aria-hidden="true" />
              Search
            </Button>
          </div>

          <div className="flex flex-wrap gap-2">
            {FILTER_OPTIONS.map((option) => {
              const isActive = option.key === selectedFilter;
              return (
                <button
                  key={option.key}
                  type="button"
                  onClick={() => handleFilterChange(option.key)}
                  className={`rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] transition ${
                    isActive ? 'bg-mono-black text-mono-white shadow-lg' : 'bg-mono-gray-100 text-mono-gray-700 hover:bg-mono-gray-200'
                  }`}
                >
                  {option.label} • {option.key === 'all' ? counts.all : counts[option.key] ?? 0}
                </button>
              );
            })}
          </div>

          {suggestions.length > 0 && (
            <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3 text-xs text-mono-gray-600">
              <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">Suggestions</p>
              <div className="mt-2 flex flex-wrap gap-2">
                {suggestions.map((suggestion, index) => (
                  <button
                    key={String(suggestion.id ?? suggestion.text ?? index)}
                    type="button"
                    onClick={() => handleSuggestionClick(suggestion)}
                    className="rounded-full border border-mono-gray-200 bg-white px-3 py-1 text-[11px] uppercase tracking-[0.3em] text-mono-gray-600 hover:border-mono-black hover:text-mono-black"
                  >
                    {String(suggestion.text ?? suggestion.title ?? suggestion.subtitle ?? 'Suggestion')}
                  </button>
                ))}
              </div>
            </div>
          )}
        </form>

        {isSearching && <LoadingSpinner message="Fetching results" />}
        {errorMessage && !isSearching && (
          <p className="mt-4 text-sm text-red-600">{errorMessage}</p>
        )}
        {!errorMessage && !isSearching && lastQuery && (
          <p className="mt-4 text-xs uppercase tracking-[0.3em] text-mono-gray-500">
            Showing {totalVisible} of {totalOverall} matches for “{lastQuery}”.
          </p>
        )}
      </Card>

      <section className="grid gap-4 md:grid-cols-2">
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Search Metrics</h2>
          {statsQuery.isLoading && <LoadingSpinner message="Loading stats" />}
          {statsQuery.isError && (
            <p className="mt-2 text-sm text-red-600">
              {statsQuery.error instanceof Error ? statsQuery.error.message : 'Unable to load stats'}
            </p>
          )}
          {!statsQuery.isLoading && !statsQuery.isError && (
            <dl className="mt-4 space-y-3 text-sm text-mono-gray-700">
              {Object.entries(stats).map(([key, value]) => (
                <div key={key} className="flex items-center justify-between">
                  <dt className="font-medium capitalize">{key.replace(/_/g, ' ')}</dt>
                  <dd>{typeof value === 'number' ? value.toLocaleString() : String(value ?? '—')}</dd>
                </div>
              ))}
              {Object.keys(stats).length === 0 && <p>No analytics captured yet.</p>}
            </dl>
          )}
        </Card>

        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Result Breakdown</h2>
          <div className="mt-4 grid grid-cols-2 gap-3 text-sm">
            {FILTER_OPTIONS.filter((option) => option.key !== 'all').map((option) => (
              <div key={option.key} className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3">
                <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{option.label}</p>
                <p className="mt-1 text-xl font-semibold text-mono-black">{counts[option.key]}</p>
                <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-400">{option.description}</p>
              </div>
            ))}
            <div className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50 p-3">
              <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">Other</p>
              <p className="mt-1 text-xl font-semibold text-mono-black">{counts.other}</p>
              <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-400">Unsupported artefacts</p>
            </div>
          </div>
        </Card>
      </section>

      {groupedResults.length === 0 && !isSearching ? (
        <Card className="border border-dashed border-mono-gray-300 bg-mono-gray-50 p-10 text-center text-sm text-mono-gray-600">
          Run a search to visualise results grouped by entity type.
        </Card>
      ) : (
        <>
          {groupedResults.map(([groupKey, groupItems]) => (
            <Card key={groupKey} className="border border-mono-gray-200 p-6">
            <header className="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p className="text-[11px] uppercase tracking-[0.3em] text-mono-gray-500">{groupKey || 'Other'}</p>
                <h3 className="text-xl font-semibold text-mono-black">{groupItems.length} match{groupItems.length === 1 ? '' : 'es'}</h3>
              </div>
              <Button
                variant="ghost"
                size="sm"
                onClick={() => {
                  if (lastQuery) {
                    const nextFilter = (groupKey === 'shipment' || groupKey === 'parcel' || groupKey === 'customer')
                      ? (groupKey as SearchFilter)
                      : 'all';
                    setSelectedFilter(nextFilter);
                    const typeParam = nextFilter === 'all' ? undefined : nextFilter;
                    searchMutation.mutate({ term: lastQuery, type: typeParam });
                  }
                }}
              >
                Focus
              </Button>
            </header>

            <div className="mt-4 divide-y divide-mono-gray-200">
              {groupItems.map((item, index) => {
                const meta = extractMeta(item);
                return (
                  <div key={item.id ?? `${groupKey}-${index}`} className="py-4 first:pt-0 last:pb-0">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                      <div>
                        <h4 className="text-lg font-semibold text-mono-black">{item.title ?? 'Untitled'}</h4>
                        <p className="text-sm text-mono-gray-600">{item.subtitle ?? item.description ?? '—'}</p>
                      </div>
                      {item.url && (
                        <a
                          href={String(item.url)}
                          target="_blank"
                          rel="noreferrer"
                          className="rounded-full border border-mono-gray-200 px-4 py-2 text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-600 hover:border-mono-black hover:text-mono-black"
                        >
                          Open
                        </a>
                      )}
                    </div>
                    {meta.length > 0 && (
                      <div className="mt-3 flex flex-wrap gap-3 text-xs text-mono-gray-500">
                        {meta.map((entry) => (
                          <span key={`${entry.label}-${entry.value}`} className="rounded-full bg-mono-gray-100 px-3 py-1 uppercase tracking-[0.3em]">
                            {entry.label}: {entry.value}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </Card>
          ))}
        </>
      )}

      {searchMeta && isPlainObject(searchMeta) && Object.keys(searchMeta).length > 0 && (
        <Card className="border border-mono-gray-200 p-6">
          <h2 className="text-lg font-semibold text-mono-black">Backend Meta</h2>
          <pre className="mt-4 max-h-64 overflow-y-auto rounded-xl bg-mono-gray-50 p-4 text-xs text-mono-gray-700">
            {JSON.stringify(searchMeta, null, 2)}
          </pre>
        </Card>
      )}
    </div>
  );
};

export default GlobalSearchPage;

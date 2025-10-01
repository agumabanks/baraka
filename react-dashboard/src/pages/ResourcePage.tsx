import React from 'react';
import { ArrowUpRight } from 'lucide-react';
import type { RouteMeta } from '../lib/navigation';

interface ResourcePageProps {
  meta: RouteMeta;
  description?: string;
  legacyUrl?: string;
}

const ResourcePage: React.FC<ResourcePageProps> = ({ meta, description, legacyUrl }) => {
  const primaryDescription = description ??
    'This module is being migrated to the new control centre experience. Core actions and data views will appear here shortly.';

  return (
    <div className="space-y-10">
      <section className="rounded-3xl border border-mono-gray-200 bg-mono-white shadow-lg">
        <div className="border-b border-mono-gray-200 px-8 py-10 sm:px-12">
          <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div className="space-y-3">
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-mono-gray-500">
                {meta.parents.map((parent) => parent.label).join(' • ') || 'Module'}
              </p>
              <h1 className="text-3xl font-semibold text-mono-black sm:text-4xl">
                {meta.label}
              </h1>
              <p className="max-w-2xl text-sm leading-relaxed text-mono-gray-600">
                {primaryDescription}
              </p>
            </div>

            {legacyUrl && (
              <a
                href={legacyUrl}
                className="inline-flex items-center gap-2 self-start rounded-full border border-mono-gray-300 px-5 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-mono-black transition-colors hover:bg-mono-gray-900 hover:text-mono-white"
                target="_blank"
                rel="noopener noreferrer"
              >
                Open Legacy
                <ArrowUpRight size={16} />
              </a>
            )}
          </div>
        </div>

        <div className="px-8 py-8 sm:px-12">
          <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <article className="rounded-2xl border border-mono-gray-200 bg-mono-gray-50/70 p-6">
              <h2 className="text-sm font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                Status
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-mono-gray-600">
                We are aligning data models and workflows for this module. Expect live dashboards, filters, and contextual quick actions in upcoming iterations.
              </p>
            </article>

            <article className="rounded-2xl border border-mono-gray-200 bg-mono-white p-6 shadow-inner">
              <h2 className="text-sm font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                Next Steps
              </h2>
              <ul className="mt-3 space-y-2 text-sm text-mono-gray-600">
                <li className="flex items-start gap-2">
                  <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                  Finalise API mappings and entity schema.
                </li>
                <li className="flex items-start gap-2">
                  <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                  Design monochrome data grids with inline actions.
                </li>
                <li className="flex items-start gap-2">
                  <span className="mt-1 inline-block h-1.5 w-1.5 rounded-full bg-mono-black" aria-hidden="true" />
                  Connect role-based permissions for critical tasks.
                </li>
              </ul>
            </article>

            <article className="rounded-2xl border border-dashed border-mono-gray-300 bg-mono-gray-50/50 p-6">
              <h2 className="text-sm font-semibold uppercase tracking-[0.25em] text-mono-gray-500">
                Feedback Loop
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-mono-gray-600">
                If your team needs this module sooner, share the current workflows with product@baraka.sanaa.ug so we can prioritise the migration.
              </p>
            </article>
          </div>
        </div>
      </section>
    </div>
  );
};

export default ResourcePage;

import { create } from 'zustand';
import { devtools } from 'zustand/middleware';
import type { WorkflowItem, WorkflowStatus } from '../types/dashboard';
import type { WorkflowBoardShipment } from '../types/workflow';

export type WorkflowSummary = Record<WorkflowStatus | 'total', number>;

const emptySummary: WorkflowSummary = {
  total: 0,
  pending: 0,
  in_progress: 0,
  testing: 0,
  awaiting_feedback: 0,
  delayed: 0,
  completed: 0,
};

type WorkflowIdentifier = string | number;

export type WorkflowState = {
  queue: WorkflowItem[];
  board: WorkflowBoardShipment[];
  summary: WorkflowSummary;
  lastSyncedAt?: string;
  isSyncing: boolean;
  setQueue: (items: WorkflowItem[], summary?: WorkflowSummary) => void;
  setBoard: (items: WorkflowBoardShipment[]) => void;
  upsertQueueItem: (item: WorkflowItem) => void;
  removeQueueItem: (id: WorkflowIdentifier) => void;
  setSyncing: (value: boolean) => void;
};

const computeSummary = (items: WorkflowItem[]): WorkflowSummary => {
  const summary: WorkflowSummary = {
    total: items.length,
    pending: 0,
    in_progress: 0,
    testing: 0,
    awaiting_feedback: 0,
    delayed: 0,
    completed: 0,
  };

  for (const item of items) {
    const status = item.status ?? 'pending';
    if (summary[status] !== undefined) {
      summary[status] += 1;
    }
  }

  return summary;
};

const toId = (value: WorkflowIdentifier) => String(value);

const useWorkflowStore = create<WorkflowState>()(
  devtools(
    (set, get) => ({
      queue: [],
      board: [],
      summary: emptySummary,
      lastSyncedAt: undefined,
      isSyncing: false,
      setQueue: (items: WorkflowItem[], summaryOverride?: WorkflowSummary) => {
        const summary = summaryOverride ?? computeSummary(items);
        set({
          queue: items,
          summary,
          lastSyncedAt: new Date().toISOString(),
        });
      },
      setBoard: (items: WorkflowBoardShipment[]) => {
        set({
          board: items,
          lastSyncedAt: new Date().toISOString(),
        });
      },
      upsertQueueItem: (item: WorkflowItem) => {
        const queue = get().queue;
        const id = toId(item.id);
        const index = queue.findIndex((existing) => toId(existing.id) === id);
        let next: WorkflowItem[];

        if (index === -1) {
          next = [item, ...queue];
        } else {
          next = [...queue];
          next[index] = { ...next[index], ...item };
        }

        set({
          queue: next,
          summary: computeSummary(next),
          lastSyncedAt: new Date().toISOString(),
        });
      },
      removeQueueItem: (idValue: WorkflowIdentifier) => {
        const id = toId(idValue);
        const next = get()
          .queue
          .filter((item) => toId(item.id) !== id);

        set({
          queue: next,
          summary: computeSummary(next),
          lastSyncedAt: new Date().toISOString(),
        });
      },
      setSyncing: (value: boolean) => set({ isSyncing: value }),
    }),
    { name: 'workflow-store' },
  ),
);

export default useWorkflowStore;

# Task Board (Todo) — Comprehensive Audit

## 1. Product Experience
- **Inconsistent interaction model.** Current view is a static status grid without drag-and-drop. Moving tasks requires button clicks per card, which slows operators and introduces friction.
- **Lack of real-time feedback.** Task list refreshes on demand only (`refetch`). Changes made by other users are invisible until a manual reload, breaking collaborative awareness.
- **No visual status context.** Status columns have minimal differentiation and no dependency / blocker indicators. Priority and due-date cues are hard to scan quickly.
- **Missing bulk tooling.** Power users cannot multi-select, perform batch status updates, or delete/archive in bulk.
- **Filter/search limitations.** Filters are shallow (status/priority/search only) and cannot be saved. No presets for recurring views.
- **No timeline or time tracking signals.** Tasks show due dates but no elapsed time or active timers, making SLA oversight difficult.
- **Attachments & activity lack presence.** There is no affordance for attachments, notes, or activity history on the card, forcing modal deep-dives.

## 2. Technical & Architecture
- **Separation from Workflow Board.** Task board duplicates logic separate from the operations Workflow Board, diverging in UI/UX and causing maintenance overhead.
- **Permissions & role awareness absent.** Tasks can be transitioned by any authenticated user; there are no guardrails based on role, project, or assignee.
- **Lack of extensible data model.** `WorkflowItem` type omits dependencies, attachments, time tracking, state transition policies, etc., limiting future automation.
- **No real-time transport.** All updates are via polling. There is no WebSocket/SSE integration, and no shared state channel for multiple tabs/devices.
- **Limited offline resilience.** Actions fail silently if connection drops; there is no offline banner or queueing strategy.
- **Duplicated filter logic.** Filtering is computed locally with no caching or preset persistence.
- **Accessibility & keyboard gaps.** There is no keyboard navigation, ARIA drag/drop metadata, or shortcuts for frequent actions.

## 3. Integration & Consistency
- **Dashboard misalignment.** “Today’s Workflow” and Workflow Board do not stay in sync with Task Board actions.
- **Visual inconsistency.** Layout, spacing, and typography diverge from the newer Operations Workflow board.
- **Missing quick actions context.** No quick actions that reflect workflow automation features (dependencies, timers, fast reassignment).

## 4. Opportunities
- **Unify data + UI with Workflow board.** Reuse a shared Kanban component with customizable cards, permissions, and real-time hooks.
- **Introduce granular permissions.** Layer role/project/assignee checks into drag/drop and action menus.
- **Add collaborative infrastructure.** Incorporate WebSocket/SSE or BroadcastChannel to sync state; fall back to intelligent polling with optimistic updates.
- **Elevate card richness.** Surface dependencies, attachments, timers, and activity count on the card face.
- **Enhance filters.** Provide advanced filter panel with tag/project/owner filters, saved presets, and quick toggles.
- **Bulk operations + keyboard.** Add multi-select, bulk actions, and keyboard shortcuts for status transitions and opening detail panels.
- **Dashboard integration.** Expose summary metrics via shared store and broadcast changes to dashboard widgets.
- **Offline UX.** Notify users when offline, queue actions, and reconcile on reconnect.

## 5. Priority Themes
1. **Experience parity:** unify UI with operations Workflow Board and deliver fluid drag/drop.
2. **Collaboration & awareness:** live updates, activity logs, and dependency visualizations.
3. **Control & governance:** permission-aware transitions, audit trails, and custom workflow rules.
4. **Productivity tooling:** advanced filters, hotkeys, bulk operations, and saved views.
5. **Resilience:** offline handling, error surfacing, and consistent integration with dashboard modules.

This audit informs the enhancement plan implemented in this iteration.
